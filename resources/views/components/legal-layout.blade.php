@props(['title', 'description'])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f3d3e">
    <meta name="description" content="{{ $description }}">
    <title>{{ $title }} | {{ config('app.name', 'CityCare Medical Centre') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7faf9] font-sans text-slate-800 antialiased">
    <a href="#policy-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-teal-900 focus:shadow-xl">Skip to policy content</a>
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8 lg:px-10">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="CityCare Medical Centre home">
                <x-application-logo class="size-10 rounded-xl bg-teal-700 p-2.5 text-white" />
                <span><span class="block font-bold tracking-tight text-slate-950">CityCare</span><span class="block text-xs text-slate-500">Medical Centre · Kampala</span></span>
            </a>
            <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium text-slate-600" aria-label="Public navigation">
                <a href="{{ url('/') }}#services" class="hover:text-teal-800">Services</a>
                <a href="{{ route('legal.terms') }}" @class(['text-teal-800' => request()->routeIs('legal.terms')])>Terms</a>
                <a href="{{ route('legal.privacy') }}" @class(['text-teal-800' => request()->routeIs('legal.privacy')])>Privacy</a>
                <a href="{{ route('login') }}" class="rounded-lg bg-teal-800 px-4 py-2 text-white transition hover:bg-teal-900">Sign in</a>
            </nav>
        </div>
    </header>
    <main id="policy-content" class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14 lg:px-10">
        <div class="mx-auto max-w-4xl">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:text-teal-950"><i class="fa fa-arrow-left" aria-hidden="true"></i>Back to CityCare</a>
            <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-teal-700">CityCare Medical Centre · Kampala, Uganda</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
                <p class="mt-3 max-w-3xl leading-7 text-slate-600">{{ $description }}</p>
                <p class="mt-4 text-xs text-slate-500">Last updated: 23 November 2026</p>
                <div class="prose-citycare mt-9 space-y-8 text-sm leading-7 text-slate-700">
                    {{ $slot }}
                </div>
            </div>
            <footer class="flex flex-wrap items-center justify-between gap-3 px-1 py-6 text-xs text-slate-500">
                <span>© {{ now()->year }} CityCare Medical Centre · Kampala, Uganda</span>
                <div class="flex gap-4"><a href="{{ route('legal.terms') }}" class="hover:text-teal-800">Terms of use</a><a href="{{ route('legal.privacy') }}" class="hover:text-teal-800">Privacy policy</a></div>
            </footer>
        </div>
    </main>
</body>
</html>
