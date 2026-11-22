<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\PesapalService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PesapalPaymentController extends Controller
{
    public function start(Request $request, Appointment $appointment, PesapalService $pesapal): RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $appointment->patient_id === $patient->id, 404);
        abort_if(in_array($appointment->status, ['cancelled', 'no_show'], true), 422, 'This appointment cannot be paid online.');

        $appointment->loadMissing(['doctor.user', 'payment']);
        $fee = (float) $appointment->doctor->consultation_fee;
        abort_if($fee <= 0, 422, 'This appointment does not have a payable fee.');

        try {
            $transaction = DB::transaction(function () use ($appointment, $fee): PaymentTransaction {
                $lockedAppointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
                $payment = Payment::query()->where('appointment_id', $lockedAppointment->id)->lockForUpdate()->first();

                if (! $payment) {
                    $payment = Payment::query()->create([
                        'appointment_id' => $lockedAppointment->id,
                        'patient_id' => $lockedAppointment->patient_id,
                        'amount' => $fee,
                        'amount_paid' => 0,
                        'payment_method' => null,
                        'status' => 'pending',
                        'reference_number' => 'CCPAY-'.Str::upper(Str::random(12)),
                        'received_by' => null,
                    ]);
                }

                $balance = round((float) $payment->amount - (float) $payment->amount_paid, 2);
                abort_if($balance <= 0, 422, 'This appointment has no remaining balance.');

                abort_if(
                    $payment->transactions()->where('status', 'review')->exists(),
                    409,
                    'A previous online payment needs clinic review before another attempt.'
                );

                $pending = $payment->transactions()->where('status', 'pending')->latest('id')->first();
                if ($pending) {
                    return $pending;
                }

                return $payment->transactions()->create([
                    'provider' => 'pesapal',
                    'merchant_reference' => 'CC-'.Str::upper((string) Str::ulid()),
                    'amount' => $balance,
                    'currency' => config('services.pesapal.currency', 'UGX'),
                    'status' => 'pending',
                ]);
            });

            if ($transaction->redirect_url && $transaction->tracking_id) {
                try {
                    $transaction = $this->refreshTransaction($transaction->tracking_id, $transaction->merchant_reference, $pesapal);
                } catch (Throwable $exception) {
                    report($exception);

                    return redirect()->away($transaction->redirect_url);
                }

                if ($transaction->status === 'completed') {
                    return redirect()->route('portal.payments')->with('success', 'Payment received successfully.');
                }

                if (! in_array($transaction->status, ['failed', 'reversed'], true)) {
                    return redirect()->away($transaction->redirect_url);
                }

                $payment = $transaction->payment()->firstOrFail();
                $transaction = $payment->transactions()->create([
                    'provider' => 'pesapal',
                    'merchant_reference' => 'CC-'.Str::upper((string) Str::ulid()),
                    'amount' => round($payment->balance, 2),
                    'currency' => config('services.pesapal.currency', 'UGX'),
                    'status' => 'pending',
                ]);
            }

            $ipnId = config('services.pesapal.ipn_id');
            abort_if(blank($ipnId), 503, 'Pesapal is not fully configured. Please contact the clinic.');

            $patientUser = $request->user();
            $patient = $appointment->patient;
            $email = $patient->email ?: $patientUser->email;
            $phone = trim((string) $patient->phone);
            abort_if(blank($email) && $phone === '', 422, 'Add a phone number or email to your patient profile before paying online.');

            $result = $pesapal->submitOrder([
                'id' => $transaction->merchant_reference,
                'currency' => $transaction->currency,
                'amount' => (float) $transaction->amount,
                'description' => 'CityCare appointment payment '.$appointment->id,
                'callback_url' => route('payments.pesapal.callback'),
                'cancellation_url' => route('portal.payments'),
                'notification_id' => $ipnId,
                'billing_address' => [
                    'email_address' => $email,
                    'phone_number' => $phone,
                    'country_code' => 'UG',
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'line_1' => $patient->address ?: '',
                ],
            ]);

            $redirectUrl = $result['redirect_url'] ?? null;
            $trackingId = $result['order_tracking_id'] ?? null;
            $redirectHost = is_string($redirectUrl) ? parse_url($redirectUrl, PHP_URL_HOST) : null;
            abort_unless(
                is_string($redirectUrl)
                && filter_var($redirectUrl, FILTER_VALIDATE_URL)
                && is_string($redirectHost)
                && (strtolower($redirectHost) === 'pesapal.com' || str_ends_with(strtolower($redirectHost), '.pesapal.com'))
                && is_string($trackingId)
                && $trackingId !== '',
                502,
                'Pesapal did not return a valid checkout link.'
            );

            $transaction->update(['tracking_id' => $trackingId, 'redirect_url' => $redirectUrl]);

            return redirect()->away($redirectUrl);
        } catch (Throwable $exception) {
            report($exception);
            if (isset($transaction) && $transaction instanceof PaymentTransaction && ! $transaction->redirect_url) {
                $transaction->update(['status' => 'failed', 'status_description' => 'Checkout could not be started.']);
            }

            return back()->withErrors(['payment' => 'We could not start the Pesapal checkout. Please try again or contact the clinic.']);
        }
    }

    public function callback(Request $request, PesapalService $pesapal): RedirectResponse
    {
        $trackingId = $request->string('OrderTrackingId')->toString();
        $merchantReference = $request->string('OrderMerchantReference')->toString();

        try {
            $transaction = $this->refreshTransaction($trackingId, $merchantReference, $pesapal);
            $message = match ($transaction->status) {
                'completed' => 'Payment received successfully.',
                'failed', 'reversed' => 'Pesapal did not complete the payment. You can try again.',
                default => 'Payment is still being processed. Your balance will update when Pesapal confirms it.',
            };

            return $request->user()
                ? redirect()->route('portal.payments')->with('success', $message)
                : redirect()->guest(route('login'))->with('success', $message);
        } catch (Throwable $exception) {
            report($exception);

            return $request->user()
                ? redirect()->route('portal.payments')->with('status', 'We are checking the payment status. Refresh your payments shortly.')
                : redirect()->guest(route('login'))->with('status', 'We are checking the payment status. Sign in shortly to review your payments.');
        }
    }

    public function ipn(Request $request, PesapalService $pesapal): JsonResponse
    {
        $trackingId = $request->string('OrderTrackingId')->toString();
        $merchantReference = $request->string('OrderMerchantReference')->toString();
        $notificationType = $request->string('OrderNotificationType')->toString();

        try {
            $transaction = $this->refreshTransaction($trackingId, $merchantReference, $pesapal);

            return response()->json([
                'orderNotificationType' => $notificationType ?: 'IPNCHANGE',
                'orderTrackingId' => $trackingId,
                'orderMerchantReference' => $merchantReference,
                'status' => 200,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'orderNotificationType' => $notificationType ?: 'IPNCHANGE',
                'orderTrackingId' => $trackingId,
                'orderMerchantReference' => $merchantReference,
                'status' => 500,
            ], 500);
        }
    }

    private function refreshTransaction(string $trackingId, string $merchantReference, PesapalService $pesapal): PaymentTransaction
    {
        if ($trackingId === '' || $merchantReference === '') {
            throw new ModelNotFoundException;
        }

        $transaction = PaymentTransaction::query()
            ->where('merchant_reference', $merchantReference)
            ->where('tracking_id', $trackingId)
            ->firstOrFail();

        $status = $pesapal->transactionStatus($trackingId);
        if (($status['merchant_reference'] ?? null) !== $merchantReference) {
            Log::warning('Pesapal returned a merchant reference mismatch.', ['payment_transaction_id' => $transaction->id]);

            throw new \RuntimeException('Pesapal merchant reference did not match.');
        }

        $paidAmount = isset($status['amount']) ? (float) $status['amount'] : null;
        $paidCurrency = strtoupper((string) ($status['currency'] ?? ''));
        if ($paidAmount === null
            || abs($paidAmount - (float) $transaction->amount) > 0.01
            || $paidCurrency !== strtoupper((string) $transaction->currency)) {
            Log::critical('Pesapal returned an amount or currency mismatch.', ['payment_transaction_id' => $transaction->id]);
            $transaction->update([
                'status' => 'review',
                'status_description' => 'Pesapal amount or currency mismatch; manual review required.',
            ]);

            return $transaction->refresh();
        }

        $statusCode = (int) ($status['status_code'] ?? -1);
        $newStatus = match ($statusCode) {
            1 => 'completed',
            2, 0 => 'failed',
            3 => 'reversed',
            default => 'pending',
        };

        return DB::transaction(function () use ($transaction, $status, $newStatus): PaymentTransaction {
            $locked = PaymentTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->whereKey($locked->payment_id)->lockForUpdate()->firstOrFail();
            $wasCompleted = $locked->status === 'completed';

            $locked->update([
                'status' => $newStatus,
                'payment_method' => isset($status['payment_method']) ? (string) $status['payment_method'] : null,
                'confirmation_code' => isset($status['confirmation_code']) ? (string) $status['confirmation_code'] : null,
                'payment_account' => isset($status['payment_account']) ? (string) $status['payment_account'] : null,
                'status_description' => isset($status['payment_status_description'])
                    ? (string) $status['payment_status_description']
                    : (isset($status['description']) ? (string) $status['description'] : null),
            ]);

            if ($newStatus === 'completed' && ! $wasCompleted) {
                $newAmountPaid = round((float) $payment->amount_paid + (float) $locked->amount, 2);
                if ($newAmountPaid > (float) $payment->amount + 0.01) {
                    $locked->update(['status' => 'review']);
                    Log::critical('Pesapal payment would exceed the appointment balance.', ['payment_transaction_id' => $locked->id]);

                    return $locked->refresh();
                }

                $payment->amount_paid = min($newAmountPaid, (float) $payment->amount);
                $payment->payment_method = $this->mapPaymentMethod($locked->payment_method);
                $payment->status = $payment->amount_paid >= (float) $payment->amount ? 'paid' : 'partial';
                $payment->paid_at = now();
                $payment->received_by = null;
                $payment->save();
            } elseif ($newStatus === 'reversed' && $wasCompleted) {
                $payment->amount_paid = max(0, round((float) $payment->amount_paid - (float) $locked->amount, 2));
                $payment->status = $payment->amount_paid >= (float) $payment->amount
                    ? 'paid'
                    : ($payment->amount_paid > 0 ? 'partial' : 'pending');
                $payment->paid_at = $payment->amount_paid > 0 ? now() : null;
                $payment->save();
            }

            return $locked->refresh();
        });
    }

    private function mapPaymentMethod(?string $pesapalMethod): string
    {
        return str_contains(strtolower((string) $pesapalMethod), 'card')
            || preg_match('/visa|mastercard|amex|american express|discover/i', (string) $pesapalMethod)
            ? 'card'
            : 'mobile_money';
    }
}
