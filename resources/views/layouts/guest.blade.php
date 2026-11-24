<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f3d3e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CityCare">
    <title>{{ config('app.name', 'CityCare Medical Centre') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/citycare-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(error => console.error('CityCare service worker registration failed.', error)));
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f2f8f7] font-sans text-slate-800 antialiased">
    <div id="app-navigation-progress" class="app-navigation-progress" hidden role="status" aria-live="polite"><span class="app-navigation-progress__bar"></span><span class="sr-only">Loading the requested page</span></div>
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(25rem,0.85fr)]">
        <aside class="relative hidden overflow-hidden bg-[#102d32] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute -right-32 -top-20 size-[30rem] rounded-full border border-white/10"></div><div class="absolute -right-20 -top-8 size-[26rem] rounded-full border border-white/10"></div>
            <a href="{{ url('/') }}" class="relative flex items-center gap-3"><x-application-logo class="size-12 rounded-2xl bg-teal-600 p-2.5 text-white"/><span><span class="block text-xl font-bold">CityCare</span><span class="block text-xs text-teal-100/70">Medical Centre</span></span></a>
            <div class="relative max-w-xl py-14">
                <span class="grid size-14 place-items-center rounded-2xl bg-teal-400/15 text-teal-200"><i class="fa fa-heartbeat text-2xl" aria-hidden="true"></i></span>
                <h1 class="mt-7 text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">Care teams, connected to what matters.</h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-teal-50/75">A secure workspace for CityCare appointments, patient histories, treatment and clinic operations.</p>
                <div class="mt-9 grid gap-4 text-sm text-teal-50/85">
                    <p class="flex items-center gap-3"><i class="fa fa-calendar-check-o w-5 text-teal-300" aria-hidden="true"></i>Appointment schedules that stay in sync</p>
                    <p class="flex items-center gap-3"><i class="fa fa-user-md w-5 text-teal-300" aria-hidden="true"></i>Patient care records in one place</p>
                    <p class="flex items-center gap-3"><i class="fa fa-shield w-5 text-teal-300" aria-hidden="true"></i>Private access for every clinic role</p>
                </div>
            </div>
            <p class="relative text-xs text-teal-100/60">© {{ now()->year }} CityCare Medical Centre · Kampala, Uganda</p>
        </aside>

        <main class="flex min-h-screen flex-col px-5 py-6 sm:px-8 lg:px-12">
            <header class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 lg:hidden"><x-application-logo class="size-10 rounded-xl bg-teal-700 p-2 text-white"/><span class="font-bold text-slate-900">CityCare</span></a>
                <a href="{{ url('/') }}" class="text-sm font-medium text-slate-600 transition hover:text-teal-800"><i class="fa fa-arrow-left me-2" aria-hidden="true"></i>Home</a>
            </header>
            <div class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-md">
                    {{ $slot }}
                    <p class="mt-8 text-center text-xs text-slate-500"><i class="fa fa-map-marker me-1 text-teal-700" aria-hidden="true"></i>Kampala, Uganda <span class="mx-2 text-slate-300">·</span> Secure clinic access</p>
                </div>
            </div>
            <footer class="text-center text-xs text-slate-400">Africa/Kampala · UGX</footer>
        </main>
    </div>
    @if(session('status'))<script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast(@json(session('status')), 'success'));</script>@endif
    @if(session('error'))<script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast(@json(session('error')), 'error'));</script>@endif
    @if($errors->any())<script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast(@json($errors->first()), 'error'));</script>@endif
</body>
</html>
