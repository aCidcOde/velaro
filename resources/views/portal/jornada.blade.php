{{--
[Modulo: resources/views/portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.1 antes da aprovacao: acompanhamento da solicitacao, reenvio de documentos e caminho de regularizacao.
--}}
@php($encerrado = $estagio->encerrado())
<x-velaro.layouts.portal title="Painel" titulo="Portal do Lojista">

  <div class="page-head">
    <div>
      <h1 class="display-md">{{ $encerrado ? 'Seu cadastro' : 'Sua solicitação' }}</h1>
      <p class="lede">
        {{ $encerrado
            ? 'O acesso de Parceiro Premium está suspenso. Veja abaixo o motivo e como regularizar.'
            : 'Este é o primeiro passo da sua jornada. Assim que a análise terminar, o painel abre completo por aqui mesmo.' }}
      </p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.dashboard') }}">
        <x-velaro.icon name="refresh" /> Atualizar status</a>
      <a class="btn btn--gold" href="{{ route('site.contato') }}">
        <x-velaro.icon name="support" /> Falar com nossa equipe</a>
    </div>
  </div>

  @if (session('status'))
    <p class="notice notice--ok" style="margin-bottom:var(--space-4)">
      <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  {{-- A mesma barra de identificação da tela 1.6. Aqui ela vale dobrado: é o que
       diz ao lojista que o cadastro que ele acompanha é o da conta com que
       acabou de entrar. --}}
  <div class="identbar">
    @foreach ($identificacao as $celula)
      <div class="identcell">
        <x-velaro.icon :name="$celula['icone']" />
        <span><small>{{ $celula['rotulo'] }}</small><strong>{{ $celula['valor'] ?: '—' }}</strong></span>
      </div>
    @endforeach
  </div>

  <section class="split split--wide" style="margin-top:var(--space-4)">
    <div class="stack">
      <div class="card">
        <div class="card__head"><h2 class="title">Etapas da habilitação</h2></div>
        <x-velaro.solicitacao.stepper :steps="$steps" :rotulos="$rotulosDasEtapas" />
      </div>

      @unless ($encerrado)
        <div class="card">
          <x-velaro.solicitacao.verificacao :checks="$checks" />
        </div>

        <x-velaro.solicitacao.reenvio-documentos :reseller="$reseller" :documentos="$documentos" />
      @endunless

      @if ($regularizacao !== [])
        <div class="card">
          <div class="card__head"><h2 class="title">Como regularizar</h2></div>
          <div class="stacklist">
            @foreach ($regularizacao as $passo)
              <div class="orderitem">
                <span class="kpi__icon kpi__icon--gold" style="width:34px;height:34px;border-radius:var(--radius-sm)">
                  <x-velaro.icon :name="$passo['icone']" />
                </span>
                <span>
                  <strong>{{ $passo['titulo'] }}</strong>
                  <small>{{ $passo['texto'] }}</small>
                </span>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card">
        <div class="card__head"><h2 class="title">Linha do tempo da solicitação</h2></div>
        <x-velaro.solicitacao.linha-do-tempo :timeline="$timeline" />
      </div>
    </div>

    <div class="stack">
      <div class="card panel-dark">
        <span class="eyebrow" style="color:var(--color-gold-300)">Status atual</span>
        <h2 class="display-sm" style="color:#fff;margin-top:var(--space-2)">{{ $painel['titulo'] }}</h2>
        <p style="margin-top:var(--space-3);font-size:var(--text-sm);line-height:21px;color:rgba(255,255,255,.7)">
          {{ $painel['texto'] }}</p>
      </div>

      @if ($proximasEtapas !== [])
        <div class="card">
          <div class="card__head"><h2 class="title">Próximas etapas</h2></div>
          <ol class="howto">
            @foreach ($proximasEtapas as $indice => $texto)
              <li><span class="num">{{ $indice + 1 }}</span><div><strong>{{ $texto }}</strong></div></li>
            @endforeach
          </ol>
        </div>
      @endif

      {{-- O menu lateral já mostra, desabilitado, tudo o que a aprovação abre. Este
           cartão diz em palavras a mesma coisa: a jornada não é uma tela de espera,
           é a primeira parada de um caminho que continua no mesmo login. --}}
      <div class="card">
        <div class="card__head"><h2 class="title">O que a aprovação libera</h2></div>
        <ul class="cklist">
          @foreach ([
            ['book', 'Catálogo com o seu custo de parceiro'],
            ['bag', 'Pedidos e compras em lote'],
            ['coin', 'Financeiro, notas e retirada de pedidos'],
            ['store', 'Sua vitrine white label, com os seus preços'],
          ] as [$icone, $texto])
            <li class="ck--wait">
              <x-velaro.icon :name="$icone" />
              <span>{{ $texto }}</span>
              <b>Após aprovação</b>
            </li>
          @endforeach
        </ul>
      </div>

      <p class="notice notice--gold"><x-velaro.icon name="info" />
        <span>A IA faz a <strong>triagem</strong>. A decisão final é sempre <strong>humana</strong>
          e fica registrada com justificativa.</span></p>
    </div>
  </section>
</x-velaro.layouts.portal>
