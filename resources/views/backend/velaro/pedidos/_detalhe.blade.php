{{--
[Modulo: resources/views/backend/velaro/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Coluna central da tela 3.6: identidade do pedido, itens com gravacao, entrega, observacoes e historico.
--}}
@php($voltar = $voltar ?? false)

<div class="card">
  <div class="spread">
    <div>
      @if($voltar)
        <a class="link-gold" href="{{ route('backend.pedidos.index') }}">← Voltar para pedidos</a>
      @endif
      <h2 class="display-sm" style="margin-top:6px">Pedido #{{ $pedido->public_number }}</h2>
      <p class="lede" style="font-size:var(--text-sm)">Data do pedido: {{ $pedido->created_at?->format('d/m/Y \à\s H:i') }}</p>
    </div>
    <span class="chip {{ $operacional['chip'] }}">{{ $operacional['rotulo'] }}</span>
    {{-- Regra 2: os dois status sao independentes, entao sao dois chips, nunca um derivado do outro. --}}
    <span class="chip {{ $pagamento['chip'] }} chip--flat">Pagamento: {{ $pagamento['rotulo'] }}</span>
    <a class="btn btn--secondary btn--sm" href="{{ route('backend.pedidos.show', $pedido) }}">
      <x-velaro.icon name="eye" /> Mais ações
    </a>
  </div>

  <div class="identbar" style="margin-top:var(--space-4)">
    <div class="identcell"><span><small>Cliente</small><strong>{{ $pedido->customer?->name ?? '—' }}@if($pedido->customer?->document) · {{ $pedido->customer->document }}@endif</strong></span></div>
    <div class="identcell"><span><small>Revendedor</small><strong>{{ $pedido->reseller?->trade_name ?? '—' }}@if($pedido->reseller?->code) · {{ $pedido->reseller->code }}@endif</strong></span></div>
    <div class="identcell"><span><small>Total do pedido</small><strong>{{ \App\Support\ValorPtBr::moeda((float) $pedido->total_amount) }}</strong></span></div>
    <div class="identcell"><span><small>Forma de pagamento</small><strong>{{ $formaDePagamento ?? '—' }}</strong></span></div>
    <div class="identcell"><span><small>Lote</small><strong>{{ $pedido->batch?->code ?? '—' }}</strong></span></div>
    @if($canal)
      <div class="identcell"><span><small>Canal de origem</small><strong>{{ $canal }}</strong></span></div>
    @endif
  </div>
</div>

<div class="card">
  <div class="card__head"><h2 class="title">Itens do pedido ({{ $pedido->items->count() }})</h2></div>
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr>
          <th>Produto</th><th>Código</th><th>Especificações</th>
          <th class="cell-num">Qtd</th><th class="cell-num">Valor unit.</th><th class="cell-num">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pedido->items as $item)
          <tr>
            <td>
              <div class="row" style="gap:10px">
                <span class="thumb"><x-velaro.icon name="ring" /></span>
                <span>
                  <strong style="color:var(--ink)">{{ $item->product?->name ?? 'Item removido do catálogo' }}</strong><br>
                  <small class="muted">{{ $item->product?->material?->name ?? '—' }}</small>
                </span>
              </div>
            </td>
            <td><code>{{ $item->variant?->sku ?? $item->product?->sku ?? '—' }}</code></td>
            <td>
              Aro: {{ $item->variant?->getAttribute('ring_size') ?? '—' }}
              @if($item->engraving?->enabled)
                <br><small class="muted">Gravação: {{ $item->engraving->text }}</small>
              @endif
            </td>
            <td class="cell-num"><span class="num">{{ $item->quantity }}</span></td>
            <td class="cell-num"><span class="num">{{ \App\Support\ValorPtBr::moeda((float) $item->unit_price) }}</span></td>
            <td class="cell-num"><span class="cell-strong num">{{ \App\Support\ValorPtBr::moeda((float) $item->total_price) }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6"><p class="lede" style="margin:0">Este pedido não tem itens.</p></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{-- As quatro linhas de valor da secao 5: subtotal, gravacao, desconto e total. --}}
  <div class="tfoot">
    <div class="spread"><strong>Subtotal</strong><span class="num">{{ \App\Support\ValorPtBr::moeda((float) $pedido->subtotal_amount) }}</span></div>
    <div class="spread"><strong>Gravação</strong><span class="num">{{ \App\Support\ValorPtBr::moeda((float) $pedido->engraving_amount) }}</span></div>
    <div class="spread"><strong>Desconto</strong><span class="num">− {{ \App\Support\ValorPtBr::moeda((float) $pedido->discount_amount) }}</span></div>
    <div class="spread"><strong>Total</strong><span class="money money--action">{{ \App\Support\ValorPtBr::moeda((float) $pedido->total_amount) }}</span></div>
  </div>
</div>

<div class="split" style="--gcols:1fr 1fr">
  <div class="card">
    <div class="card__head"><h2 class="title">Endereço de entrega (loja do revendedor)</h2></div>
    @if($entrega)
      <p class="lede" style="font-size:var(--text-sm)">
        {{ $entrega['nome'] }}<br>{{ $entrega['linha1'] }}<br>{{ $entrega['linha2'] }}
      </p>
    @else
      <p class="lede" style="font-size:var(--text-sm)">Pedido sem revendedor vinculado.</p>
    @endif
  </div>
  <div class="card">
    <div class="card__head"><h2 class="title">Observações</h2></div>
    <div class="field"><span class="input-fake input-fake--area">{{ $pedido->notes ?: 'Sem observações.' }}</span></div>
  </div>
</div>

<div class="card">
  <div class="card__head"><h2 class="title">Histórico de atualizações</h2></div>
  @forelse($historico as $evento)
    <div class="datarow">
      <span class="datarow__k">
        <x-velaro.icon name="clock" />
        <span>
          <strong style="display:block;color:var(--ink)">{{ trans('order.'.($evento->scope === \App\Models\OrderStatusEvent::SCOPE_PAYMENT ? 'payment_status' : 'operational_status').'.'.$evento->to_status, [], 'pt_BR') }}</strong>
          <small>{{ $evento->note ?? '—' }}@if($evento->actor) · {{ $evento->actor->name }}@endif</small>
        </span>
      </span>
      <span class="datarow__v"><small class="muted">{{ $evento->created_at?->format('d/m H:i') }}</small></span>
    </div>
  @empty
    <p class="lede" style="font-size:var(--text-sm)">Nenhuma atualização registrada.</p>
  @endforelse
</div>
