<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-teal-700">Patient portal</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Create your account</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Register for online appointments and access to your clinic information. We’ll verify your email at your first sign-in.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div><x-input-label for="first_name" value="First name" class="font-medium text-slate-700" /><x-text-input id="first_name" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>
            <div><x-input-label for="last_name" value="Last name" class="font-medium text-slate-700" /><x-text-input id="last_name" name="last_name" :value="old('last_name')" required autocomplete="family-name" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>
        </div>

        <div><x-input-label for="email" value="Email address" class="font-medium text-slate-700" /><x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="email" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>
        <div><x-input-label for="phone" value="Phone number" class="font-medium text-slate-700" /><x-text-input id="phone" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div><x-input-label for="gender" value="Gender" class="font-medium text-slate-700" /><select id="gender" name="gender" required class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700"><option value="">Select</option><option value="female" @selected(old('gender') === 'female')>Female</option><option value="male" @selected(old('gender') === 'male')>Male</option><option value="other" @selected(old('gender') === 'other')>Other</option></select></div>
            <div><x-input-label for="date_of_birth" value="Date of birth" class="font-medium text-slate-700" /><x-text-input id="date_of_birth" type="date" name="date_of_birth" :value="old('date_of_birth')" required max="{{ now()->subDay()->toDateString() }}" autocomplete="bday" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>
        </div>

        <div><x-input-label for="address" value="Home address (optional)" class="font-medium text-slate-700" /><textarea id="address" name="address" rows="2" autocomplete="street-address" class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700">{{ old('address') }}</textarea></div>
        <div><x-input-label for="password" value="Password" class="font-medium text-slate-700" /><x-text-input id="password" type="password" name="password" required autocomplete="new-password" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>
        <div><x-input-label for="password_confirmation" value="Confirm password" class="font-medium text-slate-700" /><x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1.5 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-teal-700 focus:ring-teal-700" /></div>

        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"><input type="checkbox" name="accepted_terms" value="1" required class="mt-0.5 rounded border-slate-300 text-teal-700 focus:ring-teal-700"><span>I confirm the information is accurate, agree to the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-semibold text-teal-800 underline">Terms of use</a>, and have read the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-semibold text-teal-800 underline">Privacy policy</a>.</span></label>
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"><i class="fa fa-user-plus" aria-hidden="true"></i>Create patient account</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">Already registered? <a href="{{ route('login') }}" class="font-semibold text-teal-800 hover:text-teal-950">Sign in</a></p>
</x-guest-layout>
