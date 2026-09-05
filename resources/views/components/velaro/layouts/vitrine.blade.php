{{-- Vitrine white label de um revendedor. Sem marca Velaro em lugar nenhum:
     as cores vêm de reseller_stores. $store é obrigatório. --}}
@props(['store', 'title' => null, 'ativo' => null, 'itensNoCarrinho' => 0, 'single' => false])
@php($r = $store->reseller)
<x-velaro.layouts.base :title="$title ?? $store->name ?? $r?->trade_name">
<x-slot:head>
<style>
  .shop{
    --shop-primary:{{ $store->color_primary ?? '#800020' }};
    --shop-secondary:{{ $store->color_secondary ?? '#b8860b' }};
    --shop-bg:{{ $store->color_background ?? '#ffffff' }};
    --shop-text:{{ $store->color_text ?? '#1a1a1a' }};
  }
  body{margin:0;background:var(--shop-bg)}
</style>
</x-slot:head>
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
<div class="shop @if($single) shop--single @endif">
  <div class="shop__main" id="conteudo">
    <nav class="shop__nav">
      <a class="shop__logo" href="{{ route('vitrine.index', $store) }}"><strong>{{ mb_strtoupper($store->name ?? $r?->trade_name ?? 'LOJA') }}</strong><small>ALIANÇAS</small></a>
      <span class="shop__tabs">
        @foreach(['Todos os produtos' => null, 'Alianças' => 'aliancas', 'Solitários' => 'solitarios', 'Acessórios' => 'acessorios'] as $rotulo => $cat)
          <a href="{{ route('vitrine.index', [$store, 'categoria' => $cat]) }}" @class(['is-active' => ($ativo ?? null) === $cat])>{{ $rotulo }}</a>
        @endforeach
      </span>
      <span class="shop__navicons"><x-velaro.icon name="search" />
        <a class="shop__bag" href="{{ route('vitrine.carrinho', $store) }}"><x-velaro.icon name="bag" /> Sacola <b>{{ $itensNoCarrinho }}</b></a></span>
    </nav>
    {{ $slot }}
  </div>
  {{ $aside ?? '' }}
</div>
</x-velaro.layouts.base>
