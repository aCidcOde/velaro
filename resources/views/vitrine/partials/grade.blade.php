{{--
[Modulo: resources/views/vitrine/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Grade de pecas da loja com paginacao — a mesma na vitrine e ao lado do carrinho do balcao.
--}}
<section class="shop__section" id="produtos">
  <h3>{{ $titulo }}</h3>

  @if($cartoes === [])
    <p class="pickup">
      <x-velaro.icon name="info" class="ic" />
      <span><strong style="color:var(--shop-text)">Nenhuma peça nesta seção por enquanto.</strong>
        <a href="{{ route('vitrine.index', $loja) }}" style="color:var(--shop-primary)">Ver todos os produtos</a>.</span>
    </p>
  @else
    <div class="prods">
      @foreach($cartoes as $cartao)
        @include('vitrine.partials.card', ['cartao' => $cartao])
      @endforeach
    </div>

    @include('vitrine.partials.paginacao')
  @endif
</section>
