{{--
[Modulo: resources/views/site/catalogo]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.3 — detalhe do modelo: galeria, ficha tecnica, opcoes de fabricacao e condicoes comerciais, sem preco.
--}}
<x-velaro.layouts.site :title="$produto->name">
  <x-slot:hero>
    <section class="hero"><div class="hero__inner">
      <div>
        <span class="badge-b2b"><x-velaro.icon name="users" /> {{ $trilha }}</span>
        <h1>{{ $produto->name }}</h1>
        <p class="hero-sub">{{ $referencia }}</p>
        @if($produto->description)
          <p class="lede">{{ $produto->description }}</p>
        @endif
        <div class="hero__ctas">
          <a class="btn btn--gold" href="{{ route('site.cadastro') }}"><x-velaro.icon name="store" /> Quero ser revendedor</a>
          <a class="btn btn--ghost-gold" href="{{ route('site.catalogo') }}"><x-velaro.icon name="book" /> ← Voltar ao catálogo</a>
        </div>
        <p class="hero__note"><x-velaro.icon name="lock" /> Catálogo público sem preço: a condição comercial é exclusiva de lojista aprovado.</p>
      </div>
      <div class="hero__art"><div style="width:300px">
        @if($capa)
          <img src="{{ asset($capa->path) }}" alt="{{ $capa->alt ?? $produto->name }}">
        @else
          <x-velaro.ring :alt="$produto->name" />
        @endif
      </div></div>
    </div></section>
  </x-slot:hero>

  <section class="band-light"><div class="band__inner">
    <div class="split split--wide" style="--gcols:minmax(0,1.1fr) minmax(0,1fr)">
      <div class="stack">
        <div class="card"><div class="pdpgal">
          <div class="pdpgal__main">
            @if($capa)
              <img src="{{ asset($capa->path) }}" alt="{{ $capa->alt ?? $produto->name }}">
            @else
              <x-velaro.ring :alt="$produto->name" />
            @endif
          </div>
          @if($imagens->count() > 1)
            <div class="pdpthumbs">
              @foreach($imagens as $indice => $imagem)
                <span class="pdpthumb @if($indice === 0) is-on @endif" title="{{ $imagem->alt ?? $produto->name }}">
                  <img src="{{ asset($imagem->path) }}" alt="{{ $imagem->alt ?? $produto->name }}" loading="lazy">
                </span>
              @endforeach
            </div>
          @endif
          <small class="fhint">Imagens ilustrativas do feitio. O acabamento final é confirmado no pedido do lojista.</small>
        </div></div>

        <div class="card">
          <div class="card__head"><h2 class="title">Ficha técnica</h2></div>
          @foreach($ficha as $linha)
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon :name="$linha['icone']" /> {{ $linha['rotulo'] }}</span>
              <span class="datarow__v">{{ $linha['valor'] }}</span>
            </div>
          @endforeach
        </div>
      </div>

      <div class="stack">
        <div class="pricelock">
          <span class="pricelock__tag"><x-velaro.icon name="lock" /> Preço exclusivo para lojistas</span>
          <h2 class="display-sm">Condição comercial liberada após a aprovação do cadastro.</h2>
          <p>A Velaro é fábrica e vende <strong>somente para lojistas com CNPJ</strong>. Preço de fábrica, desconto por
             volume e prazo de pagamento aparecem no Portal do Lojista assim que seu cadastro for aprovado.</p>
          <a class="btn btn--gold" href="{{ route('site.cadastro') }}"><x-velaro.icon name="store" /> Quero ser revendedor</a>
          <a class="btn btn--ghost-gold" href="#condicoes"><x-velaro.icon name="doc" /> Ver condições comerciais</a>
          <small class="pricelock__note">O consumidor final não compra na Velaro: ele compra na loja do revendedor.</small>
        </div>

        @if($opcoes !== [])
          <div class="card">
            <div class="card__head"><h2 class="title">Opções de fabricação</h2></div>
            @foreach($opcoes as $secao)
              <div class="optset">
                <span class="optset__lab">{{ $secao['rotulo'] }}</span>
                <div class="optrow">
                  @foreach($secao['itens'] as $item)
                    <span class="optchip @if($item['ativo']) is-on @endif">{{ $item['texto'] }}</span>
                  @endforeach
                </div>
                @if($secao['nota'])
                  <small class="fhint">{{ $secao['nota'] }}</small>
                @endif
              </div>
            @endforeach
          </div>
        @endif

        @if($produto->allows_engraving)
          <div class="card">
            <div class="card__head"><h2 class="title">Gravação personalizada</h2></div>
            <p class="lede" style="font-size:var(--text-sm)">Gravação interna de nome e data, feita na fábrica antes do envio. O limite de caracteres e o valor por peça são parametrizáveis e aparecem no Portal do Lojista.</p>
            <ul class="cklist" style="margin-top:var(--space-3)">
              @if($produto->engraving_max_chars)
                <li class="ck--ok"><x-velaro.icon name="check" /><span>Até {{ $produto->engraving_max_chars }} caracteres por aliança</span></li>
              @endif
              <li class="ck--ok"><x-velaro.icon name="check" /><span>Texto e data no mesmo pedido</span></li>
              <li class="ck--ok"><x-velaro.icon name="check" /><span>Prazo adicional de 5 dias úteis</span></li>
            </ul>
          </div>
        @endif

        {{-- Nota do protótipo aprovado (16-site-produto.html), mantida palavra por palavra. --}}
        <p class="notice notice--gold">
          <x-velaro.icon name="info" />
          <span><strong>Sem preço nesta página.</strong> A rota pública não serializa <code>products.price</code> (regra 1 do escopo 1.3 · Anexo I §3.3).</span>
        </p>
      </div>
    </div>
  </div></section>

  <section class="band-dark" id="condicoes"><div class="band__inner">
    <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1.15fr);gap:var(--space-8)">
      <div>
        <span class="eyebrow" style="color:var(--color-gold-300)">Condições comerciais</span>
        <h2 class="display-md" style="margin-top:var(--space-3)">Como a Velaro vende para a sua loja.</h2>
        <p class="lede" style="margin-top:var(--space-4);color:rgba(255,255,255,.7)">
          Toda relação financeira desta plataforma é Velaro → lojista. Quem vende ao consumidor final é você,
          pelo preço que você define na sua vitrine.</p>
        <div class="row row--wrap" style="margin-top:var(--space-6)">
          <a class="btn btn--gold" href="{{ route('site.cadastro') }}"><x-velaro.icon name="user" /> Fazer cadastro como lojista</a>
          <a class="btn btn--ghost-gold" href="{{ route('site.contato', ['origem' => 'produto']) }}"><x-velaro.icon name="support" /> Falar com especialista</a>
        </div>
      </div>
      <div class="grid g2" style="gap:var(--space-5)">
        @foreach([
          ['box', 'Pedido mínimo', 'A partir de 10 peças por pedido, sem exigência de mix de modelos.'],
          ['clock', 'Produção sob demanda', 'Até 7 dias úteis para itens de catálogo; 12 dias úteis com gravação ou aro fora da grade.'],
          ['card', 'Pagamento B2B', 'Pix, boleto ou transferência — sempre Velaro → lojista. A plataforma não processa pagamento do consumidor final.'],
          ['truck', 'Entrega e retirada', 'Envio para todo o Brasil ou retirada na fábrica, com rastreio dentro do Portal.'],
        ] as [$icone, $titulo, $texto])
          <div class="row" style="gap:var(--space-4);align-items:flex-start">
            <x-velaro.icon :name="$icone" />
            <div>
              <strong style="display:block;color:#fff;font-size:15px">{{ $titulo }}</strong>
              <p style="margin:4px 0 0;font-size:var(--text-sm);line-height:20px;color:rgba(255,255,255,.62)">{{ $texto }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div></section>

  @if($relacionados !== [])
    <section class="band-light"><div class="band__inner">
      <div class="section-head">
        <span class="eyebrow" style="color:var(--color-gold-700)">Modelos relacionados</span>
        <h2 class="display-md" style="margin-top:var(--space-2)">Quem escolhe a {{ $produto->name }} também leva</h2>
        <div class="rule"><span>VELARO</span></div>
      </div>
      <div class="prods">
        @foreach($relacionados as $cartao)
          @include('site.catalogo.partials.card', ['cartao' => $cartao, 'chipPreco' => true])
        @endforeach
      </div>
    </div></section>
  @endif
</x-velaro.layouts.site>
