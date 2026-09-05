{{--
[Modulo: resources/views/portal/clientes]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.3 — carteira de clientes do lojista: KPIs, filtros, tabela com ultimo pedido e gaveta de novo cliente.
--}}
<x-velaro.layouts.portal title="Clientes" titulo="Portal do Lojista">

  <div class="page-head">
    <div>
      <h1 class="display-md">Clientes</h1>
      <p class="lede">Gerencie os clientes da sua loja e acompanhe pedidos e relacionamento.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--primary" href="#novo-cliente"><x-velaro.icon name="plus" /> Novo cliente</a>
    </div>
  </div>

  {{-- Os quatro KPIs contam sempre a carteira do revendedor autenticado: as
       consultas nascem em ResellerScope::customers(). --}}
  <section class="grid g4" aria-label="Indicadores da carteira">
    @foreach($kpis as $kpi)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ $kpi['valor'] }}</div>
            <a class="kpi__delta" href="{{ $kpi['url'] }}">Ver detalhes →</a>
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <div class="split split--wide">
    <div class="stack">
      @include('portal.clientes.partials.filtros')

      <div class="card">
        @if($linhas === [])
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            @if($carteiraVazia)
              <span><strong>Sua carteira ainda está vazia.</strong>
                Cada pedido feito no portal nasce de um cliente — comece cadastrando quem passou na loja.</span>
            @else
              <span><strong>Nenhum cliente com esses filtros.</strong>
                <a class="link-gold" href="{{ route('portal.clientes.index') }}">Ver a carteira inteira</a>.</span>
            @endif
          </p>
        @else
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>CPF</th>
                  <th>Telefone</th>
                  <th>E-mail</th>
                  <th>Último pedido</th>
                  <th>Status</th>
                  <th class="cell-num">Ações</th>
                </tr>
              </thead>
              <tbody>
                @foreach($linhas as $linha)
                  <tr>
                    <td>
                      <div class="row" style="gap:10px">
                        <span class="avatar" style="background:var(--color-gold-100);color:var(--color-gold-800)">{{ $linha['iniciais'] }}</span>
                        <span>
                          <strong style="color:var(--ink)"><a href="{{ $linha['url'] }}">{{ $linha['nome'] }}</a></strong>
                          @if($linha['cidadeUf'])<br><small class="muted">{{ $linha['cidadeUf'] }}</small>@endif
                        </span>
                      </div>
                    </td>
                    <td><span class="num">{{ $linha['documento'] ?? '—' }}</span></td>
                    <td>
                      @if($linha['telefone'])
                        <span class="row" style="gap:6px">{{ $linha['telefone'] }}
                          <x-velaro.icon name="whats" style="color:#25D366;width:15px;height:15px" />
                        </span>
                      @else
                        <span class="muted">—</span>
                      @endif
                    </td>
                    <td>{{ $linha['email'] ?? '—' }}</td>
                    <td>
                      @if($linha['ultimoPedidoEm'])
                        {{ $linha['ultimoPedidoEm'] }}
                        @if($linha['ultimoPedidoNumero'])<br><small class="muted">Pedido #{{ $linha['ultimoPedidoNumero'] }}</small>@endif
                      @else
                        <span class="muted">Sem pedidos</span>
                      @endif
                    </td>
                    <td><span class="chip {{ $linha['chip'] }}">{{ $linha['situacao'] }}</span></td>
                    <td class="cell-num">
                      <a class="btn btn--secondary btn--sm" href="{{ $linha['url'] }}">Ver ficha</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @include('portal.clientes.partials.paginacao')
        @endif
      </div>

      <p class="notice notice--gold">
        <x-velaro.icon name="shield" />
        <span><strong>Carteira exclusiva da sua loja.</strong> Cliente, pedido e histórico são escopados pelo seu
          código de revendedor — nenhum outro lojista alcança estes cadastros, e você não alcança os dele.</span>
      </p>
    </div>

    @include('portal.clientes.partials.drawer')
  </div>
</x-velaro.layouts.portal>
