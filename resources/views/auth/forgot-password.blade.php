<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-teal-700">Account recovery</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Reset your password</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Enter your account email. If it matches a CityCare account, we’ll send a one-time reset code.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" class="font-medium text-slate-700" />
            <div class="relative mt-1.5"><i class="fa fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><x-text-input id="email" class="block w-full rounded-xl border-slate-300 py-3 ps-10 shadow-sm focus:border-teal-700 focus:ring-teal-700" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" /></div>
        </div>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"><i class="fa fa-paper-plane" aria-hidden="true"></i>Send reset code</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-semibold text-teal-800 hover:text-teal-950"><i class="fa fa-arrow-left me-1" aria-hidden="true"></i>Return to sign in</a></p>
</x-guest-layout>
