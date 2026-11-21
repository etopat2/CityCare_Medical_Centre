<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-teal-700">Welcome back</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Sign in to CityCare</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Use your clinic or patient portal account to continue.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Email address" class="font-medium text-slate-700" />
            <div class="relative mt-1.5"><i class="fa fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><x-text-input id="email" class="block w-full rounded-xl border-slate-300 py-3 ps-10 shadow-sm focus:border-teal-700 focus:ring-teal-700" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" /></div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-2"><x-input-label for="password" value="Password" class="font-medium text-slate-700" />@if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-xs font-semibold text-teal-800 hover:text-teal-950">Forgot password?</a>@endif</div>
            <div class="relative mt-1.5"><i class="fa fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><x-text-input id="password" class="block w-full rounded-xl border-slate-300 py-3 ps-10 shadow-sm focus:border-teal-700 focus:ring-teal-700" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" /></div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600"><input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-700" name="remember"><span>Keep me signed in</span></label>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"><i class="fa fa-sign-in" aria-hidden="true"></i>Sign in securely</button>
    </form>

    <div class="mt-6 flex items-start gap-3 rounded-xl border border-teal-100 bg-teal-50 p-4 text-xs leading-5 text-slate-600"><i class="fa fa-heartbeat mt-0.5 text-teal-700" aria-hidden="true"></i><p>New to CityCare? <a href="{{ route('register') }}" class="font-semibold text-teal-800 hover:text-teal-950">Create a patient portal account</a>.</p></div>
</x-guest-layout>
