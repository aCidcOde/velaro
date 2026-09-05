{{--
[Modulo: resources/views/site/catalogo]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.3 — catalogo publico: hero, barra de filtros, grade de modelos sem preco e chamada de cadastro.
--}}
<x-velaro.layouts.site :title="$colecaoAtual ? 'Catálogo · '.$colecaoAtual->name : 'Catálogo'">
  <x-slot:hero>
    <section class="hero"><div class="hero__inner">
      <div>
        @if($colecaoAtual)
          <span class="badge-b2b"><x-velaro.icon name="diamond" /> Catálogo › {{ $colecaoAtual->name }}</span>
        @endif
        <h1>CATÁLOGO VELARO</h1>
        <p class="hero-sub">Coleções que unem design, qualidade e confiança.</p>
        <p class="lede">Conheça nossas coleções de alianças. Preços e condições comerciais são liberados após cadastro e aprovação do lojista.</p>
      </div>
      <div class="hero__art"><div style="width:280px">
        <x-velaro.ring variant="cravejada" alt="Par de alianças Velaro" />
      </div></div>
    </div></section>
  </x-slot:hero>

  <section class="band-light"><div class="band__inner">
    @include('site.catalogo.partials.filtros')

    @if($cartoes === [])
      <p class="notice notice--info" style="margin-top:var(--space-4)">
        <x-velaro.icon name="info" />
        <span><strong>Nenhum modelo encontrado com esses filtros.</strong>
          <a href="{{ route('site.catalogo') }}">Ver o catálogo completo</a>.</span>
      </p>
    @else
      <div class="prods" style="margin-top:var(--space-4)">
        @foreach($cartoes as $cartao)
          @include('site.catalogo.partials.card', ['cartao' => $cartao])
        @endforeach
      </div>
      @include('site.catalogo.partials.paginacao')
    @endif

    <p class="notice notice--gold" style="margin-top:var(--space-4)">
      <x-velaro.icon name="info" />
      <span><strong>Catálogo público sem preço interno.</strong> Condições exclusivas para lojistas disponíveis após aprovação do cadastro.</span>
    </p>
  </div></section>

  <section class="band-dark" style="background:var(--color-brand-700);padding:40px var(--space-8)" id="contato">
    <div class="band__inner row row--wrap" style="gap:var(--space-8)">
      <div style="flex:1;min-width:260px">
        <h2 class="display-sm" style="color:#fff">Seja um revendedor Velaro.</h2>
        <p class="lede gold" style="margin-top:6px">Tenha acesso às condições especiais, lançamentos e suporte dedicado.</p>
      </div>
      <a class="btn btn--gold" href="{{ route('site.cadastro') }}"><x-velaro.icon name="user" /> Fazer cadastro como lojista</a>
      <a class="btn btn--ghost-gold" href="{{ route('site.contato', ['origem' => 'catalogo']) }}"><x-velaro.icon name="support" /> Falar com especialista</a>
    </div>
  </section>
</x-velaro.layouts.site>
