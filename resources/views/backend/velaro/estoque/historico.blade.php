{{--
[Modulo: resources/views/backend/velaro/estoque]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.4 (52b) — extrato do item: movimentacoes com antes e depois, e as reservas em aberto.
--}}
<x-velaro.layouts.master :title="'Movimentações · '.$produto->name">

  <a class="link-gold" href="{{ route('backend.estoque.index', ['produto' => $produto->id]) }}">← Voltar para o estoque</a>

  <div class="page-head">
    <div>
      <h1 class="display-md">Movimentações do item</h1>
      <p class="lede">Extrato completo do SKU {{ $produto->sku ?? $variante->sku }} — {{ $produto->name }}.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary btn--sm" href="{{ route('backend.relatorios.produtos') }}"><x-velaro.icon name="download" /> Exportar</a>
      @if($podeAjustar)
        <a class="btn btn--primary btn--sm" href="{{ route('backend.estoque.movimentacao', ['variante' => $variante->id]) }}">
          <x-velaro.icon name="plus" /> Nova movimentação
        </a>
      @endif
    </div>
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <div class="card">
    <div class="identbar">
      <div class="identcell"><span><small>SKU</small><strong>{{ $produto->sku ?? '—' }}</strong></span></div>
      <div class="identcell"><span><small>Produto</small><strong>{{ $produto->name }}</strong></span></div>
      <div class="identcell"><span><small>Estoque atual</small><strong>{{ $resumo['onHand'] }} unidades</strong></span></div>
      <div class="identcell"><span><small>Reservado</small><strong>{{ $resumo['reserved'] }} unidades</strong></span></div>
      <div class="identcell"><span><small>Disponível</small><strong>{{ $resumo['available'] }} unidades</strong></span></div>
      <div class="identcell"><span><small>Estoque mínimo</small><strong>{{ $resumo['minimum'] }} unidades</strong></span></div>
    </div>
  </div>

  <section class="grid g4" aria-label="Indicadores do extrato">
    @foreach($kpis as $kpi)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ number_format($kpi['valor'], 0, ',', '.') }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <div class="split split--wide">
    <div class="stack">
      <form class="filters" method="GET" action="{{ route('backend.estoque.historico', ['variant' => $variante->id]) }}">
        <label class="input-shell" style="flex:1;min-width:240px">
          <x-velaro.icon name="search" class="ic input-shell__icon" />
          <input class="input input--compact" type="search" name="busca" value="{{ $filtros['busca'] }}"
                 placeholder="Buscar por documento, pedido ou responsável…" aria-label="Buscar por documento, pedido ou responsável">
        </label>
        <label class="fbox">
          <span>Tipo</span>
          <select class="select select--compact" name="tipo">
            <option value="">Todos</option>
            @foreach($opcoes['tipos'] as $opcaoDeTipo)
              {{-- Producao nao gera linha em `stock_movements` — ela abre ordem
                   em `production_requests` e a peca so entra no cofre depois,
                   como entrada. Oferecer o tipo aqui seria um filtro que nunca
                   acha nada. --}}
              @continue($opcaoDeTipo === \App\Models\StockMovement::TYPE_PRODUCTION)
              <option value="{{ $opcaoDeTipo }}" @selected($filtros['tipo'] === $opcaoDeTipo)>{{ trans('stock.movement_type.'.$opcaoDeTipo, [], 'pt_BR') }}</option>
            @endforeach
          </select>
        </label>
        <label class="fbox">
          <span>Aro</span>
          <select class="select select--compact" name="aro">
            <option value="">Todos</option>
            @foreach($resumo['variantes'] as $irma)
              <option value="{{ $irma->getAttribute('ring_size') }}" @selected($filtros['aro'] === (string) $irma->getAttribute('ring_size'))>
                {{ $irma->getAttribute('ring_size') }}
              </option>
            @endforeach
          </select>
        </label>
        <label class="fbox">
          <span>Período</span>
          <select class="select select--compact" name="periodo">
            @foreach([7 => 'Últimos 7 dias', 30 => 'Últimos 30 dias', 90 => 'Últimos 90 dias', 0 => 'Todo o período'] as $dias => $rotulo)
              <option value="{{ $dias }}" @selected($filtros['periodo'] === $dias)>{{ $rotulo }}</option>
            @endforeach
          </select>
        </label>
        <div class="row row--wrap push">
          <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtros</button>
        </div>
      </form>

      <div class="card">
        <div class="densetable">
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th class="cell-nowrap">Data e hora</th><th>Tipo</th><th class="cell-num">Aro</th>
                  <th class="cell-num">Quantidade</th><th class="cell-num">Antes</th><th class="cell-num">Depois</th>
                  <th>Origem / documento</th><th>Responsável</th><th>Motivo</th>
                </tr>
              </thead>
              <tbody>
                @forelse($movimentacoes as $movimento)
                  <tr>
                    <td class="cell-nowrap"><span class="num">{{ $movimento->created_at?->format('d/m/Y H:i') }}</span></td>
                    <td>
                      <span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_MOVIMENTACAO[$movimento->type] ?? 'chip--neutral' }} chip--flat">
                        {{ trans('stock.movement_type.'.$movimento->type, [], 'pt_BR') }}
                      </span>
                    </td>
                    <td class="cell-num"><span class="num">{{ $movimento->stockItem?->productVariant?->getAttribute('ring_size') ?? '—' }}</span></td>
                    <td class="cell-num">
                      <span class="cell-strong num">{{ $movimento->after >= $movimento->before ? '+' : '−' }}{{ abs($movimento->after - $movimento->before) }}</span>
                    </td>
                    <td class="cell-num"><span class="num">{{ $movimento->before }}</span></td>
                    <td class="cell-num"><span class="num">{{ $movimento->after }}</span></td>
                    <td>
                      @if($movimento->order)
                        <a class="link-gold" href="{{ route('backend.pedidos.show', $movimento->order) }}">#{{ $movimento->order->public_number }}</a>
                      @else
                        <span class="muted">—</span>
                      @endif
                    </td>
                    <td>{{ $movimento->actor?->name ?? 'Sistema' }}</td>
                    <td><small>{{ $movimento->reason ?? '—' }}</small></td>
                  </tr>
                @empty
                  <tr><td colspan="9"><p class="lede" style="margin:0">Nenhuma movimentação com esses filtros.</p></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($movimentacoes->hasPages())
            <div class="tfoot"><div class="pagination">{{ $movimentacoes->links() }}</div></div>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Reservas em aberto ({{ $reservas->sum('qty') }} unidades)</h2></div>
        <div class="densetable">
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th>Pedido</th><th>Revendedor</th><th>Cliente final</th><th class="cell-num">Aro</th>
                  <th class="cell-num">Qtd reservada</th><th>Reservado em</th><th>Previsão de expedição</th><th>Status do pedido</th>
                </tr>
              </thead>
              <tbody>
                @forelse($reservas as $reserva)
                  <tr>
                    <td>
                      @if($reserva->order)
                        <a class="link-gold" href="{{ route('backend.pedidos.show', $reserva->order) }}">#{{ $reserva->order->public_number }}</a>
                      @else
                        <span class="muted">—</span>
                      @endif
                    </td>
                    {{-- Regra 3.2: o revendedor responsavel aparece em toda linha do Master. --}}
                    <td>{{ $reserva->order?->reseller?->trade_name ?? '—' }}</td>
                    <td>{{ $reserva->order?->customer?->name ?? '—' }}</td>
                    <td class="cell-num"><span class="num">{{ $reserva->stockItem?->productVariant?->getAttribute('ring_size') ?? '—' }}</span></td>
                    <td class="cell-num"><span class="cell-strong num">{{ $reserva->qty }}</span></td>
                    <td><span class="num">{{ $reserva->created_at?->format('d/m/Y H:i') }}</span></td>
                    <td><span class="num">{{ $reserva->order?->getAttribute('expected_at')?->format('d/m/Y') ?? '—' }}</span></td>
                    <td>
                      <span class="chip chip--flat">
                        {{ $reserva->order ? trans('order.operational_status.'.$reserva->order->operational_status, [], 'pt_BR') : '—' }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8"><p class="lede" style="margin:0">Nenhuma peça deste item está reservada.</p></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="tfoot"><div class="spread"><strong>Total reservado</strong><span class="num">{{ $reservas->sum('qty') }} unidades</span></div></div>
        </div>
      </div>

      <p class="notice notice--info">
        <x-velaro.icon name="shield" />
        <span>Toda linha do extrato guarda <code>before</code> e <code>after</code>. É essa dupla que permite auditar um ajuste manual sem depender do saldo atual (doc 3-4, regra 3).</span>
      </p>
    </div>

    <div class="stack">
      <div class="card">
        <div class="card__head"><h2 class="title">Item</h2>
          <span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_SITUACAO[$resumo['situacao']] }}">{{ trans('stock.item_status.'.$resumo['situacao'], [], 'pt_BR') }}</span>
        </div>
        <div class="prod__img" style="height:140px">
          @if($resumo['capa'])
            <img src="{{ $resumo['capa']['src'] }}" alt="{{ $resumo['capa']['alt'] }}" loading="lazy" style="width:100%;height:100%;object-fit:contain">
          @else
            <x-velaro.ring :alt="$produto->name" style="width:100%;height:100%;object-fit:contain" />
          @endif
        </div>
        <div class="datarow"><span class="datarow__k"><x-velaro.icon name="tag" /> SKU</span><span class="datarow__v">{{ $produto->sku ?? '—' }}</span></div>
        <div class="datarow"><span class="datarow__k"><x-velaro.icon name="sparkle" /> Coleção</span><span class="datarow__v">{{ $produto->collection?->name ?? '—' }}</span></div>
        <div class="datarow"><span class="datarow__k"><x-velaro.icon name="diamond" /> Material</span><span class="datarow__v">{{ $produto->material?->name ?? '—' }}</span></div>
        <div class="datarow"><span class="datarow__k"><x-velaro.icon name="box" /> Local</span><span class="datarow__v">{{ $resumo['local']?->name ?? '—' }}</span></div>
        <div class="datarow"><span class="datarow__k"><x-velaro.icon name="ring" /> Aro aberto</span><span class="datarow__v">{{ $variante->getAttribute('ring_size') }} · {{ $variante->sku }}</span></div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Estoque por tamanho</h2></div>
        <div class="table-scroll">
          <table class="table">
            <thead><tr><th>Tamanho</th><th class="cell-num">Atual</th><th class="cell-num">Reservado</th><th class="cell-num">Disponível</th></tr></thead>
            <tbody>
              @forelse($resumo['porFaixa'] as $faixa)
                <tr>
                  <td><span class="num">{{ $faixa['rotulo'] }}</span></td>
                  <td class="cell-num"><span class="num">{{ $faixa['onHand'] }}</span></td>
                  <td class="cell-num"><span class="num">{{ $faixa['reserved'] }}</span></td>
                  <td class="cell-num"><span class="num">{{ $faixa['available'] }}</span></td>
                </tr>
              @empty
                <tr><td colspan="4"><small class="muted">Sem aro cadastrado.</small></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</x-velaro.layouts.master>
