{{-- Lista de links da sidebar / menu mobile. $items = [[icone, rótulo, nome-da-rota], ...] --}}
@props(['items'])
@foreach($items as [$icone, $rotulo, $rota])
<a href="{{ route($rota) }}" @class(['is-active' => request()->routeIs($rota) || request()->routeIs($rota.'.*')])><x-velaro.icon :name="$icone" /> {{ $rotulo }}</a>
@endforeach
