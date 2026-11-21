@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @if($active) aria-current="page" @endif @class([
    'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
    'bg-teal-800 text-white shadow-sm' => $active,
    'text-slate-300 hover:bg-white/10 hover:text-white' => !$active,
])>
    <i class="fa fa-{{ $icon }} w-5 text-center text-base" aria-hidden="true"></i>
    <span>{{ $slot }}</span>
</a>
