{{--
[Modulo: resources/views/portal/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.5 — lista de pedidos do lojista: KPIs, filtros, tabela com os dois status e a gaveta do pedido selecionado.
--}}
<x-velaro.layouts.portal title="Pedidos" titulo="Portal do Lojista">

  <div class="page-head">
    <div>
      <h1 class="display-md">Pedidos</h1>
      <p class="lede">Acompanhe e gerencie todos os pedidos da sua loja.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.catalogo') }}"><x-velaro.icon name="book" /> Catálogo Revendedor</a>
    </div>
  </div>

  {{-- Cada KPI conta a carteira inteira e abre a lista com `periodo=0`, para o
       número do cartão e a lista que ele abre nunca discordarem. --}}
  <section class="grid g5" aria-label="Indicadores dos pedidos">
    @foreach($kpis as $kpi)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ $kpi['valor'] }}</div>
            <a class="kpi__delta" href="{{ $kpi['url'] }}">{{ $kpi['cta'] }}</a>
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <div class="split split--wide">
    <div class="stack">
      @include('portal.pedidos.partials.filtros')

      <div class="card">
        @if($linhas === [])
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            @if($carteiraVazia)
              <span><strong>Nenhum pedido ainda.</strong>
                Monte o primeiro no <a class="link-gold" href="{{ route('portal.catalogo') }}">catálogo revendedor</a>.</span>
            @else
              <span><strong>Nenhum pedido com esses filtros.</strong>
                A lista abre nos últimos 90 dias —
                <a class="link-gold" href="{{ route('portal.pedidos.index', ['periodo' => 0]) }}">ver todos os períodos</a>.</span>
            @endif
          </p>
        @else
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th>Pedido</th>
                  <th>Cliente</th>
                  <th>Data</th>
                  <th class="cell-num">Itens</th>
                  <th class="cell-num">Valor (custo Velaro)</th>
                  <th>Status do pedido</th>
                  <th>Status do pagamento</th>
                  <th>Entrega prevista</th>
                  <th class="cell-num">Ações</th>
                </tr>
              </thead>
              <tbody>
                @foreach($linhas as $linha)
                  <tr>
                    <td>
                      <strong style="color:var(--ink)"><a href="{{ $linha['url'] }}">#{{ $linha['numero'] }}</a></strong>
                      @if($linha['lote'])<br><small class="muted">Lote {{ $linha['lote'] }}</small>@endif
                    </td>
                    <td>
                      @if($linha['clienteUrl'])
                        <a href="{{ $linha['clienteUrl'] }}">{{ $linha['cliente'] }}</a>
                      @else
                        <span class="muted">{{ $linha['cliente'] ?? '—' }}</span>
                      @endif
                    </td>
                    <td>{{ $linha['data'] }}<br><small class="muted">{{ $linha['hora'] }}</small></td>
                    <td class="cell-num"><span class="num">{{ $linha['unidades'] }}</span></td>
                    <td class="cell-num"><span class="cell-strong num">{{ $linha['valor'] }}</span></td>
                    <td><span class="chip {{ $linha['operacional']['chip'] }}">{{ $linha['operacional']['rotulo'] }}</span></td>
                    <td><span class="chip {{ $linha['pagamento']['chip'] }}">{{ $linha['pagamento']['rotulo'] }}</span></td>
                    <td>{{ $linha['previsao'] ?? '—' }}</td>
                    <td class="cell-num">
                      <a class="btn btn--secondary btn--sm" href="{{ $linha['gavetaUrl'] }}">Resumo</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @include('portal.pedidos.partials.paginacao')
        @endif
      </div>

      <p class="notice notice--gold">
        <x-velaro.icon name="info" />
        <span><strong>Status do pedido</strong> e <strong>status do pagamento</strong> são campos independentes
          (Anexo I §6). O pedido só entra em produção após a quitação do lote.</span>
      </p>
    </div>

    @if($gaveta)
      @include('portal.pedidos.partials.gaveta')
    @endif
  </div>
</x-velaro.layouts.portal>
