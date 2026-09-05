{{-- Lista de links da sidebar / menu mobile. $items = [[icone, rótulo, nome-da-rota], ...]

     Itens em `$bloqueados` continuam visíveis, mas não são links: o lojista em
     pré-cadastro precisa ver a jornada que o espera, e sumir com metade do menu
     esconderia dele o produto que acabou de contratar. Como o item não abre nada,
     ele deixa de ser <a> — um link que não navega é pior que um rótulo. --}}
@props(['items', 'bloqueados' => [], 'motivo' => null])
@foreach($items as [$icone, $rotulo, $rota])
@if(in_array($rota, $bloqueados, true))
<span class="nav__locked" aria-disabled="true" @if($motivo) title="{{ $motivo }}" @endif><x-velaro.icon :name="$icone" /> {{ $rotulo }} <x-velaro.icon name="lock" class="ic nav__lock" /></span>
@else
<a href="{{ route($rota) }}" @class(['is-active' => request()->routeIs($rota) || request()->routeIs($rota.'.*')])><x-velaro.icon :name="$icone" /> {{ $rotulo }}</a>
@endif
@endforeach
@if($bloqueados !== [] && $motivo)
<p class="nav__note">{{ $motivo }}</p>
@endif
