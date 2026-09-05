{{--
[Modulo: resources/views/portal/catalogo]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.2 — catalogo revendedor: KPIs, filtros, grade com o custo B2B e o painel lateral da peca.
--}}
<x-velaro.layouts.portal title="Catálogo Revendedor" titulo="Catálogo Revendedor">

  <div class="page-head">
    <div>
      <h1 class="display-md">Catálogo Revendedor</h1>
      <p class="lede">Catálogo com custo exclusivo para lojistas, disponibilidade e ferramentas para criação de pedidos.</p>
    </div>
  </div>

  <section class="grid g4" aria-label="Resumo do catálogo">
    @foreach($indicadores as $indicador)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon {{ $indicador['variante'] }}"><x-velaro.icon :name="$indicador['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $indicador['rotulo'] }}</div>
            <div class="kpi__value">{{ number_format($indicador['valor'], 0, ',', '.') }}</div>
            @if($indicador['url'])
              <a class="kpi__delta" href="{{ $indicador['url'] }}">{{ $indicador['nota'] }}</a>
            @else
              <div class="kpi__delta kpi__delta--flat">{{ $indicador['nota'] }}</div>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <div class="split split--wide">
    <div class="stack">
      @include('portal.catalogo.partials.filtros')

      @if($cartoes === [])
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span><strong>Nenhuma peça encontrada com esses filtros.</strong>
            <a href="{{ route('portal.catalogo') }}">Ver o catálogo completo</a>.</span>
        </p>
      @else
        <div class="prods">
          @foreach($cartoes as $cartao)
            @include('portal.catalogo.partials.card', ['cartao' => $cartao])
          @endforeach
        </div>
        @include('portal.catalogo.partials.paginacao')
      @endif

      {{-- Regra 1 da tela 2.2: o custo é interno e não chega à vitrine do consumidor. --}}
      <p class="notice notice--gold">
        <x-velaro.icon name="lock" />
        <span>Os preços exibidos são <strong>exclusivos para revendedores</strong> e não devem ser compartilhados com clientes finais.</span>
      </p>
    </div>

    @include('portal.catalogo.partials.drawer')
  </div>
</x-velaro.layouts.portal>
