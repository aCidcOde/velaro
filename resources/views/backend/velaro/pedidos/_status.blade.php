{{--
[Modulo: resources/views/backend/velaro/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Coluna direita da tela 3.6: as 7 etapas, o aviso de chegada, a confirmacao de retirada e as notificacoes.
--}}
<div class="card">
  <div class="card__head"><h2 class="title">Status do pedido</h2></div>
  {{-- Regra 1: sete etapas, na ordem canonica da cadeia operacional. --}}
  <ul class="timeline">
    @foreach($linhaDoTempo as $etapa)
      <li class="tl tl--{{ $etapa['estado'] }}">
        <span class="tl__dot"></span>
        <span class="tl__body">
          <strong>{{ $etapa['rotulo'] }}</strong>
          {{-- So os dois ultimos degraus tem descricao de espera, e e a do
               prototipo. Repetir "aguardando chegada na loja" num pedido que
               ainda nem saiu da producao diria ao operador a coisa errada. --}}
          @if($etapa['estado'] === 'todo' && $etapa['chave'] === \App\Models\Order::OPERATIONAL_STATUS_READY_FOR_PICKUP)
            <span class="tl__desc">Aguardando chegada na loja</span>
          @elseif($etapa['estado'] === 'todo' && $etapa['chave'] === \App\Models\Order::OPERATIONAL_STATUS_PICKED_UP)
            <span class="tl__desc">Aguardando confirmação</span>
          @endif
        </span>
        <span class="tl__when">{{ $etapa['em']?->format('d/m H:i') ?? '—' }}</span>
      </li>
    @endforeach
  </ul>
</div>

<p class="notice notice--info">
  <x-velaro.icon name="bell" />
  <span>Quando o pedido chegar na loja, o revendedor e o cliente serão notificados automaticamente que o pedido está pronto para retirada.</span>
</p>

<div class="card">
  <div class="card__head"><h2 class="title">Confirmação de retirada</h2></div>
  <p class="lede" style="font-size:var(--text-sm)">Confirme abaixo quando o pedido for retirado pelo cliente na loja.</p>

  @if($pedido->picked_up_at)
    <div class="datarow">
      <span class="datarow__k"><x-velaro.icon name="check" /><span><strong style="display:block;color:var(--ink)">Retirado</strong><small>{{ $pedido->picked_up_by_name ?? '—' }}@if($pedido->picked_up_by_document) · {{ $pedido->picked_up_by_document }}@endif</small></span></span>
      <span class="datarow__v"><small class="muted">{{ $pedido->picked_up_at->format('d/m/Y H:i') }}</small></span>
    </div>
  @else
    {{-- Regra 3: a confirmacao existe por lote inteiro E por pedido. As duas
         acoes estao implementadas no service, com log e evento de timeline, mas
         `routes/velaro.php` ainda nao mapeia o POST de nenhuma das duas — e o
         arquivo de rotas esta fora do territorio desta entrega. Os botoes
         mostram o estado real (permissao e elegibilidade) em vez de postar para
         uma rota que nao existe. --}}
    {{-- O design system nao estiliza `.btn:disabled` (so `.input`/`.select`),
         entao o estado desligado vem daqui — com a mesma opacidade e o mesmo
         cursor que ele da aos campos. Sem isso os dois botoes pareceriam
         clicaveis e nao fariam nada. --}}
    @php($inerte = 'opacity:.72;cursor:not-allowed')
    <button class="btn btn--secondary" type="button" disabled style="{{ $inerte }}"
            aria-disabled="true"
            title="{{ $podeConfirmarRetiradaDoLote ? 'Rota de confirmação ainda não mapeada' : 'Exige a permissão velaro.orders.confirm_batch_pickup' }}">
      <x-velaro.icon name="box" /> Confirmar retirada do lote inteiro
    </button>
    <button class="btn btn--primary" type="button" disabled style="{{ $inerte }}"
            aria-disabled="true"
            title="{{ $podeConfirmarRetirada ? 'Rota de confirmação ainda não mapeada' : 'Exige a permissão velaro.orders.confirm_pickup' }}">
      <x-velaro.icon name="check" /> Confirmar retirada por pedido
    </button>
    <p class="notice notice--info">
      <x-velaro.icon name="info" />
      <span>Rota de confirmação pendente de mapeamento em <code>routes/velaro.php</code>. A regra, o evento de timeline e o registro em <code>audit_logs</code> já estão implementados e cobertos por teste.</span>
    </p>
  @endif

  @if($proximoStatus && $podeAtualizarStatus)
    <p class="notice notice--info">
      <x-velaro.icon name="truck" />
      <span>Próxima etapa da esteira: <strong>{{ $proximoStatus['rotulo'] }}</strong>.</span>
    </p>
  @endif
</div>

<div class="card">
  <div class="card__head"><h2 class="title">Notificações enviadas</h2></div>
  @forelse($notificacoes as $aviso)
    <div class="datarow">
      <span class="datarow__k">
        <x-velaro.icon name="mail" />
        <span>
          <strong style="display:block;color:var(--ink)">
            {{ $aviso->recipient_type === \App\Models\NotificationLog::RECIPIENT_TYPE_RESELLER ? 'Revendedor' : 'Cliente' }}
            ({{ $aviso->recipient_type === \App\Models\NotificationLog::RECIPIENT_TYPE_RESELLER ? ($pedido->reseller?->trade_name ?? '—') : ($pedido->customer?->name ?? '—') }})
          </strong>
          <small>{{ $aviso->sent_at ? 'Enviado em '.$aviso->sent_at->format('d/m/Y \à\s H:i') : 'Na fila de envio' }}</small>
        </span>
      </span>
      <span class="datarow__v">
        <span class="chip chip--{{ $aviso->status === \App\Models\NotificationLog::STATUS_SENT ? 'ok' : ($aviso->status === \App\Models\NotificationLog::STATUS_FAILED ? 'danger' : 'warn') }} chip--flat">
          {{ trans('notification.status.'.$aviso->status, [], 'pt_BR') }}
        </span>
      </span>
    </div>
  @empty
    <p class="lede" style="font-size:var(--text-sm)">Nenhum aviso enviado para este pedido ainda.</p>
  @endforelse
</div>
