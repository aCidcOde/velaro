{{--
[Modulo: resources/views/site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.1: pagina inicial B2B — hero exclusivo para lojistas, pilares, colecoes e chamada de cadastro.
--}}
<x-velaro.layouts.site title="Plataforma exclusiva para lojistas">

{{-- ============================== HERO ============================== --}}
<x-slot:hero>
<section class="hero">
  <div class="hero__inner">
    <div>
      <span class="badge-b2b">
        <x-velaro.icon name="users" /> Plataforma exclusiva para lojistas e revendedores
      </span>

      <h1>Exclusivo para<br><em>lojistas e revendedores</em></h1>

      <p class="lede">A Velaro é parceira de negócios para lojas e revendedores de alianças, com condições
        comerciais especiais, catálogo profissional e suporte dedicado para impulsionar suas vendas.</p>

      <div class="hero__ctas">
        <a class="btn btn--gold" href="{{ route('site.cadastro') }}">
          <x-velaro.icon name="store" />
          <span class="cta-stack"><strong>Quero ser revendedor</strong><small>Cadastro exclusivo para lojistas</small></span>
        </a>
        <a class="btn btn--ghost-gold" href="{{ route('site.catalogo') }}">
          <x-velaro.icon name="book" />
          <span class="cta-stack"><strong>Ver catálogo profissional</strong><small>Soluções para revenda</small></span>
        </a>
      </div>

      {{-- Regra 3 do escopo 1.1: a página diz, no próprio hero, que não há venda ao consumidor final. --}}
      <p class="hero__note">
        <x-velaro.icon name="info" /> Não realizamos vendas diretas ao consumidor final.
      </p>
    </div>

    <div class="hero__art">
      <div aria-hidden="true" style="position:absolute;right:-40px;top:50%;transform:translateY(-50%);width:420px;height:420px;border-radius:50%;pointer-events:none;background:radial-gradient(circle at 42% 40%, rgba(219,167,101,.20), transparent 64%)"></div>
      <svg viewBox="0 0 190 226" fill="none" aria-hidden="true" style="width:190px;height:226px">
        <path d="M95 6 178 30v104c0 44-38 68-83 86-45-18-83-42-83-86V30z" fill="rgba(1,34,39,.55)" stroke="var(--color-gold-400)" stroke-width="2"/>
        <path d="M95 16 168 37v96c0 38-33 59-73 75-40-16-73-37-73-75V37z" stroke="var(--color-gold-700)" stroke-width="1"/>
        <path d="M79 42l6-8h20l6 8z" fill="var(--color-gold-400)"/>
        <text x="95" y="112" text-anchor="middle" font-family="Jost, sans-serif" font-size="44" font-weight="500" letter-spacing="1" fill="var(--color-gold-300)">B2B</text>
        <text x="95" y="134" text-anchor="middle" font-family="Jost, sans-serif" font-size="13" letter-spacing="3" fill="rgba(255,255,255,.86)">EXCLUSIVO</text>
        <rect x="24" y="150" width="142" height="30" rx="4" fill="var(--color-gold-500)"/>
        <text x="95" y="170" text-anchor="middle" font-family="Jost, sans-serif" font-size="13" font-weight="700" letter-spacing="2" fill="#1a0d05">PARA LOJISTAS</text>
      </svg>
    </div>
  </div>
</section>
</x-slot:hero>

{{-- ========================= PILARES DA HOME ========================= --}}
{{-- A home troca os quatro pilares padrão do casco pelos do protótipo 01. --}}
<x-slot:pillars>
  @foreach ([
    ['diamond', 'Condições comerciais para revenda', 'Preços e vantagens exclusivas para lojistas.'],
    ['support', 'Suporte ao lojista', 'Atendimento dedicado para impulsionar seus resultados.'],
    ['book', 'Catálogo profissional', 'Coleções completas com informações técnicas e fotos de alta qualidade.'],
    ['chart', 'Parceria para revendedores', 'Relacionamento de confiança para crescermos juntos.'],
  ] as [$icone, $titulo, $descricao])
    <div class="pillar">
      <x-velaro.icon :name="$icone" style="width:32px;height:32px;color:var(--color-gold-400)" />
      <div><h3>{{ $titulo }}</h3><p>{{ $descricao }}</p></div>
    </div>
  @endforeach
</x-slot:pillars>

{{-- ============================ COLEÇÕES ============================ --}}
<section class="band-dark">
  <div class="band__inner">
    <div class="section-head">
      <h2 class="display-md">Coleções Velaro</h2>
      <div class="rule"><span>◆</span></div>
      <p class="lede" style="margin-top:12px;color:rgba(255,255,255,.66)">Designs exclusivos para todos os estilos e momentos.</p>
    </div>

    <div class="prods">
      @forelse ($colecoes as $colecao)
        <div class="prod">
          <div class="prod__img">
            @if ($colecao->cover_path)
              <img src="{{ asset($colecao->cover_path) }}" alt="Coleção {{ $colecao->name }}"
                   loading="lazy" style="width:100%;height:100%;object-fit:contain">
            @else
              <x-velaro.ring :alt="'Coleção '.$colecao->name" style="width:100%;height:100%;object-fit:contain" />
            @endif
          </div>
          <h4>{{ $colecao->name }}</h4>
          <small class="prod__spec">{{ $colecao->description }}</small>
          <div class="prod__acts">
            <a class="btn btn--secondary btn--sm" href="{{ route('site.catalogo', $colecao->slug) }}">Ver coleção</a>
          </div>
        </div>
      @empty
        <p class="lede" style="color:rgba(255,255,255,.66)">Nossas coleções estão sendo atualizadas. Fale com o time comercial para conhecer o catálogo.</p>
      @endforelse
    </div>

    <div class="row row--wrap" style="justify-content:center;margin-top:var(--space-8)">
      <a class="btn btn--ghost-gold" href="{{ route('site.catalogo') }}">Ver todas as coleções ›</a>
    </div>

    {{-- Regra 2 do escopo 1.1: o catálogo público não mostra custo B2B. --}}
    <p class="notice notice--dark" style="margin-top:var(--space-8)">
      <x-velaro.icon name="shield" />
      <span><strong>Catálogo público sem preço interno.</strong> Custos, condições e ferramentas de pedido são
        liberados somente após aprovação do cadastro do lojista.</span>
    </p>
  </div>
</section>

{{-- ============================ CTA FINAL ============================ --}}
<section class="band-dark" style="background:var(--color-brand-700);padding:40px var(--space-8)">
  <div class="band__inner row row--wrap" style="gap:var(--space-8)">
    <div style="flex:1;min-width:260px">
      <h2 class="display-sm" style="color:#fff">Seja um revendedor Velaro.</h2>
      <p class="lede gold" style="margin-top:6px">Tenha acesso às condições especiais, lançamentos e suporte dedicado.</p>
    </div>
    <a class="btn btn--gold" href="{{ route('site.cadastro') }}">Fazer cadastro como lojista</a>
    {{-- Regra 4 do escopo 1.1: o lead nasce na 1.8; esta tela só marca a origem. --}}
    <a class="btn btn--ghost-gold" href="{{ route('site.contato', ['origem' => 'home']) }}">Falar com especialista</a>
  </div>
</section>

</x-velaro.layouts.site>
