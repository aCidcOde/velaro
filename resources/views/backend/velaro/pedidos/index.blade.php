{{--
[Modulo: resources/views/backend/velaro/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.6 — pedidos: os 5 KPIs/abas, a lista filtrada e o detalhe do pedido aberto ao lado.
--}}
<x-velaro.layouts.master title="Pedidos">

  <div class="page-head">
    <div>
      <h1 class="display-md">Pedidos</h1>
      <p class="lede">Acompanhe, gerencie e visualize todos os pedidos realizados.</p>
    </div>
    <div class="row row--wrap">
      {{-- A exportacao mora na tela 3.9, que e quem tem a permissao
           `velaro.reports.export`; aqui o botao leva para la em vez de abrir uma
           segunda geracao de arquivo dentro de Pedidos. --}}
      <a class="btn btn--secondary btn--sm" href="{{ route('backend.relatorios.vendas') }}">
        <x-velaro.icon name="download" /> Exportar
      </a>
      @if($podeCriar)
        <a class="btn btn--primary btn--sm" href="{{ route('backend.pedidos.create') }}">+ Novo pedido</a>
      @endif
    </div>
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  {{-- Os cinco cartoes sao as abas do prototipo: cada um leva para a lista ja
       recortada pelo proprio numero, senao o cartao e a lista discordariam. --}}
  <section class="grid g5" aria-label="Situação dos pedidos">
    @foreach($kpis as $kpi)
      <a class="card card--compact" href="{{ route('backend.pedidos.index', ['aba' => $kpi['aba'], 'periodo' => 0]) }}"
         @if($filtros['aba'] === $kpi['aba']) aria-current="page" @endif>
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ number_format($kpi['valor'], 0, ',', '.') }}</div>
          </div>
        </div>
      </a>
    @endforeach
  </section>

  <div class="split3">
    <div class="stack">
      <form class="filters" method="GET" action="{{ route('backend.pedidos.index') }}">
        <input type="hidden" name="aba" value="{{ $filtros['aba'] }}">
        <label class="input-shell" style="flex:1;min-width:240px">
          <x-velaro.icon name="search" class="ic input-shell__icon" />
          <input class="input input--compact" type="search" name="busca" value="{{ $filtros['busca'] }}"
                 placeholder="Buscar pedido, cliente ou código…" aria-label="Buscar pedido, cliente ou código">
        </label>
        <label class="fbox">
          <span>Status</span>
          <select class="select select--compact" name="status">
            <option value="">Todos</option>
            @foreach($statusDisponiveis as $opcao)
              <option value="{{ $opcao['valor'] }}" @selected($filtros['status'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
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
          <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
        </div>
      </form>

      <div class="stacklist">
        @forelse($pedidos as $item)
          @php($chip = $chips[$item->operational_status] ?? ['rotulo' => $item->operational_status, 'chip' => 'chip--neutral'])
          <a class="pickitem {{ $selecionado?->is($item) ? 'is-on' : '' }}"
             href="{{ route('backend.pedidos.index', array_merge(request()->query(), ['pedido' => $item->public_number])) }}">
            <div class="pickitem__top">
              <strong>#{{ $item->public_number }}</strong>
              <span class="chip {{ $chip['chip'] }} chip--flat">{{ $chip['rotulo'] }}</span>
            </div>
            <small>{{ $item->created_at?->format('d/m/Y') }}</small>
            <strong style="font-size:var(--text-body)">{{ $item->customer?->name ?? 'Sem cliente final' }}</strong>
            {{-- Regra 3.2: toda linha do Master mostra o revendedor responsavel. --}}
            <small>Revendedor: {{ $item->reseller?->trade_name ?? '—' }}</small>
            <div class="pickitem__top" style="margin-top:6px">
              <span class="cell-strong num">{{ \App\Support\ValorPtBr::moeda((float) $item->total_amount) }}</span>
              <small>{{ $item->items_count }} {{ $item->items_count === 1 ? 'item' : 'itens' }}</small>
            </div>
          </a>
        @empty
          <p class="notice notice--info"><x-velaro.icon name="info" /><span>Nenhum pedido com esses filtros.</span></p>
        @endforelse
      </div>

      @if($pedidos->hasPages())
        <div class="pagination">{{ $pedidos->links() }}</div>
      @endif
    </div>

    @if($detalhe)
      <div class="stack">
        @include('backend.velaro.pedidos._detalhe', $detalhe)
      </div>
      <div class="stack">
        @include('backend.velaro.pedidos._status', $detalhe)
      </div>
    @else
      <div class="stack">
        <div class="card">
          <div class="card__head"><h2 class="title">Nenhum pedido selecionado</h2></div>
          <p class="lede" style="font-size:var(--text-sm)">Escolha um pedido na lista para ver os itens, os dois status e a linha do tempo.</p>
        </div>
      </div>
      <div class="stack"></div>
    @endif
  </div>

</x-velaro.layouts.master>
