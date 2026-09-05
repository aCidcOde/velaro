{{--
[Modulo: resources/views/portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Central de ajuda do portal: busca, categorias, artigo em destaque, FAQ da plataforma, guias, videos e canais.
--}}
<x-velaro.layouts.portal title="Central de ajuda" titulo="Central de ajuda">

  <div class="page-head">
    <div>
      <h1 class="display-md">Central de ajuda</h1>
      <p class="lede">Tutoriais, guias e respostas para as dúvidas mais comuns do Portal do Lojista.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.suporte.index') }}">← Voltar para o suporte</a>
      <a class="btn btn--gold" href="{{ route('portal.suporte.create') }}"><x-velaro.icon name="support" /> Abrir chamado</a>
    </div>
  </div>

  <div class="card">
    <form method="GET" action="{{ route('portal.ajuda') }}" role="search">
      <span class="helpsearch">
        <x-velaro.icon name="search" />
        <input class="input input--compact" type="search" name="q" value="{{ $busca }}"
               style="border:0;background:transparent;padding:0"
               placeholder="Buscar artigo, guia ou vídeo — ex.: “margem”, “lote”, “nota fiscal”…"
               aria-label="Buscar artigo, guia ou vídeo">
      </span>
    </form>

    <div class="row row--wrap" style="margin-top:var(--space-3);gap:6px">
      <small class="muted">Buscas mais comuns:</small>
      @foreach($buscasComuns as $sugestao)
        <a class="chip chip--neutral chip--flat" href="{{ route('portal.ajuda', ['q' => $sugestao]) }}">{{ $sugestao }}</a>
      @endforeach
    </div>
  </div>

  @if($categorias !== [])
    <div class="card">
      <div class="card__head"><h2 class="title">Categorias</h2></div>
      <div class="quickgrid">
        @foreach($categorias as $categoria)
          <a class="quickcard" href="{{ route('portal.ajuda', ['q' => $categoria['nome']]) }}">
            <x-velaro.icon :name="$categoria['icone']" />
            <span><strong>{{ $categoria['nome'] }}</strong><small>{{ $categoria['artigos'] }} {{ $categoria['artigos'] === 1 ? 'artigo' : 'artigos' }}</small></span>
            <b>›</b>
          </a>
        @endforeach
      </div>
    </div>
  @endif

  <div class="split" style="--gcols:minmax(0,1fr) 320px">
    <div class="stack">

      @if($busca !== null)
        {{-- Modo busca: o resultado toma o lugar do artigo aberto. --}}
        <div class="card">
          <div class="card__head">
            <h2 class="title">Resultados para “{{ $busca }}”</h2>
            <a class="link-gold" href="{{ route('portal.ajuda') }}">Limpar busca →</a>
          </div>

          @if($resultados === [] && $faq === [])
            <p class="notice notice--info">
              <x-velaro.icon name="info" />
              <span><strong>Nada encontrado para esse termo.</strong>
                <a href="{{ route('portal.suporte.create') }}">Abra um chamado</a> e o time da Velaro responde com o seu pedido à vista.</span>
            </p>
          @else
            <div class="artlist">
              @foreach($resultados as $resultado)
                <span class="artitem">
                  <x-velaro.icon name="doc" />
                  <span><strong>{{ $resultado['titulo'] }}</strong></span>
                  <small>{{ $resultado['tipo'] }} · {{ $resultado['minutos'] }} min</small>
                </span>
              @endforeach
            </div>
          @endif
        </div>
      @elseif($destaque !== null)
        <section id="artigo">
          <div class="card">
            <div class="row row--wrap" style="gap:8px;margin-bottom:var(--space-3)">
              <span class="chip chip--brand chip--flat">{{ $destaque['categoria'] }}</span>
              <small class="muted">{{ $destaque['minutos'] }} min de leitura</small>
              @if($destaque['atualizado'])
                <small class="muted">· Atualizado em {{ $destaque['atualizado'] }}</small>
              @endif
            </div>

            <h2 class="display-sm">{{ $destaque['titulo'] }}</h2>

            <div class="artbody" style="margin-top:var(--space-4)">
              {{-- `help_articles.body` é HTML editorial da própria Velaro, como
                   o texto legal do site (site/legal.blade.php) — por isso sai
                   sem escape. O campo não tem escrita pelo lojista: nenhuma rota
                   do portal grava em `help_articles`. Quem for construir a
                   edição do artigo no Master precisa sanear na escrita, porque
                   este é o ponto em que o HTML gravado vira HTML renderizado. --}}
              {!! $destaque['corpo'] !!}
            </div>

            <div class="spread" style="margin-top:var(--space-5);padding-top:var(--space-4);border-top:1px solid var(--border)">
              <small class="muted">Não resolveu? O time da Velaro responde em horário comercial.</small>
              <a class="btn btn--gold" href="{{ route('portal.precos.edit') }}"><x-velaro.icon name="tag" /> Ir para Preços e margens</a>
            </div>
          </div>
        </section>
      @elseif(! $temBiblioteca)
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span><strong>A biblioteca de artigos ainda está sendo publicada.</strong>
            As perguntas frequentes abaixo já respondem o essencial da operação; para o resto,
            <a href="{{ route('portal.suporte.create') }}">abra um chamado</a>.</span>
        </p>
      @endif

      {{-- FAQ operacional: as regras do contrato, iguais para todo lojista. --}}
      @if($faq !== [])
        <section id="faq">
          <div class="card">
            <div class="card__head">
              <h2 class="title">Perguntas frequentes</h2>
              <a class="link-gold" href="{{ route('portal.suporte.create') }}">Não achei minha dúvida →</a>
            </div>
            @foreach($faq as $item)
              <div class="faqitem">
                <strong>{{ $item['pergunta'] }}</strong>
                <p>{{ $item['resposta'] }}</p>
              </div>
            @endforeach
          </div>
        </section>
      @endif

      @if($guias !== [])
        <section id="guias">
          <div class="card">
            <div class="card__head"><h2 class="title">Guias e manuais</h2></div>
            @foreach($guias as $guia)
              <a class="docfile" href="{{ $guia['url'] }}">
                <x-velaro.icon name="doc" />
                <span><strong>{{ $guia['titulo'] }}</strong><small>{{ $guia['descricao'] }}</small></span>
                <b class="docfile__ok">↓</b>
              </a>
            @endforeach
            <small class="fhint">Materiais atualizados a cada revisão do catálogo. Baixe sempre a versão mais recente.</small>
          </div>
        </section>
      @endif

      @if($videos !== [])
        <section id="videos">
          <div class="card">
            <div class="card__head"><h2 class="title">Vídeos tutoriais</h2></div>
            <div class="videogrid">
              @foreach($videos as $video)
                <a class="videocard" href="{{ $video['url'] }}" rel="noopener noreferrer" target="_blank">
                  <span class="videocard__thumb"><b>▶</b></span>
                  <strong>{{ $video['titulo'] }}</strong>
                  <small>{{ $video['categoria'] }}</small>
                </a>
              @endforeach
            </div>
          </div>
        </section>
      @endif
    </div>

    <div class="stack">
      @if($destaque !== null && $naCategoria !== [])
        <div class="card">
          <div class="card__head"><h2 class="title">Nesta categoria · {{ $destaque['categoria'] }}</h2></div>
          <div class="artlist">
            @foreach($naCategoria as $artigo)
              <span @class(['artitem', 'is-on' => $artigo['atual']])>
                <x-velaro.icon name="doc" />
                <span><strong>{{ $artigo['titulo'] }}</strong></span>
                <small>{{ $artigo['minutos'] }} min</small>
              </span>
            @endforeach
          </div>
        </div>
      @endif

      @if($maisLidos !== [])
        <div class="card">
          <div class="card__head"><h2 class="title">Mais lidos</h2></div>
          <div class="artlist">
            @foreach($maisLidos as $artigo)
              <span class="artitem">
                <x-velaro.icon name="doc" />
                <span><strong>{{ $artigo['titulo'] }}</strong></span>
                <small>{{ $artigo['minutos'] }} min</small>
              </span>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card">
        <div class="card__head"><h2 class="title">Atalhos do Portal</h2></div>
        <div class="stack">
          @foreach($atalhos as $atalho)
            <a class="seclink" href="{{ $atalho['url'] }}">
              <x-velaro.icon :name="$atalho['icone']" />
              <span><strong>{{ $atalho['titulo'] }}</strong><small>{{ $atalho['descricao'] }}</small></span>
            </a>
          @endforeach
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Não encontrou o que precisava?</h2></div>
        <p class="lede" style="font-size:var(--text-sm)">
          Abra um chamado e o time da Velaro responde em horário comercial, com o seu pedido e o seu lote já à vista.
        </p>
        <a class="btn btn--primary" href="{{ route('portal.suporte.create') }}"><x-velaro.icon name="support" /> Abrir chamado</a>
        <div class="datarow" style="margin-top:var(--space-3)">
          <span class="datarow__k"><x-velaro.icon name="clock" /> Atendimento</span>
          <span class="datarow__v">{{ $atendimento }}</span>
        </div>
      </div>

      <p class="notice notice--info">
        <x-velaro.icon name="info" />
        <span>A central de ajuda é do <strong>Portal do Lojista</strong>. Conteúdo para o consumidor final
          não existe aqui: ele não tem login na plataforma.</span>
      </p>
    </div>
  </div>
</x-velaro.layouts.portal>
