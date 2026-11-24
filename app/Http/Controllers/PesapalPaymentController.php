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

/**
 * Starts and verifies PesaPal checkout flows for appointment, medication, and laboratory payments.
 */
class PesapalPaymentController extends Controller
{
    /** Starts an appointment checkout for a patient-entered partial amount, capped at the current balance. */
    public function start(Request $request, Appointment $appointment, PesapalService $pesapal): RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $appointment->patient_id === $patient->id, 404);
        abort_if(in_array($appointment->status, ['cancelled', 'no_show'], true), 422, 'This appointment cannot be paid online.');

        $appointment->loadMissing(['doctor.user', 'payment']);
        $fee = (float) ($appointment->consultation_fee ?? $appointment->doctor->consultation_fee);
        abort_if($fee <= 0, 422, 'This appointment does not have a payable fee.');
        $availableBalance = $appointment->payment?->balance ?? $fee;
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.(int) floor($availableBalance)],
        ]);
        $requestedAmount = (float) $validated['amount'];

        try {
            $transaction = DB::transaction(function () use ($appointment, $fee, $requestedAmount): PaymentTransaction {
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

                $balance = round($payment->balance, 2);
                abort_if($balance <= 0, 422, 'This appointment has no remaining balance.');
                abort_if($requestedAmount > $balance, 422, 'The amount entered is higher than the remaining balance.');

                abort_if(
                    $payment->transactions()->where('status', 'review')->exists(),
                    409,
                    'A previous online payment needs clinic review before another attempt.'
                );

                $pending = $payment->transactions()->where('status', 'pending')->latest('id')->first();
                if ($pending) {
                    if (abs((float) $pending->amount - $requestedAmount) < 0.01) {
                        return $pending;
                    }

                    // A payment amount can be changed safely only before an order is
                    // submitted to Pesapal. Once a checkout URL exists, its amount is fixed.
                    if (blank($pending->tracking_id) && blank($pending->redirect_url)) {
                        $pending->update([
                            'amount' => $requestedAmount,
                            'currency' => config('services.pesapal.currency', 'UGX'),
                            'status_description' => null,
                        ]);

                        return $pending->refresh();
                    }

                    return $pending;
                }

                return $payment->transactions()->create([
                    'provider' => 'pesapal',
                    'merchant_reference' => 'CC-'.Str::upper((string) Str::ulid()),
                    'amount' => $requestedAmount,
                    'currency' => config('services.pesapal.currency', 'UGX'),
                    'status' => 'pending',
                ]);
            });

            if (abs((float) $transaction->amount - $requestedAmount) > 0.01) {
                return back()->withErrors([
                    'amount' => 'A Pesapal checkout for UGX '.number_format((float) $transaction->amount).' is already in progress. Your entered amount was not charged. Finish or check the existing checkout from My payments before starting another amount.',
                ])->withInput();
            }

            if ($transaction->redirect_url && $transaction->tracking_id) {
                try {
                    $transaction = $this->refreshTransaction($transaction->tracking_id, $transaction->merchant_reference, $pesapal);
                } catch (Throwable $exception) {
                    report($exception);

                    return redirect()->route('portal.payments.checkout', $transaction);
                }

                if ($transaction->status === 'completed') {
                    return redirect()->route('portal.payments')->with('success', 'Payment received successfully.');
                }

                if (! in_array($transaction->status, ['failed', 'reversed'], true)) {
                    return redirect()->route('portal.payments.checkout', $transaction);
                }

                $payment = $transaction->payment()->firstOrFail();
                $transaction = $payment->transactions()->create([
                    'provider' => 'pesapal',
                    'merchant_reference' => 'CC-'.Str::upper((string) Str::ulid()),
                    'amount' => $requestedAmount,
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
                'callback_url' => $pesapal->publicRoute('payments.pesapal.callback'),
                'cancellation_url' => $pesapal->publicRoute('portal.payments'),
                'notification_id' => $ipnId,
                'billing_address' => array_filter([
                    'email_address' => $email,
                    'phone_number' => $phone !== '' ? $phone : null,
                    'country_code' => 'UG',
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'line_1' => $pesapal->formatAddressLine($patient->address),
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
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

            return redirect()->route('portal.payments.checkout', $transaction);
        } catch (Throwable $exception) {
            $amountLimitExceeded = $this->isPesapalAmountLimitError($exception);
            Log::error('Pesapal appointment checkout setup failed.', [
                'appointment_id' => $appointment->id,
                'payment_transaction_id' => isset($transaction) ? $transaction->id : null,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            if (isset($transaction) && $transaction instanceof PaymentTransaction && ! $transaction->redirect_url) {
                $transaction->update([
                    'status' => 'failed',
                    'status_description' => $amountLimitExceeded
                        ? 'Amount exceeds the PesaPal merchant transaction limit.'
                        : 'Checkout could not be started.',
                ]);
            }

            return back()->withErrors(['payment' => $amountLimitExceeded
                ? 'PesaPal declined this amount because it exceeds the clinic account transaction limit. Try a smaller partial payment, or contact the clinic to ask PesaPal to raise the limit. No payment was taken.'
                : 'We could not prepare the secure checkout. No payment was taken. Please try again, or contact the clinic if the problem continues.']);
        }
    }

    /** Starts an online medication checkout only after pharmacy review and validates the partial amount under lock. */
    public function startMedication(Request $request, \App\Models\MedicationOrder $medicationOrder, PesapalService $pesapal): RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $medicationOrder->patient_id === $patient->id, 404);
        abort_unless($medicationOrder->reviewed_at && $medicationOrder->payment, 409, 'The medication bill is not ready for payment.');
        abort_if($medicationOrder->consultation && ! $medicationOrder->consultation->appointment?->consultationFeeSettled(), 409, 'Settle the consultation fee before paying for or viewing prescription details.');
        $payment = $medicationOrder->payment;
        $balance = (float) $payment->balance;
        abort_if($balance <= 0, 422, 'This medication bill has no remaining balance.');
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:'.(int) floor($balance)]]);

        try {
            $transaction = DB::transaction(function () use ($medicationOrder, $data): PaymentTransaction {
                $order = \App\Models\MedicationOrder::query()->whereKey($medicationOrder->id)->lockForUpdate()->firstOrFail();
                $payment = Payment::query()->where('medication_order_id', $order->id)->lockForUpdate()->firstOrFail();
                $amount = (float) $data['amount'];
                abort_if($amount > $payment->balance, 422, 'The amount entered is higher than the remaining medication balance.');
                abort_if($payment->transactions()->where('status', 'review')->exists(), 409, 'A previous online payment needs clinic review.');
                $pending = $payment->transactions()->where('provider', 'pesapal')->where('status', 'pending')->latest('id')->first();
                if ($pending) {
                    abort_if(abs((float) $pending->amount - $amount) > 0.01, 409, 'A different medication checkout is already in progress. Finish it before changing the amount.');
                    return $pending;
                }

                return $payment->transactions()->create([
                    'provider' => 'pesapal',
                    'merchant_reference' => 'CC-'.Str::upper((string) Str::ulid()),
                    'amount' => $amount,
                    'currency' => config('services.pesapal.currency', 'UGX'),
                    'status' => 'pending',
                ]);
            });

            if ($transaction->redirect_url && $transaction->tracking_id) {
                return redirect()->route('portal.payments.checkout', $transaction);
            }
            $ipnId = config('services.pesapal.ipn_id');
            abort_if(blank($ipnId), 503, 'Pesapal is not fully configured.');
            $patientUser = $request->user();
            $patient = $medicationOrder->patient;
            $email = $patient->email ?: $patientUser->email;
            $phone = trim((string) $patient->phone);
            abort_if(blank($email) && $phone === '', 422, 'Add a phone number or email to your patient profile before paying online.');
            $result = $pesapal->submitOrder([
                'id' => $transaction->merchant_reference,
                'currency' => $transaction->currency,
                'amount' => (float) $transaction->amount,
                'description' => 'CityCare medication payment '.$medicationOrder->id,
                'callback_url' => $pesapal->publicRoute('payments.pesapal.callback'),
                'cancellation_url' => $pesapal->publicRoute('portal.payments'),
                'notification_id' => $ipnId,
                'billing_address' => array_filter([
                    'email_address' => $email,
                    'phone_number' => $phone !== '' ? $phone : null,
                    'country_code' => 'UG',
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'line_1' => $pesapal->formatAddressLine($patient->address),
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
            ]);
            $redirectUrl = $result['redirect_url'] ?? null;
            $trackingId = $result['order_tracking_id'] ?? null;
            $host = is_string($redirectUrl) ? parse_url($redirectUrl, PHP_URL_HOST) : null;
            abort_unless(is_string($redirectUrl) && filter_var($redirectUrl, FILTER_VALIDATE_URL) && is_string($host)
                && (strtolower($host) === 'pesapal.com' || str_ends_with(strtolower($host), '.pesapal.com'))
                && is_string($trackingId) && $trackingId !== '', 502, 'Pesapal did not return a valid checkout link.');
            $transaction->update(['tracking_id' => $trackingId, 'redirect_url' => $redirectUrl]);

            return redirect()->route('portal.payments.checkout', $transaction);
        } catch (Throwable $exception) {
            report($exception);
            $amountLimitExceeded = $this->isPesapalAmountLimitError($exception);
            if (isset($transaction) && $transaction instanceof PaymentTransaction && ! $transaction->redirect_url) {
                $transaction->update([
                    'status' => 'failed',
                    'status_description' => $amountLimitExceeded
                        ? 'Amount exceeds the PesaPal merchant transaction limit.'
                        : 'Checkout could not be started.',
                ]);
            }

            return back()->withErrors(['payment' => $amountLimitExceeded
                ? 'PesaPal declined this amount because it exceeds the clinic account transaction limit. Try a smaller partial payment, or contact the clinic to ask PesaPal to raise the limit. No payment was taken.'
                : 'We could not prepare the medication checkout. No payment was taken. Please try again.']);
        }
    }

    /** Starts an online laboratory checkout and prevents conflicting pending checkouts for different amounts. */
    public function startLab(Request $request, \App\Models\LabOrder $labOrder, PesapalService $pesapal): RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $labOrder->patient_id === $patient->id, 404);
        abort_if($labOrder->consultation && ! $labOrder->consultation->appointment?->consultationFeeSettled(), 409, 'Settle the consultation fee before paying for or viewing referral details.');
        $payment = $labOrder->payment;
        abort_unless($payment, 409, 'The laboratory bill is not ready for payment.');
        $balance = (float) $payment->balance;
        abort_if($balance <= 0, 422, 'This laboratory bill has no remaining balance.');
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:'.(int) floor($balance)]]);

        try {
            $transaction = DB::transaction(function () use ($labOrder, $data): PaymentTransaction {
                $order = \App\Models\LabOrder::query()->whereKey($labOrder->id)->lockForUpdate()->firstOrFail();
                $payment = Payment::query()->where('lab_order_id', $order->id)->lockForUpdate()->firstOrFail();
                $amount = (float) $data['amount'];
                abort_if($amount > $payment->balance, 422, 'The amount entered is higher than the remaining laboratory balance.');
                abort_if($payment->transactions()->where('status', 'review')->exists(), 409, 'A previous online payment needs clinic review.');
                $pending = $payment->transactions()->where('provider', 'pesapal')->where('status', 'pending')->latest('id')->first();
                if ($pending) {
                    abort_if(abs((float) $pending->amount - $amount) > 0.01, 409, 'A different laboratory checkout is already in progress. Finish it before changing the amount.');
                    return $pending;
                }
                return $payment->transactions()->create(['provider' => 'pesapal', 'merchant_reference' => 'CC-'.Str::upper((string) Str::ulid()), 'amount' => $amount, 'currency' => config('services.pesapal.currency', 'UGX'), 'status' => 'pending']);
            });

            if ($transaction->redirect_url && $transaction->tracking_id) return redirect()->route('portal.payments.checkout', $transaction);
            $ipnId = config('services.pesapal.ipn_id');
            abort_if(blank($ipnId), 503, 'Pesapal is not fully configured.');
            $patientUser = $request->user();
            $email = $patient->email ?: $patientUser->email;
            $phone = trim((string) $patient->phone);
            abort_if(blank($email) && $phone === '', 422, 'Add a phone number or email to your patient profile before paying online.');
            $result = $pesapal->submitOrder([
                'id' => $transaction->merchant_reference, 'currency' => $transaction->currency, 'amount' => (float) $transaction->amount,
                'description' => 'CityCare laboratory payment '.$labOrder->id, 'callback_url' => $pesapal->publicRoute('payments.pesapal.callback'),
                'cancellation_url' => $pesapal->publicRoute('portal.payments'), 'notification_id' => $ipnId,
                'billing_address' => array_filter([
                    'email_address' => $email,
                    'phone_number' => $phone !== '' ? $phone : null,
                    'country_code' => 'UG',
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'line_1' => $pesapal->formatAddressLine($patient->address),
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
            ]);
            $url = $result['redirect_url'] ?? null;
            $tracking = $result['order_tracking_id'] ?? null;
            $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
            abort_unless(is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && is_string($host) && (strtolower($host) === 'pesapal.com' || str_ends_with(strtolower($host), '.pesapal.com')) && is_string($tracking) && $tracking !== '', 502, 'Pesapal did not return a valid checkout link.');
            $transaction->update(['tracking_id' => $tracking, 'redirect_url' => $url]);
            return redirect()->route('portal.payments.checkout', $transaction);
        } catch (Throwable $exception) {
            report($exception);
            $amountLimitExceeded = $this->isPesapalAmountLimitError($exception);
            if (isset($transaction) && $transaction instanceof PaymentTransaction && ! $transaction->redirect_url) {
                $transaction->update([
                    'status' => 'failed',
                    'status_description' => $amountLimitExceeded
                        ? 'Amount exceeds the PesaPal merchant transaction limit.'
                        : 'Checkout could not be started.',
                ]);
            }
            return back()->withErrors(['payment' => $amountLimitExceeded
                ? 'PesaPal declined this amount because it exceeds the clinic account transaction limit. Try a smaller partial payment, or contact the clinic to ask PesaPal to raise the limit. No payment was taken.'
                : 'We could not prepare the laboratory checkout. No payment was taken. Please try again.']);
        }
    }

    /** Shows the hosted-checkout handoff only to the patient who owns the pending transaction. */
    public function checkout(Request $request, PaymentTransaction $paymentTransaction): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $paymentTransaction->payment()->where('patient_id', $patient->id)->exists(), 404);
        abort_unless($paymentTransaction->provider === 'pesapal' && filled($paymentTransaction->redirect_url), 404);

        if ($paymentTransaction->status === 'completed') {
            return redirect()->route('portal.payments')->with('success', 'This payment has already been received.');
        }

        if (in_array($paymentTransaction->status, ['failed', 'reversed', 'review'], true)) {
            return redirect()->route('portal.payments')->with('status', 'This payment session is no longer available. Start a new payment or contact the clinic.');
        }

        $paymentTransaction->load(['payment.appointment.doctor.user', 'payment.medicationOrder.consultation.appointment.doctor.user', 'payment.labOrder.appointment.doctor.user', 'payment.labOrder.items.test']);

        return view('portal.payments.checkout', ['transaction' => $paymentTransaction]);
    }

    /** Handles the browser return from PesaPal and refreshes provider status before messaging the patient. */
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

    /** Lets a patient refresh only a PesaPal transaction linked to their own payment. */
    public function refresh(Request $request, PaymentTransaction $paymentTransaction, PesapalService $pesapal): RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $paymentTransaction->payment()->where('patient_id', $patient->id)->exists(), 404);
        abort_unless($paymentTransaction->provider === 'pesapal' && filled($paymentTransaction->tracking_id), 404);

        try {
            $transaction = $this->refreshTransaction(
                $paymentTransaction->tracking_id,
                $paymentTransaction->merchant_reference,
                $pesapal,
            );

            $message = match ($transaction->status) {
                'completed' => 'Payment received successfully.',
                'failed', 'reversed' => 'Pesapal did not complete the payment. You can try again.',
                'review' => 'This payment needs clinic review. Please contact the clinic.',
                default => 'Payment is still being processed. Check again shortly.',
            };

            return redirect()->route('portal.payments')->with('status', $message);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('portal.payments')->with('status', 'We could not refresh the Pesapal status. Please try again shortly.');
        }
    }

    /** Acknowledges PesaPal's server notification after querying and reconciling its transaction status. */
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

    /**
     * Verifies provider identifiers and amount/currency, then applies the payment state exactly once under lock.
     * Invalid or inconsistent provider responses are kept out of the paid balance and sent for review.
     */
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
                if ($newAmountPaid + (float) $payment->amount_waived > (float) $payment->amount + 0.01) {
                    $locked->update(['status' => 'review']);
                    Log::critical('Pesapal payment would exceed the appointment balance.', ['payment_transaction_id' => $locked->id]);

                    return $locked->refresh();
                }

                $payment->amount_paid = min($newAmountPaid, max(0, (float) $payment->amount - (float) $payment->amount_waived));
                $payment->payment_method = $this->mapPaymentMethod($locked->payment_method);
                $payment->status = $payment->amount_paid + (float) $payment->amount_waived >= (float) $payment->amount ? 'paid' : 'partial';
                $payment->paid_at = now();
                $payment->received_by = null;
                $payment->save();
                if ($payment->billing_type === 'laboratory' && $payment->status === 'paid') {
                    $payment->labOrder()->update(['status' => 'queued']);
                }
            } elseif ($newStatus === 'reversed' && $wasCompleted) {
                $payment->amount_paid = max(0, round((float) $payment->amount_paid - (float) $locked->amount, 2));
                $payment->status = $payment->amount_paid + (float) $payment->amount_waived >= (float) $payment->amount
                    ? 'paid'
                    : ($payment->amount_paid > 0 ? 'partial' : 'pending');
                $payment->paid_at = $payment->amount_paid > 0 ? now() : null;
                $payment->save();
            }

            return $locked->refresh();
        });
    }

    /** Maps PesaPal's method description to the clinic's card or mobile-money receipt category. */
    private function mapPaymentMethod(?string $pesapalMethod): string
    {
        return str_contains(strtolower((string) $pesapalMethod), 'card')
            || preg_match('/visa|mastercard|amex|american express|discover/i', (string) $pesapalMethod)
            ? 'card'
            : 'mobile_money';
    }

    /** Identifies the provider's merchant-level amount cap so checkout can give an actionable response. */
    private function isPesapalAmountLimitError(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'amount exceeds limit')
            || str_contains($message, 'amount exceeds the transaction limit');
    }
}
