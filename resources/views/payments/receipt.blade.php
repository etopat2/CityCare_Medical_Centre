<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CityCare receipt {{ $receiptTransaction?->merchant_reference ?? $payment->reference_number }}</title>
    <style>
        body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:12px;margin:36px}.header{border-bottom:3px solid #0f766e;padding-bottom:16px;margin-bottom:24px}.brand{font-size:22px;font-weight:bold;color:#0f766e}.muted{color:#64748b}.label{color:#64748b;width:42%}table{width:100%;border-collapse:collapse;margin-top:22px}td,th{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left}.total{font-size:16px;font-weight:bold}.footer{margin-top:36px;border-top:1px solid #cbd5e1;padding-top:12px;color:#64748b}
    </style>
</head>
<body>
    <header class="header"><div class="brand">CityCare Medical Centre</div><div class="muted">Patient payment receipt · Kampala, Uganda</div></header>
    <h2>Payment receipt</h2>
    <p class="muted">Receipt {{ $receiptTransaction?->merchant_reference ?? $payment->reference_number }} · Generated {{ now()->format('j F Y, g:i A') }}</p>
    <table>
        <tr><td class="label">Patient</td><td>{{ $payment->patient->first_name }} {{ $payment->patient->last_name }} ({{ $payment->patient->patient_number }})</td></tr>
        <tr><td class="label">Charge type</td><td>{{ ['medication' => 'Medication fee', 'laboratory' => 'Laboratory tests'][$payment->billing_type] ?? 'Appointment / consultation fee' }}</td></tr>
        <tr><td class="label">Appointment</td><td>{{ $payment->visit_appointment?->appointment_date?->format('j F Y') ?? '—' }} · {{ $payment->visit_appointment?->doctor?->user?->name ?? '—' }}</td></tr>
        @if($payment->visit_appointment?->department)<tr><td class="label">Department</td><td>{{ $payment->visit_appointment->department->name }}</td></tr>@endif
        @if($payment->billing_type === 'medication' && $payment->medicationOrder)
            @if(! auth()->user()->hasRole('patient') || ! $payment->medicationOrder->consultation || $payment->medicationOrder->consultation->appointment?->consultationFeeSettled())
                <tr><td colspan="2"><strong>Medication items</strong></td></tr>
                @foreach($payment->medicationOrder->items->whereIn('status', ['ready', 'dispensed']) as $item)
                    <tr><td>{{ $item->medicine->name }} {{ $item->medicine->strength }}</td><td>{{ $item->quantity }} × UGX {{ number_format((float) $item->unit_price) }} — UGX {{ number_format((float) $item->line_total) }}</td></tr>
                @endforeach
            @else
                <tr><td colspan="2">Prescription details are available after consultation fee settlement.</td></tr>
            @endif
        @endif
        @if($payment->billing_type === 'laboratory' && $payment->labOrder)
            @if(! auth()->user()->hasRole('patient') || ! $payment->labOrder->consultation || $payment->labOrder->consultation->appointment?->consultationFeeSettled())
                <tr><td colspan="2"><strong>Laboratory tests</strong></td></tr>
                @foreach($payment->labOrder->items as $item)
                    <tr><td>{{ $item->test->name }}</td><td>{{ $item->test->specimen }} · UGX {{ number_format((float) $item->price) }}</td></tr>
                @endforeach
            @else
                <tr><td colspan="2">Referral details are available after consultation fee settlement.</td></tr>
            @endif
        @endif
        @if($receiptTransaction)
            <tr><td class="label">Payment date</td><td>{{ $receiptTransaction->created_at?->format('j F Y, g:i A') ?? now()->format('j F Y, g:i A') }}</td></tr>
            <tr><td class="label">Payment method</td><td>{{ ucfirst(str_replace('_', ' ', $receiptTransaction->payment_method ?: $payment->payment_method ?: 'online')) }}</td></tr>
            <tr><td class="label">Transaction reference</td><td>{{ $receiptTransaction->merchant_reference }}</td></tr>
            @if($receiptTransaction->confirmation_code)<tr><td class="label">Confirmation code</td><td>{{ $receiptTransaction->confirmation_code }}</td></tr>@endif
            <tr><td class="label">Payment received</td><td class="total">UGX {{ number_format((float) $receiptTransaction->amount, 2) }}</td></tr>
        @else
            <tr><td class="label">Payment date</td><td>{{ $payment->paid_at?->format('j F Y, g:i A') ?? '—' }}</td></tr>
            <tr><td class="label">Payment method</td><td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? '—')) }}</td></tr>
            <tr><td class="label">Amount charged</td><td>UGX {{ number_format((float) $payment->amount, 2) }}</td></tr>
            <tr><td class="label">Total received</td><td class="total">UGX {{ number_format((float) $payment->amount_paid, 2) }}</td></tr>
            @if((float) $payment->amount_waived > 0)
                <tr><td class="label">Fee waived</td><td>UGX {{ number_format((float) $payment->amount_waived, 2) }}</td></tr>
                @if($payment->exemption)<tr><td class="label">Waiver reason</td><td>{{ \App\Models\PaymentExemption::REASONS[$payment->exemption->reason] ?? ucfirst(str_replace('_', ' ', $payment->exemption->reason)) }}</td></tr>@endif
            @endif
            <tr><td class="label">Balance due</td><td>UGX {{ number_format($payment->balance, 2) }}</td></tr>
        @endif
    </table>
    <footer class="footer">Thank you for choosing CityCare Medical Centre. Keep this receipt for your records.</footer>
</body>
</html>
