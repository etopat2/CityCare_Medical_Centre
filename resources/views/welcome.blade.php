<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f0fdfa">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5faf9] font-sans text-slate-800 antialiased">
    <main class="relative isolate min-h-screen overflow-hidden">
        <div class="absolute inset-x-0 top-0 -z-10 h-[34rem] bg-gradient-to-br from-teal-100 via-cyan-50 to-[#f5faf9]"></div>
        <div class="mx-auto max-w-7xl px-5 py-6 sm:px-8 lg:px-10">
            <header class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="CityCare home">
                    <span class="grid size-11 place-items-center rounded-2xl bg-teal-700 text-white shadow-lg shadow-teal-900/15"><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 4v16M4 12h16" stroke-linecap="round"/></svg></span>
                    <span><span class="block text-base font-bold tracking-tight text-slate-900">CityCare</span><span class="block text-xs text-slate-500">Medical Centre</span></span>
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl border border-teal-900/10 bg-white/70 px-4 py-2.5 text-sm font-semibold text-teal-900 shadow-sm transition hover:bg-white">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl bg-teal-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Staff and patient login</a>
                @endauth
            </header>

            <section class="grid items-center gap-12 pb-20 pt-16 sm:pt-24 lg:grid-cols-[1.05fr_.95fr] lg:gap-16 lg:pb-28 lg:pt-28">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white/70 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[.14em] text-teal-800"><span class="size-2 rounded-full bg-teal-500"></span>Care, connected</p>
                    <h1 class="mt-6 max-w-2xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">Better care starts with a clearer picture.</h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">A single, secure place for CityCare patients and clinic teams to manage appointments, treatment records and payments.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        @auth<a href="{{ route('dashboard') }}" class="rounded-xl bg-teal-800 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-900">Open your dashboard</a>@else<a href="{{ route('login') }}" class="rounded-xl bg-teal-800 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-900">Sign in to CityCare <span aria-hidden="true">→</span></a>@endauth
                        <span class="text-sm text-slate-500">Serving our community in Kampala</span>
                    </div>
                </div>
                <div class="relative mx-auto w-full max-w-xl">
                    <div class="absolute -inset-6 -z-10 rounded-[2.5rem] bg-teal-200/40 blur-2xl"></div>
                    <div class="rounded-[1.75rem] border border-white/90 bg-white/90 p-5 shadow-2xl shadow-teal-950/10 backdrop-blur sm:p-7">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                            <div><p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Clinic overview</p><h2 class="mt-1 text-xl font-bold text-slate-900">Care in good hands</h2></div>
                            <div class="grid size-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.8 8.6c0 5-8.8 10-8.8 10s-8.8-5-8.8-10a4.5 4.5 0 0 1 8.8-1.2 4.5 4.5 0 0 1 8.8 1.2Z"/><path d="M8 11h2l1-2 2 5 1-3h2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </div>
                        <div class="grid gap-3 py-5 sm:grid-cols-2">
                            @foreach([['Appointments','Simple booking with live doctor availability'],['Patient records','History ready for the care team'],['Treatment','Consultations and follow-up in one place'],['Payments','Clear balances and printable receipts']] as [$label,$description])
                                <div class="rounded-2xl bg-slate-50 p-4"><span class="text-sm font-semibold text-slate-900">{{ $label }}</span><p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $description }}</p></div>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-teal-50 px-4 py-3.5"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-white text-teal-700"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="text-sm leading-5 text-teal-950">Your care information is protected and available to the right people.</p></div>
                    </div>
                </div>
            </section>

            <footer class="flex flex-col gap-2 border-t border-teal-900/10 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between"><span>© {{ now()->year }} CityCare Medical Centre</span><span>Africa/Kampala · UGX</span></footer>
        </div>
    </main>
</body>
</html>
