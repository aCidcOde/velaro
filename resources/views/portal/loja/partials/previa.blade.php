{{--
[Modulo: resources/views/portal/loja/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Previa da vitrine da tela 2.6: pintada pelas cores gravadas e com o preco B2C resolvido.
--}}
<div class="drawer" style="{{ $paleta }}">
  <header class="drawer__head">
    <div>
      <h2 class="title">Pré-visualização da loja</h2>
      <p class="drawer__sub">Assim o cliente verá sua vitrine, com a identidade da sua loja.</p>
    </div>
    <span class="row" style="gap:4px;color:var(--ink-muted)"><x-velaro.icon name="eye" /></span>
  </header>

  <div class="drawer__body">
    {{-- Os `.storeprev*` do design system nascem com a paleta do protótipo fixa.
         A prévia precisa mostrar a loja DESTE lojista, então cada elemento
         pintável recebe a cor gravada — os mesmos quatro valores que a vitrine
         em /loja/{slug} usa. --}}
    <div class="storeprev" style="background:var(--shop-bg);color:var(--shop-text)">
      <div class="storeprev__bar" style="background:var(--shop-text)">
        <strong>{{ mb_strtoupper($loja->name ?? 'Sua loja') }}</strong>
        <span><x-velaro.icon name="search" /> <x-velaro.icon name="cart" /></span>
      </div>

      <div class="storeprev__tabs">
        <b style="background:var(--shop-primary)">Todos os produtos</b>
        <span>Alianças</span><span>Solitários</span><span>Acessórios</span>
      </div>

      <div class="storeprev__banner"
           style="background:linear-gradient(110deg, color-mix(in srgb, var(--shop-primary) 70%, #000), var(--shop-primary))">
        <strong>{{ mb_strtoupper($loja->name ?? 'Sua loja') }}</strong>
        <span>{{ $loja->slogan ?? 'Símbolo de amor. Promessa para a vida toda.' }}</span>
        <em style="background:var(--shop-secondary)">Conheça nossas alianças</em>
      </div>

      <div class="storeprev__grid">
        <span class="eyebrow" style="grid-column:1/-1">Destaques</span>
        @foreach($previa as $item)
          <div class="prevprod">
            <span class="thumb"><x-velaro.ring thumb :alt="$item['nome']" style="width:100%;height:auto" /></span>
            <strong>{{ $item['nome'] }}</strong>
            <small>{{ $item['material'] }}</small>
            @if($item['preco'])
              <span class="prevprod__price num" style="color:var(--shop-primary)">{{ $item['preco'] }}</span>
            @else
              <span class="prevprod__price num" style="color:var(--shop-primary)">Consulte</span>
            @endif
            <span class="chip chip--neutral chip--flat">{{ $item['chip'] }}</span>
          </div>
        @endforeach
      </div>
    </div>

    <p class="notice notice--gold"><x-velaro.icon name="info" /><span>Esta prévia é pintada pelos campos acima.
      @if($loja->hide_supplier_brand)
        A vitrine <strong>não exibe marca Velaro</strong> em nenhum ponto.
      @else
        Ligue <strong>Ocultar marca do fornecedor</strong> para remover qualquer menção à Velaro.
      @endif
    </span></p>

    <div class="datarow">
      <span class="datarow__k">Situação da vitrine</span>
      <span class="datarow__v">
        @if($loja->is_active)
          <span class="chip chip--ok chip--flat">Publicada</span>
        @else
          <span class="chip chip--neutral chip--flat">Não publicada</span>
        @endif
      </span>
    </div>
    @if($loja->published_at)
      <div class="datarow">
        <span class="datarow__k">Publicada em</span>
        <span class="datarow__v num">{{ $loja->published_at->format('d/m/Y H:i') }}</span>
      </div>
    @endif
  </div>
</div>
