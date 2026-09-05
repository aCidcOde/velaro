{{--
[Modulo: resources/views/site/catalogo/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Card .prod da grade publica: foto, SKU, nome e ficha curta — sem preco, por regra de escopo.
--}}
@php($chipPreco = $chipPreco ?? false)
<div class="prod">
  <div class="prod__img">
    @if($cartao['imagem'])
      <img src="{{ $cartao['imagem']['src'] }}" alt="{{ $cartao['imagem']['alt'] }}" loading="lazy"
           style="width:100%;height:100%;object-fit:contain">
    @else
      <x-velaro.ring :alt="$cartao['nome']" style="width:100%;height:100%;object-fit:contain" />
    @endif
  </div>
  <small class="prod__sku">{{ $cartao['sku'] }}</small>
  <h4>{{ $cartao['nome'] }}</h4>
  {{-- Segunda linha literal do prototipo (§5): largura + "Acabamento polido" fixo em todos os doze cards. --}}
  <small class="prod__spec">{{ $cartao['especificacao'] }}@if($cartao['largura'])<br>{{ $cartao['largura'] }} | Acabamento polido @endif</small>
  <span class="prod__price"></span>
  @if($chipPreco)
    <div class="row" style="gap:5px"><span class="chip chip--neutral chip--flat">Preço após cadastro</span></div>
  @endif
  <div class="prod__acts">
    <a class="btn btn--secondary btn--sm" href="{{ route('site.produto', $cartao['slug']) }}">Ver detalhes</a>
  </div>
</div>
