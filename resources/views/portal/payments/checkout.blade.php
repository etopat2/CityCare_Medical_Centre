<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">CityCare secure checkout</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-900">Complete your payment</h2>
            </div>
            <a href="{{ route('portal.payments') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Back to payments</a>
        </div>
    </x-slot>

    @php
        $payment = $transaction->payment;
        $appointment = $payment->appointment;
    @endphp

    <div class="mx-auto max-w-3xl py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-800" aria-hidden="true"><i class="fa fa-lock text-xl"></i></span>
            <h3 class="mt-4 text-xl font-semibold text-slate-900">Your secure checkout is ready</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Choose card or mobile money in the secure payment window. Your payment is processed by Pesapal; CityCare does not store your full card details.</p>

            <dl class="mt-6 grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Amount to pay</dt><dd class="mt-1 text-lg font-bold text-slate-900">{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</dd></div>
                <div><dt class="text-slate-500">Appointment</dt><dd class="mt-1 font-medium text-slate-800">{{ $appointment->appointment_date->format('j M Y') }} · Dr. {{ $appointment->doctor->user->name }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Payment reference</dt><dd class="mt-1 break-all font-mono text-xs text-slate-700">{{ $transaction->merchant_reference }}</dd></div>
            </dl>

            <button type="button" data-open-payment-modal class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 sm:w-auto">
                <i class="fa fa-lock" aria-hidden="true"></i> Open secure payment
            </button>
            <p class="mt-3 text-xs text-slate-500">Keep this page open until the payment is confirmed. You can close the payment window and reopen it here.</p>
        </section>
    </div>

    <dialog
        id="payment-method-modal"
        aria-labelledby="payment-modal-title"
        class="fixed inset-0 z-[100] m-auto flex h-[calc(100dvh-1rem)] max-h-none w-[calc(100%-1rem)] max-w-6xl flex-col overflow-hidden rounded-2xl bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-sm sm:h-[calc(100dvh-3rem)] sm:w-[calc(100%-3rem)]"
    >
        <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-6">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Secure payment</p>
                <h3 id="payment-modal-title" class="mt-0.5 truncate text-lg font-semibold text-slate-900">Choose a payment method</h3>
            </div>
            <button type="button" data-close-payment-modal class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-600" aria-label="Close secure checkout">
                <i class="fa fa-times" aria-hidden="true"></i><span class="hidden sm:inline">Close</span>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_17rem]">
                <section class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <p class="text-sm leading-6 text-slate-600">Pay by card or mobile money using Pesapal's secure checkout. Available methods may depend on your location and the clinic's account.</p>
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <iframe
                            id="payments-holder"
                            src="{{ $transaction->redirect_url }}"
                            title="Secure card and mobile money checkout"
                            class="block h-[min(68vh,40rem)] min-h-[360px] w-full bg-white sm:h-[min(70vh,42rem)] sm:min-h-[520px]"
                            allow="payment *; clipboard-read; clipboard-write"
                            referrerpolicy="strict-origin-when-cross-origin"
                            loading="eager"
                        ></iframe>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <span>Your full card details are entered with Pesapal and are not stored by CityCare.</span>
                        <a href="{{ $transaction->redirect_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-teal-800 underline decoration-teal-300 underline-offset-4 hover:text-teal-950">Open in a new tab</a>
                    </div>
                </section>

                <aside class="h-fit rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment summary</h4>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</p>
                    <dl class="mt-4 space-y-3 border-t border-slate-200 pt-4 text-sm">
                        <div><dt class="text-slate-500">Appointment</dt><dd class="mt-1 font-medium text-slate-800">{{ $appointment->appointment_date->format('j M Y') }}</dd></div>
                        <div><dt class="text-slate-500">Doctor</dt><dd class="mt-1 font-medium text-slate-800">Dr. {{ $appointment->doctor->user->name }}</dd></div>
                        <div><dt class="text-slate-500">Reference</dt><dd class="mt-1 break-all font-mono text-xs text-slate-700">{{ $transaction->merchant_reference }}</dd></div>
                    </dl>
                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs leading-5 text-amber-900">Keep this window open until checkout returns you to CityCare. Mobile money payments can take a short time to confirm.</p>
                </aside>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const dialogs = document.querySelectorAll('#payment-method-modal');
            const modal = dialogs[0];
            dialogs.forEach((duplicate, index) => index > 0 && duplicate.remove());

            if (!modal) return;

            const openModal = () => {
                if (!modal.open) modal.showModal();
            };

            document.querySelector('[data-open-payment-modal]')?.addEventListener('click', openModal);
            document.querySelector('[data-close-payment-modal]')?.addEventListener('click', event => {
                event.preventDefault();
                if (modal.open) modal.close();
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', openModal, { once: true });
            } else {
                openModal();
            }
        })();
    </script>
</x-app-layout>
