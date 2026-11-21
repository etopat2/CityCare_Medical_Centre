<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f3d3e">
    <title>{{ trim($__env->yieldContent('title', config('app.name', 'CityCare'))) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

        <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-white/10 bg-[#102d32] transition-transform duration-200 lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" aria-label="Main navigation">
            <a href="{{ route('dashboard') }}" class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <x-application-logo class="size-11 rounded-2xl shadow-lg shadow-teal-950/25" />
                <span><span class="block text-lg font-bold tracking-tight text-white">CityCare</span><span class="block text-xs text-teal-100/70">Medical Centre</span></span>
            </a>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6" aria-label="Clinic sections">
                <p class="px-3 pb-2 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-400">Workspace</p>
                <x-sidebar-link :href="route('dashboard')" icon="th-large" :active="request()->routeIs('dashboard*')">Dashboard</x-sidebar-link>
                @role('doctor')<x-sidebar-link :href="route('doctor-schedule.own')" icon="calendar" :active="request()->routeIs('doctor-schedule.own')">My schedule</x-sidebar-link>@endrole
                @role('patient')
                    <x-sidebar-link :href="route('portal.profile')" icon="user-circle" :active="request()->routeIs('portal.profile')">My profile</x-sidebar-link>
                    <x-sidebar-link :href="route('portal.appointments')" icon="calendar-check-o" :active="request()->routeIs('portal.appointments')">My appointments</x-sidebar-link>
                    <x-sidebar-link :href="route('portal.visits')" icon="heartbeat" :active="request()->routeIs('portal.visits')">My visit history</x-sidebar-link>
                    <x-sidebar-link :href="route('portal.payments')" icon="credit-card" :active="request()->routeIs('portal.payments')">My payments</x-sidebar-link>
                @endrole

                @canany(['patients.view', 'patients.create'])<x-sidebar-link :href="route('patients.index')" icon="users" :active="request()->routeIs('patients.*')">Patients</x-sidebar-link>@endcanany
                @canany(['appointments.view', 'appointments.create'])<x-sidebar-link :href="route('appointments.index')" icon="calendar-check-o" :active="request()->routeIs('appointments.*')">Appointments</x-sidebar-link>@endcanany
                @canany(['doctors.view', 'doctors.manage'])<x-sidebar-link :href="route('doctors.index')" icon="user-md" :active="request()->routeIs('doctors.*')">Doctors</x-sidebar-link>@endcanany
                @can('departments.manage')<x-sidebar-link :href="route('departments.index')" icon="hospital-o" :active="request()->routeIs('departments.*')">Departments</x-sidebar-link>@endcan
                @can('schedules.manage')<x-sidebar-link :href="route('doctor-schedules.index')" icon="clock-o" :active="request()->routeIs('doctor-schedules.*')">Doctor schedules</x-sidebar-link>@endcan
                @canany(['consultations.view', 'consultations.create'])<x-sidebar-link :href="route('consultations.index')" icon="stethoscope" :active="request()->routeIs('consultations.*')">Consultations</x-sidebar-link>@endcanany
                @canany(['payments.view', 'payments.create'])<x-sidebar-link :href="route('payments.index')" icon="money" :active="request()->routeIs('payments.*')">Payments</x-sidebar-link>@endcanany

                @canany(['reports.view', 'reports.daily.view', 'reports.payments.view'])
                    <p class="px-3 pb-2 pt-7 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-400">Insights</p>
                    <x-sidebar-link :href="route('reports.index')" icon="bar-chart" :active="request()->routeIs('reports.*')">Reports & exports</x-sidebar-link>
                @endcanany

                @canany(['users.manage', 'access.manage', 'audit.view', 'system-logs.view'])
                    <p class="px-3 pb-2 pt-7 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-400">Administration</p>
                    @can('users.manage')<x-sidebar-link :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')">System users</x-sidebar-link>@endcan
                    @can('access.manage')<x-sidebar-link :href="route('access.index')" icon="key" :active="request()->routeIs('access.*')">Roles & permissions</x-sidebar-link>@endcan
                    @can('audit.view')<x-sidebar-link :href="route('audit.index')" icon="history" :active="request()->routeIs('audit.*')">Audit trail</x-sidebar-link>@endcan
                    @can('system-logs.view')<x-sidebar-link :href="route('system-logs.index')" icon="file-text-o" :active="request()->routeIs('system-logs.*')">Application logs</x-sidebar-link>@endcan
                @endcanany
            </nav>

            <div class="border-t border-white/10 px-4 py-4">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-200 hover:bg-white/10"><img src="{{ auth()->user()->avatarUrl() }}" alt="" class="size-9 rounded-full bg-teal-100 object-cover"><span class="min-w-0 flex-1"><span class="block truncate font-semibold">{{ auth()->user()->name }}</span><span class="block text-xs capitalize text-slate-400">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span></span><i class="fa fa-cog text-slate-400" aria-hidden="true"></i></a>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">@csrf<button class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-300 hover:bg-white/10 hover:text-white"><i class="fa fa-sign-out me-3 w-5 text-center" aria-hidden="true"></i>Sign out</button></form>
            </div>
        </aside>

        <div class="min-h-screen lg:ps-72">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
                <div class="flex min-h-[4.5rem] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation"><i class="fa fa-bars" aria-hidden="true"></i></button>
                        <div class="hidden sm:block"><p class="text-xs font-medium uppercase tracking-wider text-teal-700">CityCare Medical Centre</p><p class="mt-0.5 text-sm text-slate-500"><i class="fa fa-calendar-o me-1.5" aria-hidden="true"></i>{{ now()->format('l, j F Y') }}</p></div>
                    </div>
                    <div x-data="{ profileMenuOpen: false }" @keydown.escape.window="profileMenuOpen = false" class="relative">
                        <button type="button" @click="profileMenuOpen = !profileMenuOpen" :aria-expanded="profileMenuOpen.toString()" aria-haspopup="true" class="flex max-w-[15rem] items-center gap-2.5 rounded-xl px-2 py-1.5 text-left transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="size-10 shrink-0 rounded-full bg-teal-100 object-cover ring-2 ring-white">
                            <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span><span class="block truncate text-xs capitalize text-slate-500">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span></span>
                            <i class="fa fa-chevron-down ms-1 text-xs text-slate-500" aria-hidden="true"></i>
                        </button>
                        <div x-cloak x-show="profileMenuOpen" x-transition.origin.top.right @click.outside="profileMenuOpen = false" class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-2 shadow-xl">
                            <div class="border-b border-slate-100 px-4 py-2.5"><p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p></div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-teal-50 hover:text-teal-900"><i class="fa fa-user-circle w-4 text-center" aria-hidden="true"></i>Profile</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-rose-700 hover:bg-rose-50"><i class="fa fa-sign-out w-4 text-center" aria-hidden="true"></i>Sign out</button></form>
                        </div>
                    </div>
                </div>
            </header>

            @isset($header)
                <div class="border-b border-slate-200 bg-white px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
            @endisset

            <main class="px-4 sm:px-6 lg:px-8" id="main-content">
                {{ $slot }}
            </main>

            <footer class="mt-8 border-t border-slate-200 bg-white px-4 py-5 text-xs text-slate-500 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><span>© {{ now()->year }} CityCare Medical Centre</span><span><i class="fa fa-map-marker me-1 text-teal-700" aria-hidden="true"></i>Kampala, Uganda <span class="mx-2 text-slate-300">|</span> Africa/Kampala · UGX</span></div>
            </footer>
        </div>
    </div>

    {{-- Flash and validation summaries use Toastify; field messages remain beside their inputs. --}}
    @if(session('success'))<script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast(@json(session('success')), 'success'));</script>@endif
    @if(session('error'))<script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast(@json(session('error')), 'error'));</script>@endif
    @if($errors->any())<script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast(@json($errors->first()), 'error'));</script>@endif
</body>
</html>
