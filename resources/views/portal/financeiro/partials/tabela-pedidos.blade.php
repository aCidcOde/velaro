{{--
[Modulo: resources/views/portal/financeiro/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tabela de pedidos do financeiro: custo Velaro, lote, prazo, status do pagamento e a NF-e do lote.
--}}
<div class="table-scroll" tabindex="0" aria-label="Pedidos e o custo Velaro de cada um">
  <table class="table">
    <thead>
      <tr>
        <th>Pedido</th>
        <th>Cliente final</th>
        <th>Data do pedido</th>
        <th class="cell-num">Valor custo Velaro</th>
        <th>Lote</th>
        <th>Prazo máximo para pagamento</th>
        <th>Status do pagamento</th>
        <th>NF-e</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($linhasDePedido as $linha)
        <tr>
          <td>
            <strong style="color:var(--ink)">{{ $linha['numero'] }}</strong>
            @if($linha['referencia'])<br><small class="muted">Ref.: {{ $linha['referencia'] }}</small>@endif
          </td>
          <td>
            <div class="row" style="gap:8px">
              <span class="avatar avatar--sm" style="background:var(--color-gold-100);color:var(--color-gold-800)">{{ $linha['iniciais'] }}</span>{{ $linha['cliente'] }}
            </div>
          </td>
          <td>{{ $linha['data'] }}<br><small class="muted">{{ $linha['hora'] }}</small></td>
          <td class="cell-num"><span class="cell-strong num">{{ $linha['valor'] }}</span></td>
          <td><span class="num">{{ $linha['lote'] }}</span></td>
          <td>{{ $linha['prazo'] }}@if($linha['prazo_hora'])<br><small class="muted">{{ $linha['prazo_hora'] }}</small>@endif</td>
          <td><span class="chip {{ $linha['status']['classe'] }}">{{ $linha['status']['rotulo'] }}</span></td>
          <td>
            @if($linha['nota'])
              <a class="link-gold" href="{{ $linha['nota']['url'] }}"
                 aria-label="Baixar a {{ $linha['nota']['numero'] }}, do lote deste pedido">Baixar NF</a>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td><a class="link-gold" href="{{ $linha['url'] }}" aria-label="Abrir o pedido {{ $linha['numero'] }}">⋯</a></td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="muted">Nenhum pedido neste recorte.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
