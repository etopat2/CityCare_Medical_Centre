<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-teal-700">Email verification</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Check your inbox</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">We sent a six-digit sign-in code to <span class="font-semibold text-slate-800">{{ $email }}</span>. The code expires in 10 minutes.</p>
    </div>

    <form method="POST" action="{{ route('login.otp.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" value="Six-digit code" class="font-medium text-slate-700" />
            <div class="relative mt-1.5"><i class="fa fa-key absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><x-text-input id="code" class="block w-full rounded-xl border-slate-300 py-3 ps-10 text-lg tracking-[0.35em] shadow-sm focus:border-teal-700 focus:ring-teal-700" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus /></div>
        </div>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"><i class="fa fa-shield" aria-hidden="true"></i>Verify and continue</button>
    </form>

    <form method="POST" action="{{ route('login.otp.resend') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-teal-800 hover:text-teal-950">Send a new code</button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500">You can close this page if you did not try to sign in.</p>
</x-guest-layout>
