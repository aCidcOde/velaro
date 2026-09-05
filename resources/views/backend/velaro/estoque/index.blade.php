{{--
[Modulo: resources/views/backend/velaro/estoque]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.4 — estoque: os 5 KPIs, a tabela por SKU com faixa de aros e a gaveta do item.
--}}
@inject('estoque', 'App\\Services\\Backend\\EstoqueService')
<x-velaro.layouts.master title="Estoque">

  <div class="page-head">
    <div>
      <h1 class="display-md">Estoque</h1>
      <p class="lede">Acompanhe disponibilidade dos produtos, saldos por tamanho, reservas e necessidade de reposição.</p>
    </div>
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <section class="grid g5" aria-label="Indicadores do estoque">
    @foreach($kpis as $kpi)
      @php($destino = $kpi['situacao'] ? route('backend.estoque.index', ['situacao' => $kpi['situacao']]) : null)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ number_format($kpi['valor'], 0, ',', '.') }}</div>
            @if($kpi['variacao'] !== null)
              <div class="kpi__delta kpi__delta--{{ $kpi['variacao'] > 0 ? 'up' : ($kpi['variacao'] < 0 ? 'down' : 'flat') }}">
                {{ $kpi['variacao'] > 0 ? '↑' : ($kpi['variacao'] < 0 ? '↓' : '') }}
                {{ \App\Support\ValorPtBr::percentual(abs($kpi['variacao'])) }} vs. mês anterior
              </div>
            @elseif($destino)
              <a class="kpi__delta" href="{{ $destino }}">Ver na lista →</a>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <div class="split split--wide">
    <div class="stack">
      <form class="filters" method="GET" action="{{ route('backend.estoque.index') }}">
        <label class="input-shell" style="flex:1;min-width:240px">
          <x-velaro.icon name="search" class="ic input-shell__icon" />
          <input class="input input--compact" type="search" name="busca" value="{{ $filtros['busca'] }}"
                 placeholder="Buscar por SKU, produto ou coleção…" aria-label="Buscar por SKU, produto ou coleção">
        </label>
        <label class="fbox">
          <span>Categoria</span>
          <select class="select select--compact" name="categoria">
            <option value="">Todas</option>
            @foreach($opcoes['categorias'] as $categoria)
              <option value="{{ $categoria->id }}" @selected($filtros['categoria'] == $categoria->id)>{{ $categoria->name }}</option>
            @endforeach
          </select>
        </label>
        <label class="fbox">
          <span>Status</span>
          <select class="select select--compact" name="situacao">
            <option value="">Todos</option>
            @foreach($opcoes['situacoes'] as $situacao)
              <option value="{{ $situacao }}" @selected($filtros['situacao'] === $situacao)>{{ trans('stock.item_status.'.$situacao, [], 'pt_BR') }}</option>
            @endforeach
          </select>
        </label>
        <label class="fbox">
          <span>Local</span>
          <select class="select select--compact" name="local">
            <option value="">Todos</option>
            @foreach($opcoes['locais'] as $local)
              <option value="{{ $local->id }}" @selected($filtros['local'] == $local->id)>{{ $local->name }}</option>
            @endforeach
          </select>
        </label>
        <div class="row row--wrap push">
          <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtros</button>
          {{-- Exportacao e da tela 3.9 (`velaro.reports.export`); aqui o botao leva para la. --}}
          <a class="btn btn--secondary btn--sm" href="{{ route('backend.relatorios.produtos') }}"><x-velaro.icon name="download" /> Exportar</a>
          @if($podeAjustar || $podeSolicitarProducao)
            <a class="btn btn--primary btn--sm" href="{{ route('backend.estoque.movimentacao') }}">+ Nova movimentação</a>
          @endif
        </div>
      </form>

      <div class="card">
        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>SKU</th><th>Produto</th><th>Coleção</th><th>Material</th><th>Tamanhos</th>
                <th class="cell-num">Estoque atual</th><th class="cell-num">Reservado</th><th class="cell-num">Estoque mínimo</th>
                <th>Reposição</th><th>Status</th><th class="cell-num">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse($itens as $produto)
                @php($onHand = (int) $produto->getAttribute('stock_on_hand'))
                @php($reserved = (int) $produto->getAttribute('stock_reserved'))
                @php($minimum = (int) $produto->getAttribute('stock_minimum'))
                @php($situacao = $estoque->situacao($onHand, $reserved, $minimum))
                @php($reposicao = $estoque->reposicao($onHand, $minimum))
                <tr>
                  <td><code>{{ $produto->sku ?? '—' }}</code></td>
                  <td><strong style="color:var(--ink)">{{ $produto->name }}</strong></td>
                  <td>{{ $produto->collection?->name ?? '—' }}</td>
                  <td>{{ $produto->material?->name ?? '—' }}</td>
                  <td><span class="num">{{ $estoque->faixaDeAros($produto->variants) ?? '—' }}</span></td>
                  <td class="cell-num"><span class="cell-strong num">{{ $onHand }}</span></td>
                  <td class="cell-num"><span class="num">{{ $reserved }}</span></td>
                  <td class="cell-num"><span class="num">{{ $minimum }}</span></td>
                  <td><span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_REPOSICAO[$reposicao] }} chip--flat">{{ trans('stock.restock.'.$reposicao, [], 'pt_BR') }}</span></td>
                  <td>
                    <span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_SITUACAO[$situacao] }}">
                      {{ trans('stock.item_status.'.$situacao, [], 'pt_BR') }}
                    </span>
                  </td>
                  <td class="cell-num">
                    <a class="btn btn--secondary btn--sm"
                       href="{{ route('backend.estoque.index', array_merge(request()->query(), ['produto' => $produto->id])) }}">
                      <x-velaro.icon name="eye" /> Ver
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="11"><p class="lede" style="margin:0">Nenhum item com esses filtros.</p></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($itens->hasPages())
          <div class="tfoot"><div class="pagination">{{ $itens->links() }}</div></div>
        @endif
      </div>

      <p class="notice notice--info">
        <x-velaro.icon name="shield" />
        <span>O controle de estoque físico principal pertence à Velaro. O Portal do Parceiro Premium <strong>apenas consulta</strong> disponibilidade (Anexo I §6).</span>
      </p>
    </div>

    @if($gaveta)
      @include('backend.velaro.estoque._gaveta', $gaveta + ['podeAjustar' => $podeAjustar, 'podeSolicitarProducao' => $podeSolicitarProducao, 'locais' => $opcoes['locais']])
    @else
      <aside class="drawer">
        <header class="drawer__head"><div><h2 class="title">Nenhum item selecionado</h2></div></header>
        <div class="drawer__body"><p class="lede" style="font-size:var(--text-sm)">Escolha um produto na tabela para ver o saldo por tamanho, as reservas e as últimas movimentações.</p></div>
      </aside>
    @endif
  </div>

</x-velaro.layouts.master>
