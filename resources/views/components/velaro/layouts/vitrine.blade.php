{{-- Vitrine white label de um revendedor. Sem marca Velaro em lugar nenhum:
     as cores vêm de reseller_stores. $store é obrigatório.

     `abas` são as categorias que o lojista escolheu exibir (reseller_store_categories),
     na forma [['rotulo' => ..., 'slug' => ...], ...] — a primeira sempre é
     "Todos os produtos", com slug nulo. Sem a lista, o menu cai na navegação
     padrão do protótipo. --}}
@props(['store', 'title' => null, 'ativo' => null, 'itensNoCarrinho' => 0, 'single' => false, 'abas' => null])
@php($r = $store->reseller)
@php($menu = $abas ?? [
  ['rotulo' => 'Todos os produtos', 'slug' => null],
  ['rotulo' => 'Alianças', 'slug' => 'aliancas'],
  ['rotulo' => 'Solitários', 'slug' => 'solitarios'],
  ['rotulo' => 'Acessórios', 'slug' => 'acessorios'],
])
{{-- A marca do documento é a da LOJA, e o ícone da aba também: o <title> e o
     favicon da Velaro seriam vazamento de marca perante o consumidor final
     (regra 1 das telas 2.9 e 2.10). Sem logo gravada a aba fica sem ícone —
     melhor nenhum do que o do fornecedor. --}}
<x-velaro.layouts.base
  :title="$title"
  :marca="$store->name ?? $r?->trade_name ?? 'Loja'"
  :favicons="false">
<x-slot:head>
@if($store->logo_path)
<link rel="icon" href="{{ asset('storage/'.$store->logo_path) }}">
@endif
<meta name="theme-color" content="{{ $store->color_background ?? '#ffffff' }}">
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
      <a class="shop__logo" href="{{ route('vitrine.index', $store) }}">
        @if($store->logo_path)
          <img src="{{ asset('storage/'.$store->logo_path) }}" alt="{{ $store->name ?? $r?->trade_name ?? 'Loja' }}" style="max-height:38px;width:auto">
        @else
          <strong>{{ mb_strtoupper($store->name ?? $r?->trade_name ?? 'LOJA') }}</strong><small>ALIANÇAS</small>
        @endif
      </a>
      <span class="shop__tabs">
        @foreach($menu as $aba)
          <a href="{{ route('vitrine.index', [$store, 'categoria' => $aba['slug']]) }}" @class(['is-active' => ($ativo ?? null) === $aba['slug']])>{{ $aba['rotulo'] }}</a>
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
