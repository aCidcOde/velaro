<x-layouts.backend :title="$title ?? null">
    @if (isset($slot))
        {{ $slot }}
    @else
        @yield('content')
    @endif
</x-layouts.backend>
