{{--
[Modulo: resources/views/site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.2: pagina institucional montada com o grupo `about` de settings.
--}}
<x-velaro.layouts.site title="Sobre nós">

{{-- ============================== HERO ============================== --}}
<x-slot:hero>
<section class="hero">
  <div class="hero__inner">
    <div>
      <span class="badge-b2b"><x-velaro.icon name="users" /> {{ $texto['hero_eyebrow'] ?? '' }}</span>
      <h1>{{ $texto['hero_titulo'] ?? '' }}</h1>
      @foreach ($apresentacao as $paragrafo)
        <p class="lede" @if(! $loop->first) style="margin-top:var(--space-3)" @endif>{{ $paragrafo }}</p>
      @endforeach

      <div class="hero__ctas">
        <a class="btn btn--gold" href="{{ route('site.cadastro') }}">
          <x-velaro.icon name="store" />
          <span class="cta-stack"><strong>Seja um revendedor</strong><small>Solicite seu cadastro</small></span>
        </a>
        <a class="btn btn--ghost-gold" href="{{ route('site.catalogo') }}">
          <x-velaro.icon name="book" />
          <span class="cta-stack"><strong>Ver catálogo</strong><small>Conheça nossas coleções</small></span>
        </a>
      </div>
    </div>

    <div class="hero__art">
      <div style="width:290px"><x-velaro.ring variant="classica" alt="Par de alianças Velaro" /></div>
    </div>
  </div>
</section>
</x-slot:hero>

{{-- =========================== NOSSA HISTÓRIA =========================== --}}
<section class="band-light">
  <div class="band__inner">
    <div class="split" style="--gcols:minmax(0,.9fr) minmax(0,1.6fr);gap:var(--space-8)">
      <div>
        <span class="eyebrow" style="color:var(--color-gold-700)">{{ $texto['historia_eyebrow'] ?? '' }}</span>
        <h2 class="display-md" style="margin-top:var(--space-3)">{{ $texto['historia_titulo'] ?? '' }}</h2>
        @foreach ($historia as $paragrafo)
          <p class="lede" style="margin-top:var(--space-4)">{{ $paragrafo }}</p>
        @endforeach
      </div>

      @php($icones = ['factory', 'diamond', 'support', 'truck'])
      <div class="grid g2">
        @foreach ($diferenciais as $indice => $diferencial)
          <div class="card" style="text-align:center">
            <div style="display:grid;place-items:center;gap:10px">
              <x-velaro.icon :name="$icones[$indice % 4]"
                             style="width:34px;height:34px;color:var(--color-gold-600)" />
              <h3 class="title" style="font-size:15px;letter-spacing:.06em;text-transform:uppercase">{{ $diferencial['titulo'] }}</h3>
              <p style="margin:0;font-size:var(--text-sm);line-height:20px">{{ $diferencial['texto'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ==================== PENSADO PARA O SEU NEGÓCIO ==================== --}}
<section class="band-dark">
  <div class="band__inner">
    <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr);gap:var(--space-8)">
      <div>
        <span class="eyebrow" style="color:var(--color-gold-300)">{{ $texto['negocio_eyebrow'] ?? '' }}</span>
        <h2 class="display-md" style="margin-top:var(--space-3)">{{ $texto['negocio_titulo'] ?? '' }}</h2>
        <p class="lede" style="margin-top:var(--space-4);color:rgba(255,255,255,.7)">{{ $texto['negocio_texto'] ?? '' }}</p>
      </div>

      <div>
        <span class="eyebrow" style="color:var(--color-gold-300)">{{ $texto['numeros_titulo'] ?? '' }}</span>
        @php($marcas = ['diamond', 'globe', 'ring', 'users'])
        <div class="grid g2" style="margin-top:var(--space-4);gap:var(--space-5)">
          @foreach ($numeros as $indice => $numero)
            <div class="row" style="gap:var(--space-4);align-items:flex-start">
              <x-velaro.icon :name="$marcas[$indice % 4]"
                             style="width:30px;height:30px;color:var(--color-gold-400);flex:none" />
              <div>
                <strong style="display:block;color:#fff;font-size:15px">{{ $numero['titulo'] }}</strong>
                <p style="margin:4px 0 0;font-size:var(--text-sm);line-height:20px;color:rgba(255,255,255,.62)">{{ $numero['texto'] }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============================ CTA FINAL ============================ --}}
<section class="band-dark" style="background:var(--color-brand-700);padding:40px var(--space-8)">
  <div class="band__inner row row--wrap" style="gap:var(--space-8)">
    <div style="flex:1;min-width:260px">
      <h2 class="display-sm" style="color:#fff">{{ $texto['cta_titulo'] ?? '' }}</h2>
      <p class="lede gold" style="margin-top:6px">{{ $texto['cta_texto'] ?? '' }}</p>
    </div>
    {{-- Regra 2 do escopo 1.2: o lead nasce na 1.8, com `origin = sobre`. --}}
    <a class="btn btn--gold" href="{{ route('site.contato', ['origem' => 'sobre']) }}">
      <x-velaro.icon name="user" /> Solicitar atendimento
    </a>
    <a class="btn btn--ghost-gold" href="{{ route('site.catalogo') }}">
      <x-velaro.icon name="book" /> Ver catálogo
    </a>
  </div>
</section>

</x-velaro.layouts.site>
