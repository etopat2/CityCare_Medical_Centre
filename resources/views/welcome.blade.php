<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f3d3e">
    <meta name="description" content="Explore CityCare Medical Centre services in Kampala, from consultations and laboratory testing to pharmacy, patient records and online payments.">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CityCare">
    <title>{{ config('app.name', 'CityCare Medical Centre') }} | Connected care in Kampala</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/citycare-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(error => console.error('CityCare service worker registration failed.', error)));
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-800 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-teal-900 focus:shadow-xl">Skip to content</a>
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 sm:px-8 lg:px-10">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-3" aria-label="CityCare Medical Centre home">
                <x-application-logo class="size-11 rounded-2xl bg-teal-700 p-2.5 text-white shadow-lg shadow-teal-900/15" />
                <span><span class="block text-base font-bold tracking-tight text-slate-950">CityCare</span><span class="block text-xs text-slate-500">Medical Centre · Kampala</span></span>
            </a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex" aria-label="Main navigation">
                <a href="#services" class="transition hover:text-teal-800">Our services</a>
                <a href="#care-journey" class="transition hover:text-teal-800">How care works</a>
                <a href="#care-team" class="transition hover:text-teal-800">Care team</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"><i class="fa fa-th-large" aria-hidden="true"></i><span class="hidden sm:inline">Open dashboard</span><span class="sm:hidden">Dashboard</span></a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-teal-900 transition hover:bg-teal-50">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Patient account</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main-content" data-no-section-nav>
        <section class="relative isolate overflow-hidden bg-[#f1f8f7]">
            <div class="absolute -right-40 -top-44 -z-10 size-[38rem] rounded-full border border-teal-900/5"></div>
            <div class="absolute -right-20 -top-24 -z-10 size-[28rem] rounded-full border border-teal-900/5"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-14 sm:px-8 sm:pb-24 sm:pt-20 lg:grid-cols-[1.05fr_.95fr] lg:gap-16 lg:px-10 lg:pb-28 lg:pt-24">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white/85 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[.14em] text-teal-800"><span class="size-2 rounded-full bg-emerald-500"></span>Care connected around you</p>
                    <h1 class="mt-6 max-w-2xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">Your health journey, thoughtfully connected.</h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">From your first appointment to tests, prescriptions and follow-up, CityCare brings patients and clinic teams together around clear, coordinated care.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-900/10 transition hover:-translate-y-0.5 hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Continue to your workspace <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-900/10 transition hover:-translate-y-0.5 hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"><i class="fa fa-user-plus" aria-hidden="true"></i>Create a patient account</a>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-teal-900/15 bg-white px-6 py-3.5 text-sm font-semibold text-teal-900 shadow-sm transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Sign in <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                        @endauth
                    </div>
                    <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500"><span class="inline-flex items-center gap-2"><i class="fa fa-map-marker text-teal-700" aria-hidden="true"></i>Kampala, Uganda</span><span class="inline-flex items-center gap-2"><i class="fa fa-money text-teal-700" aria-hidden="true"></i>UGX billing</span><span class="inline-flex items-center gap-2"><i class="fa fa-mobile text-teal-700" aria-hidden="true"></i>Patient portal access</span></div>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-teal-200/50 blur-2xl"></div>
                    <div class="overflow-hidden rounded-[1.75rem] border border-white bg-white shadow-[0_28px_80px_rgba(15,61,62,0.14)]">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3"><x-application-logo class="size-10 rounded-xl bg-teal-700 p-2.5 text-white"/><div><p class="text-xs font-semibold uppercase tracking-wider text-teal-700">The CityCare experience</p><p class="mt-0.5 font-semibold text-slate-900">One connected care journey</p></div></div>
                            <span class="grid size-10 place-items-center rounded-full bg-emerald-50 text-emerald-700"><i class="fa fa-heartbeat" aria-hidden="true"></i></span>
                        </div>
                        <div class="space-y-3 p-5 sm:p-6">
                            <div class="flex gap-3 rounded-2xl bg-teal-50 p-4"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-teal-800 shadow-sm"><i class="fa fa-calendar-check-o" aria-hidden="true"></i></span><div><p class="text-sm font-semibold text-slate-900">Plan your visit</p><p class="mt-1 text-xs leading-5 text-slate-600">Request a consultation or book a laboratory test through your patient account.</p></div></div>
                            <div class="ms-8 h-3 border-s border-dashed border-teal-300"></div>
                            <div class="flex gap-3 rounded-2xl bg-cyan-50 p-4"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-cyan-800 shadow-sm"><i class="fa fa-stethoscope" aria-hidden="true"></i></span><div><p class="text-sm font-semibold text-slate-900">Get coordinated care</p><p class="mt-1 text-xs leading-5 text-slate-600">Clinicians can connect consultations, prescriptions and referred tests to your visit.</p></div></div>
                            <div class="ms-8 h-3 border-s border-dashed border-teal-300"></div>
                            <div class="flex gap-3 rounded-2xl bg-emerald-50 p-4"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-emerald-800 shadow-sm"><i class="fa fa-check" aria-hidden="true"></i></span><div><p class="text-sm font-semibold text-slate-900">Keep track of what comes next</p><p class="mt-1 text-xs leading-5 text-slate-600">Review visit information, balances and payment receipts in one place.</p></div></div>
                        </div>
                        <div class="flex items-center gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-white text-teal-700 shadow-sm"><i class="fa fa-shield" aria-hidden="true"></i></span><p class="text-xs leading-5 text-slate-600">Your portal access is personal. Clinic information is available according to each staff member’s role.</p></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-24">
            <div class="max-w-2xl"><p class="text-xs font-bold uppercase tracking-[.18em] text-teal-700">Services at CityCare</p><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Care and clinic services, working together.</h2><p class="mt-4 leading-7 text-slate-600">A connected workflow helps patients understand their next step and helps the clinic team coordinate the services around each visit.</p></div>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['calendar-check-o','Consultations & appointments','Book a consultation, choose an available clinician and review appointment details in your portal.','bg-teal-50 text-teal-800'],
                    ['flask','Laboratory testing','Book a test directly or follow a clinician referral. View test orders, payment status and completed results.','bg-cyan-50 text-cyan-800'],
                    ['medkit','Pharmacy & prescriptions','Clinicians can prescribe medicines; pharmacists review availability, prepare a separate medicine bill and record dispensing.','bg-emerald-50 text-emerald-800'],
                    ['credit-card','Payments & receipts','Track consultation, laboratory and medicine balances. Pay online by mobile money or card through the integrated PesaPal checkout, or pay at the clinic.','bg-amber-50 text-amber-800'],
                    ['folder-open','Patient records','Access your appointments, visit history, referrals and payment receipts from a personal account.','bg-violet-50 text-violet-800'],
                    ['users','Coordinated clinic team','Role-based workspaces support reception, clinicians, laboratory technicians, pharmacists and cashiers through their part of the visit.','bg-rose-50 text-rose-800'],
                ] as [$icon, $title, $description, $tone])
                    <article class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg sm:p-6">
                        <span class="grid size-12 place-items-center rounded-2xl {{ $tone }}"><i class="fa fa-{{ $icon }} text-lg" aria-hidden="true"></i></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-950">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="care-journey" class="scroll-mt-24 border-y border-slate-200 bg-[#f7faf9]">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[.8fr_1.2fr] lg:px-10 lg:py-24">
                <div class="max-w-xl"><p class="text-xs font-bold uppercase tracking-[.18em] text-teal-700">A clearer care journey</p><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">From booking to follow-through.</h2><p class="mt-4 leading-7 text-slate-600">CityCare connects the practical steps of a clinic visit, so patients can see their bookings and bills while the care team coordinates the work behind them.</p>
                    <a href="{{ route('register') }}" class="mt-7 inline-flex items-center gap-2 font-semibold text-teal-800 hover:text-teal-950">Start with a patient account <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <ol class="grid gap-4 sm:grid-cols-2" aria-label="How the care journey works">
                    @foreach([
                        ['01','Book or request','Choose a consultation or a specific laboratory test. Review your appointment details in the patient portal.'],
                        ['02','Meet your care team','The clinician records the consultation and may prescribe medicines or refer you for laboratory tests.'],
                        ['03','Complete tests and prescriptions','Laboratory staff record results after the related bill is paid. Pharmacists review prescription stock and dispensing.'],
                        ['04','Settle bills and keep receipts','See separate consultation, laboratory and medication charges, pay online or at the clinic, and access payment receipts.'],
                    ] as [$number, $title, $description])
                        <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><span class="text-xs font-bold tracking-[.16em] text-teal-700">{{ $number }} / VISIT</span><h3 class="mt-3 font-semibold text-slate-950">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p></li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section id="care-team" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-[.85fr_1.15fr] lg:items-center">
                <div><p class="text-xs font-bold uppercase tracking-[.18em] text-teal-700">For patients and care teams</p><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">The right tools for every part of care.</h2><p class="mt-4 leading-7 text-slate-600">Each team member works in a focused space, with access guided by their responsibilities. Patients have their own portal for appointments, visits, tests and payments.</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        @auth<a href="{{ route('dashboard') }}" class="rounded-xl bg-teal-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900">Open your workspace</a>@else<a href="{{ route('register') }}" class="rounded-xl bg-teal-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900">Create patient account</a><a href="{{ route('login') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-teal-700 hover:text-teal-900">Staff sign in</a>@endauth
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach([
                        ['user-md','Clinicians','Consultations, diagnosis, treatment plans and referrals.'],
                        ['flask','Laboratory team','Paid test orders, sample collection and result entry.'],
                        ['medkit','Pharmacy team','Medicine stock, prescription review, dispensing and clearance.'],
                        ['money','Reception & cashier','Patient registration, appointments, payments and receipts.'],
                    ] as [$icon, $title, $description])
                        <article class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-800"><i class="fa fa-{{ $icon }}" aria-hidden="true"></i></span><div><h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3><p class="mt-1 text-xs leading-5 text-slate-600">{{ $description }}</p></div></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-[#102d32] text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-7 px-5 py-12 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10 lg:py-14">
                <div class="max-w-2xl"><p class="text-xs font-bold uppercase tracking-[.18em] text-teal-300">Your next step</p><h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Make your CityCare visit easier to follow.</h2><p class="mt-2 text-sm leading-6 text-teal-50/75">Sign in to continue, or create a patient account to get started with the portal.</p></div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    @auth<a href="{{ route('dashboard') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-teal-950 transition hover:bg-teal-50">Go to dashboard</a>@else<a href="{{ route('register') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-teal-950 transition hover:bg-teal-50">Create account</a><a href="{{ route('login') }}" class="rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Sign in</a>@endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10">
            <div class="flex flex-col gap-7 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-3"><x-application-logo class="size-10 rounded-xl bg-teal-700 p-2.5 text-white"/><div><p class="font-bold text-slate-900">CityCare Medical Centre</p><p class="mt-1 text-xs text-slate-500">Connected care in Kampala, Uganda</p></div></div>
                <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-600" aria-label="Policy and service links"><a href="#services" class="hover:text-teal-800">Services</a><a href="{{ route('login') }}" class="hover:text-teal-800">Patient and staff sign in</a><a href="{{ route('legal.terms') }}" class="hover:text-teal-800">Terms of use</a><a href="{{ route('legal.privacy') }}" class="hover:text-teal-800">Privacy policy</a></nav>
            </div>
            <div class="mt-7 flex flex-col gap-2 border-t border-slate-100 pt-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between"><span>© {{ now()->year }} CityCare Medical Centre. All rights reserved.</span><span>Kampala, Uganda <span class="mx-1 text-slate-300">·</span> Africa/Kampala <span class="mx-1 text-slate-300">·</span> UGX</span></div>
        </div>
    </footer>
</body>
</html>
