{{--
/*
[Modulo: resources/views/portal/suporte]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Chamado do lojista (mockup 42): ficha, conversa Velaro-revendedor, historico de status e anexos.
*/
--}}
<x-velaro.layouts.portal :title="'Chamado '.$chamado->code" titulo="Suporte">

<div class="page-head">
  <div>
    <h1 class="display-md">Chamado de suporte</h1>
    <p class="lede">Acompanhe o atendimento em andamento com a equipe Velaro.</p>
  </div>
  <div class="row row--wrap">
    <a class="btn btn--secondary" href="{{ route('portal.suporte.index') }}">← Voltar para o suporte</a>
    <a class="btn btn--secondary" href="{{ route('portal.ajuda') }}">
      <x-velaro.icon name="book" /> Central de ajuda</a>
  </div>
</div>

@if(session('status'))
  <p class="notice notice--ok" style="margin-bottom:var(--space-4)" role="status">
    <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
@endif

<div class="card">
  <div class="spread">
    <div>
      <span class="eyebrow">Chamado em andamento</span>
      <div class="row row--wrap" style="gap:10px;margin-top:6px">
        <h2 class="display-sm">{{ $chamado->code }}</h2>
        <span class="chip {{ $status['chip'] }}">{{ $status['rotulo'] }}</span>
        <span class="chip {{ $prioridade['chip'] }}">Prioridade: {{ $prioridade['rotulo'] }}</span>
      </div>
      <p class="lede" style="margin-top:6px"><strong style="color:var(--ink)">{{ $chamado->subject }}</strong></p>
      <small class="muted">Aberto em {{ $chamado->created_at?->format('d/m/Y \à\s H:i') }}
        · última atualização {{ $chamado->updated_at?->format('d/m/Y \à\s H:i') }}</small>
    </div>
  </div>

  <div class="identbar" style="margin-top:var(--space-4)">
    <div class="identcell"><span><small>Minha loja</small>
      <strong>{{ $chamado->reseller?->trade_name ?? $chamado->reseller?->legal_name }}<br>
        <small class="muted">Cód. {{ $chamado->reseller?->code }}</small></strong></span></div>
    <div class="identcell"><span><small>Contato</small><strong>{{ $contato }}</strong></span></div>
    <div class="identcell"><span><small>Categoria</small><strong>{{ $chamado->category }}</strong></span></div>
    <div class="identcell"><span><small>Pedido relacionado</small>
      <strong>
        @if($chamado->order)
          {{ $chamado->order->public_number }}<br>
          <a class="link-gold" href="{{ route('portal.pedidos.show', $chamado->order) }}">Ver pedido ↗</a>
        @else
          —
        @endif
      </strong></span></div>
    {{-- O consumidor final aparece só como pessoa vinculada ao pedido: ele não
         tem login e não participa da conversa. --}}
    <div class="identcell"><span><small>Cliente final</small>
      <strong>{{ $chamado->customer?->name ?? '—' }}
        @if($chamado->customer)<br><small class="muted">Vinculada ao pedido</small>@endif
      </strong></span></div>
  </div>
</div>

