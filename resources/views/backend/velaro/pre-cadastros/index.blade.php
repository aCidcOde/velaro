{{--
[Modulo: resources/views/backend/velaro/pre-cadastros]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Tela 3.11 — fila de solicitacoes de lojista: KPIs, filtros e a tabela com o resultado da triagem.
--}}
<x-velaro.layouts.master title="Solicitações pré-cadastro">

  <div class="page-head">
    <div>
      <h1 class="display-md">Solicitações pré-cadastro</h1>
      <p class="lede">Acompanhe solicitações recebidas e valide novos revendedores.</p>
    </div>
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  {{-- Cada KPI abre a lista já filtrada e com `periodo=0`, para o número do cartão
       e a lista que ele abre nunca discordarem. --}}
  <section class="grid g5" aria-label="Indicadores das solicitações">
    @foreach($kpis as $kpi)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ $kpi['valor'] }}</div>
            @if($kpi['filtro'] !== [])
              <a class="kpi__delta" href="{{ route('backend.pre-cadastros.index', $kpi['filtro']) }}">Ver na fila →</a>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <form class="filters" method="GET" action="{{ route('backend.pre-cadastros.index') }}">
    <label class="fsearch">
      <x-velaro.icon name="search" />
      <input class="input input--bare" type="search" name="busca" value="{{ $filtros['busca'] }}"
             placeholder="Buscar empresa ou responsável…" aria-label="Buscar empresa ou responsável">
    </label>
    <label class="fbox">
      <span>Status</span>
      <select class="select select--compact" name="status">
        <option value="">Todos</option>
        <option value="{{ \App\Models\Reseller::STATUS_PENDING }}" @selected($filtros['status'] === \App\Models\Reseller::STATUS_PENDING)>Aguardando decisão</option>
        <option value="{{ \App\Models\Reseller::STATUS_AWAITING_INFO }}" @selected($filtros['status'] === \App\Models\Reseller::STATUS_AWAITING_INFO)>Aguardando informações</option>
      </select>
    </label>
    <label class="fbox">
      <span>Período</span>
      <select class="select select--compact" name="periodo">
        @foreach([30 => 'Últimos 30 dias', 90 => 'Últimos 90 dias', 0 => 'Todo o histórico'] as $dias => $rotulo)
          <option value="{{ $dias }}" @selected((int) $filtros['periodo'] === $dias)>{{ $rotulo }}</option>
        @endforeach
      </select>
    </label>
    <div class="row row--wrap push">
      <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
    </div>
  </form>

  <div class="card">
    <div class="table-scroll">
      <table class="table">
        <thead>
          <tr>
            <th>Empresa</th><th>Responsável</th><th>Cidade/UF</th><th>Data</th>
            <th>Resultado IA</th><th>Status</th><th class="cell-num">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse($solicitacoes as $solicitacao)
            @php($verificacao = $solicitacao->verifications->first())
            <tr>
              <td>
                <strong style="color:var(--ink)">{{ $solicitacao->trade_name }}</strong>
                <small class="muted">{{ $solicitacao->protocol }}</small>
              </td>
              <td>{{ $solicitacao->contact_name }}</td>
              <td>{{ $solicitacao->city }} / {{ $solicitacao->state }}</td>
              <td><span class="num">{{ $solicitacao->created_at?->format('d/m/Y') }}</span></td>
              <td>
                @if($verificacao === null)
                  <span class="chip chip--neutral chip--flat">Sem triagem</span>
                @elseif($verificacao->cnaes_compativeis)
                  <span class="chip chip--ok chip--flat">Compatível</span>
                @else
                  <span class="chip chip--danger chip--flat">Incompatível</span>
                @endif
              </td>
              <td>
                @if($solicitacao->status === \App\Models\Reseller::STATUS_AWAITING_INFO)
                  <span class="chip chip--info chip--flat">Aguardando informações</span>
                @else
                  <span class="chip chip--warn chip--flat">Aguardando decisão</span>
                @endif
              </td>
              <td class="cell-num">
                <a class="btn btn--secondary btn--sm" href="{{ route('backend.pre-cadastros.show', $solicitacao) }}">Analisar</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7"><p class="lede" style="margin:0">Nenhuma solicitação na fila com esses filtros.</p></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($solicitacoes->hasPages())
      <div class="pag">{{ $solicitacoes->links() }}</div>
    @endif
  </div>

</x-velaro.layouts.master>
