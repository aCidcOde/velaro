{{--
[Modulo: resources/views/portal/catalogo/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Card .prod da grade do lojista: foto, SKU, nome, CUSTO B2B, chip de disponibilidade e as duas acoes.
--}}
<div class="prod">
  <div class="prod__img">
    @if($cartao['imagem'])
      <img src="{{ $cartao['imagem']['src'] }}" alt="{{ $cartao['imagem']['alt'] }}" loading="lazy"
           style="width:100%;height:100%;object-fit:contain">
    @else
      <x-velaro.ring :alt="$cartao['nome']" style="width:100%;height:100%;object-fit:contain" />
    @endif
  </div>

  <small class="prod__sku">SKU: {{ $cartao['sku'] }}</small>
  <h4>{{ $cartao['nome'] }}</h4>
  <small class="prod__spec">{{ $cartao['especificacao'] }}</small>

  {{-- O custo Velaro. É esta a tela em que ele aparece — e só ela. --}}
  <span class="prod__price">{{ $cartao['custo'] }}</span>

  <div class="row" style="gap:5px">
    <span class="chip {{ $cartao['disponibilidade']['chip'] }} chip--flat">{{ $cartao['disponibilidade']['rotulo'] }}</span>
    @if($cartao['novo'])
      <span class="chip chip--brand chip--flat">NOVO</span>
    @endif
  </div>

  <div class="prod__acts">
    <a class="btn btn--secondary btn--sm" href="{{ $cartao['urlFicha'] }}#ficha">Ver detalhes</a>
    <a class="btn btn--primary btn--sm" href="{{ $cartao['urlPedido'] }}">+ Adicionar</a>
  </div>
</div>