<div class="split split--wide">
  <div class="stack">

    {{-- ─────────────── CONVERSA ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">Conversa</h2></div>

      {{-- A lista já vem cortada pelo service: `is_internal_note` nunca entra na
           consulta, então não há nota interna nesta variável para vazar. --}}
      <div class="thread">
        @forelse($conversa as $mensagem)
          @php($daVelaro = $mensagem->author_role === $papelVelaro)
          <div class="msg @if($daVelaro) msg--agent @endif">
            <span class="avatar avatar--sm">{{ mb_strtoupper(mb_substr($daVelaro ? 'Equipe Velaro' : ($mensagem->author?->name ?? '?'), 0, 2)) }}</span>
            <div class="msg__body">
              <div class="msg__head">
                <strong>{{ $daVelaro ? 'Equipe Velaro Suporte' : ($mensagem->author?->name ?? 'Minha loja') }}</strong>
                @if($daVelaro)
                  <span class="chip chip--ok chip--flat">Velaro</span>
                @else
                  <span class="chip chip--brand chip--flat">Minha loja</span>
                @endif
                <span class="msg__when">{{ $mensagem->created_at?->format('d/m/Y \à\s H:i') }}</span>
              </div>
              <p>{!! nl2br(e($mensagem->body)) !!}</p>

              @foreach($mensagem->attachments as $anexo)
                <div class="docfile" style="margin-top:10px">
                  <x-velaro.icon name="doc" />
                  <span><strong>{{ $anexo->original_name }}</strong>
                    <small>{{ $anexo->created_at?->format('d/m/Y \à\s H:i') }}
                      · {{ number_format($anexo->size_bytes / 1024, 0, ',', '.') }} KB</small></span>
                </div>
              @endforeach
            </div>
          </div>
        @empty
          <p class="muted">Este chamado ainda não tem mensagens visíveis.</p>
        @endforelse
      </div>

      {{-- O contrato de rotas do portal tem GET /portal/suporte/{code} e nenhuma
           rota de resposta: a thread é leitura. Responder continua acontecendo
           pelos canais abaixo até a rota existir. --}}
      <p class="notice notice--gold" style="margin-top:var(--space-4)">
        <x-velaro.icon name="info" /><span>Para complementar este atendimento, responda pelo WhatsApp ou pelo
        e-mail do suporte com o número <strong>{{ $chamado->code }}</strong> — ou
        <a class="link-gold" href="{{ route('portal.suporte.create') }}">abra um novo chamado</a>.</span></p>
    </div>

    <p class="notice notice--info"><x-velaro.icon name="shield" /><span>Você vê a conversa completa do seu
      atendimento. <strong>Observações internas</strong> da equipe Velaro existem no chamado, mas nunca são
      exibidas ao revendedor.</span></p>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card__head"><h2 class="title">Detalhes do chamado</h2></div>
      <div class="datarow"><span class="datarow__k">Protocolo</span>
        <span class="datarow__v"><span class="num">{{ $chamado->code }}</span></span></div>
      <div class="datarow"><span class="datarow__k">Status</span>
        <span class="datarow__v"><span class="chip {{ $status['chip'] }} chip--flat">{{ $status['rotulo'] }}</span></span></div>
      <div class="datarow"><span class="datarow__k">Prioridade</span>
        <span class="datarow__v"><span class="chip {{ $prioridade['chip'] }} chip--flat">{{ $prioridade['rotulo'] }}</span></span></div>
      <div class="datarow"><span class="datarow__k">Categoria</span>
        <span class="datarow__v">{{ $chamado->category }}</span></div>
      <div class="datarow"><span class="datarow__k">Assunto</span>
        <span class="datarow__v">{{ $chamado->subject }}</span></div>
      <div class="datarow"><span class="datarow__k">Pedido relacionado</span>
        <span class="datarow__v">
          @if($chamado->order)
            <a class="link-gold" href="{{ route('portal.pedidos.show', $chamado->order) }}">{{ $chamado->order->public_number }}</a>
          @else — @endif
        </span></div>
      <div class="datarow"><span class="datarow__k">Cliente final</span>
        <span class="datarow__v">{{ $chamado->customer?->name ?? '—' }}</span></div>
      <div class="datarow"><span class="datarow__k">Canal de abertura</span>
        <span class="datarow__v">{{ $chamado->channel ?? '—' }}</span></div>
      <div class="datarow"><span class="datarow__k">Aberto em</span>
        <span class="datarow__v num">{{ $chamado->created_at?->format('d/m/Y \à\s H:i') }}</span></div>
      <div class="datarow"><span class="datarow__k">Última atualização</span>
        <span class="datarow__v num">{{ $chamado->updated_at?->format('d/m/Y \à\s H:i') }}</span></div>
      <div class="datarow"><span class="datarow__k">Responsável na Velaro</span>
        <span class="datarow__v">{{ $chamado->assignee?->name ?? 'Equipe Velaro Suporte' }}</span></div>
    </div>

    <div class="card">
      <div class="card__head"><h2 class="title">Histórico de status</h2></div>
      <ul class="timeline">
        @foreach($historico as $indice => $evento)
          @php($rotulo = $rotulos[$evento->to_status] ?? ['rotulo' => $evento->to_status])
          <li class="tl {{ $indice === 0 ? 'tl--now' : 'tl--done' }}">
            <span class="tl__dot"></span>
            <span class="tl__body"><strong>{{ $rotulo['rotulo'] }}</strong>
              <span class="tl__desc">{{ $evento->channel ?? 'Velaro' }}</span></span>
            <span class="tl__when">{{ $evento->created_at?->format('d/m H:i') }}</span>
          </li>
        @endforeach
      </ul>
    </div>

    <div class="card">
      <div class="card__head"><h2 class="title">Anexos</h2></div>
      @forelse($anexos as $anexo)
        <div class="docfile">
          <x-velaro.icon name="doc" />
          <span><strong>{{ $anexo->original_name }}</strong>
            <small>{{ $anexo->created_at?->format('d/m/Y \à\s H:i') }}
              · {{ number_format($anexo->size_bytes / 1024, 0, ',', '.') }} KB</small></span>
        </div>
      @empty
        <p class="muted" style="margin:0">Nenhum anexo neste chamado.</p>
      @endforelse
    </div>

    @include('portal.suporte.partials.canais')
  </div>
</div>

</x-velaro.layouts.portal>
