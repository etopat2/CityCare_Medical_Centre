@can('payments.exempt')
    @php($consultationFee = (float) ($appointment->consultation_fee ?? $appointment->doctor?->consultation_fee ?? 0))
    @if($consultationFee > 0 && (! $appointment->payment || $appointment->payment->balance > 0))
        <form method="POST" action="{{ route('appointments.fee-exemption', $appointment) }}" class="mt-5 space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4" data-confirm="Exempt the remaining consultation fee? This will settle the fee without recording a cash or online payment and give the patient access to consultation details." data-confirm-ok="Apply fee exemption">
            @csrf
            <div>
                <h3 class="font-semibold text-amber-950">Offer a consultation-fee exemption</h3>
                <p class="mt-1 text-sm text-amber-900">The remaining balance will be recorded as waived, not as money received.</p>
            </div>
            <label class="block text-sm font-medium text-slate-700">Reason
                <select name="reason" required class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="">Select an approved reason</option>
                    @foreach(\App\Models\PaymentExemption::REASONS as $value => $label)
                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium text-slate-700">Details (optional)
                <textarea name="details" rows="2" maxlength="2000" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Add context for the clinic's financial record">{{ old('details') }}</textarea>
            </label>
            <button class="w-full rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800">Apply fee exemption</button>
        </form>
    @endif
@endcan
