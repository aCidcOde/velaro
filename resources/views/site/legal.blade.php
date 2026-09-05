{{--
[Modulo: resources/views/site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Casca dos documentos legais: hero com selo de versao, indice lateral e corpo numerado.
--}}
<x-velaro.layouts.site :title="$documento['titulo']">

{{-- ============================== HERO ============================== --}}
<x-slot:hero>
<section class="hero">
  <div class="hero__inner">
    <div>
      <span class="badge-b2b"><x-velaro.icon name="doc" /> Documentos legais</span>
      <h1>{{ $documento['titulo'] }}</h1>
      <p class="hero-sub">{{ $selo }}</p>
      <p class="lede">{{ $documento['resumo'] }}</p>
      <p class="hero__note"><x-velaro.icon name="info" /> {{ $aviso }}</p>
    </div>
    <div class="hero__art">
      <div style="width:250px"><x-velaro.ring variant="branco" alt="Par de alianças Velaro" /></div>
    </div>
  </div>
</section>
</x-slot:hero>

{{-- ============================ DOCUMENTO ============================ --}}
<section class="band-light">
  <div class="band__inner">

    <div class="identbar">
      @foreach ($identidade as $celula)
        <div class="identcell">
          <x-velaro.icon :name="$celula['icone']" style="color:var(--color-gold-600)" />
          <span><small>{{ $celula['rotulo'] }}</small><strong>{{ $celula['valor'] }}</strong></span>
        </div>
      @endforeach
    </div>

    <div class="split" style="--gcols:300px minmax(0,1fr);margin-top:var(--space-4)">
      <nav class="legalidx" aria-label="Índice do documento">
        <strong>Neste documento</strong>
        @foreach ($documento['secoes'] as $ordem => $secao)
          <a href="#{{ $secao['id'] }}">
            <b>{{ str_pad((string) ($ordem + 1), 2, '0', STR_PAD_LEFT) }}</b><span>{{ $secao['titulo'] }}</span>
          </a>
        @endforeach
        <a class="legalidx__alt" href="{{ route($rotaAlternativa) }}">
          <x-velaro.icon name="link" /><span>{{ $rotuloAlternativo }}</span>
        </a>
      </nav>

      <div class="stack">
        <div class="card">
          <div class="legaltext">
            @foreach ($documento['secoes'] as $ordem => $secao)
              <section id="{{ $secao['id'] }}">
                <h3><span>{{ $ordem + 1 }}.</span>{{ $secao['titulo'] }}</h3>
                {{-- Texto legal escrito no proprio codigo (LegalDocumentService); nao vem de input. --}}
                {!! $secao['corpo'] !!}
              </section>
            @endforeach
          </div>
        </div>

        <p class="notice">
          <x-velaro.icon name="info" />
          <span><strong>Registro do aceite.</strong> Ao enviar o cadastro de lojista, a plataforma grava data,
            hora, IP e a <strong>versão {{ $versao }}</strong> deste texto, conforme o escopo 1.4 (Anexo I §3.4).</span>
        </p>

        <div class="card">
          <div class="spread">
            <div>
              <h2 class="title">Dúvidas sobre este documento?</h2>
              <p class="lede" style="margin-top:4px;font-size:var(--text-sm)">
                Fale com o encarregado de dados pelo e-mail {{ $dpo }} ou pelo nosso atendimento.</p>
            </div>
            <div class="row row--wrap">
              <a class="btn btn--primary" href="{{ route('site.cadastro') }}">
                <x-velaro.icon name="user-plus" /> Ir para o cadastro
              </a>
              <a class="btn btn--secondary" href="{{ route($rotaAlternativa) }}">
                <x-velaro.icon name="doc" /> {{ $rotuloAlternativo }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

</x-velaro.layouts.site>
