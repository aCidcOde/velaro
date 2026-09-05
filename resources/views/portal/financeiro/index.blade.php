{{--
[Modulo: resources/views/portal/financeiro]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.4 — financeiro do lojista: KPIs, pedidos do lote, notas emitidas e o drawer de pagamento a Velaro.
--}}
@php($loja = auth()->user()?->reseller?->trade_name ?? 'sua loja')
<x-velaro.layouts.portal title="Financeiro" titulo="Financeiro">

  <div class="page-head">
    <div>
      <h1 class="display-md">Financeiro</h1>
      <p class="lede">Acompanhe os pedidos feitos pela {{ $loja }} e seus clientes, controle lotes e pagamentos à Velaro, e consulte notas fiscais emitidas.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.financeiro.notas') }}">
        <x-velaro.icon name="doc" /> Notas fiscais emitidas
      </a>
      @if($resumoDoLote)
        <a class="btn btn--gold" href="{{ $resumoDoLote['url'] }}">
          <x-velaro.icon name="coin" /> Pagar lote {{ $resumoDoLote['rotulo'] }}
        </a>
      @endif
    </div>
  </div>

  @if($resumoDoLote && ! $resumoDoLote['quitado'])
    <p class="notice notice--danger">
      <x-velaro.icon name="info" />
      <span>
        <strong>Lote atual {{ $resumoDoLote['vencido'] ? 'venceu em' : 'vence em' }} {{ $resumoDoLote['prazo'] }}.</strong>
        Evite atrasos e mantenha seus pedidos em produção.
      </span>
    </p>
  @endif

  <section class="grid g5">
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--danger"><x-velaro.icon name="coin" /></span>
        <div>
          <div class="kpi__label">Total em aberto</div>
          <div class="kpi__value">{{ $kpis['em_aberto'] }}</div>
          <div class="kpi__delta">
            @if($kpis['em_aberto_url'])
              <a class="link-gold" href="{{ $kpis['em_aberto_url'] }}">Ver detalhes →</a>
            @else
              Nada em aberto
            @endif
          </div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--gold"><x-velaro.icon name="bag" /></span>
        <div>
          <div class="kpi__label">Pedidos no lote atual</div>
          <div class="kpi__value">{{ $kpis['pedidos_do_lote'] }}</div>
          <div class="kpi__delta">{{ $kpis['pedidos_do_lote_total'] }}</div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--warn"><x-velaro.icon name="calendar" /></span>
        <div>
          <div class="kpi__label">Próximo vencimento</div>
          <div class="kpi__value">{{ $kpis['proximo_vencimento'] }}</div>
          <div class="kpi__delta">{{ $kpis['proximo_vencimento_hora'] }}</div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--info"><x-velaro.icon name="doc" /></span>
        <div>
          <div class="kpi__label">Notas fiscais emitidas</div>
          <div class="kpi__value">{{ $kpis['notas_emitidas'] }}</div>
          <div class="kpi__delta">Este mês</div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--ok"><x-velaro.icon name="check" /></span>
        <div>
          <div class="kpi__label">Pagamentos confirmados</div>
          <div class="kpi__value">{{ $kpis['pagamentos_confirmados'] }}</div>
          <div class="kpi__delta">Este mês</div>
        </div>
      </div>
    </div>
  </section>

  <div class="split split--wide">
    <div class="stack">

      <div class="card" id="pedidos">
        <div class="tabs">
          @foreach($abas as $item)
            <a class="tab{{ $item['ativa'] ? ' is-on' : '' }}" href="{{ $item['url'] }}"
               @if($item['ativa']) aria-current="page" @endif>{{ $item['rotulo'] }}</a>
          @endforeach
        </div>

        @if($lotes !== null)
          <div class="table-scroll" tabindex="0" aria-label="Lotes semanais do lojista">
            <table class="table">
              <thead>
                <tr>
                  <th>Lote</th>
                  <th>Período</th>
                  <th>Prazo máximo para pagamento</th>
                  <th>Pedidos</th>
                  <th class="cell-num">Valor custo Velaro</th>
                  <th>Status</th>
                  <th>NF-e</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                @forelse($linhasDeLote as $linha)
                  <tr>
                    <td>
                      <strong style="color:var(--ink)">{{ $linha['rotulo'] }}</strong><br>
                      <small class="muted">{{ $linha['codigo'] }}</small>
                    </td>
                    <td>{{ $linha['periodo'] }}</td>
                    <td>{{ $linha['vencimento'] }}<br><small class="muted">{{ $linha['hora_limite'] }}</small></td>
                    <td>{{ $linha['pedidos'] }}</td>
                    <td class="cell-num"><span class="cell-strong num">{{ $linha['total'] }}</span></td>
                    <td><span class="chip {{ $linha['status']['classe'] }}">{{ $linha['status']['rotulo'] }}</span></td>
                    <td>
                      @if($linha['nota'])
                        <a class="link-gold" href="{{ $linha['nota']['url'] }}">{{ $linha['nota']['numero'] }}</a>
                      @else
                        <span class="muted">—</span>
                      @endif
                    </td>
                    <td><a class="link-gold" href="{{ $linha['url'] }}">Ver lote</a></td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="muted">Nenhum lote faturado até agora.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="tfoot">
            @include('portal.financeiro.partials.paginacao', ['paginador' => $lotes, 'rotulo' => $rotuloDaLista])
          </div>
        @else
          @include('portal.financeiro.partials.tabela-pedidos')
          <div class="tfoot">
            @include('portal.financeiro.partials.paginacao', ['paginador' => $pedidos, 'rotulo' => $rotuloDaLista])
          </div>
        @endif
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Notas fiscais emitidas</h2></div>
        <div class="table-scroll" tabindex="0" aria-label="Últimas notas fiscais emitidas contra a loja">
          <table class="table">
            <thead>
              <tr>
                <th>Número NF-e</th>
                <th>Data de emissão</th>
                <th>Competência</th>
                <th class="cell-num">Valor total</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse($notas as $nota)
                <tr>
                  <td><strong style="color:var(--ink)">{{ $nota['numero'] }}</strong></td>
                  <td>{{ $nota['emissao'] }}</td>
                  <td>{{ $nota['competencia'] }}</td>
                  <td class="cell-num"><span class="cell-strong num">{{ $nota['valor'] }}</span></td>
                  <td><span class="chip {{ $nota['status']['classe'] }}">{{ $nota['status']['rotulo'] }}</span></td>
                  <td><a class="btn btn--secondary btn--sm" href="{{ $nota['url'] }}"><x-velaro.icon name="search" /> Consultar</a></td>
                </tr>
              @empty
                <tr><td colspan="6" class="muted">Nenhuma nota emitida até agora. A NF-e sai depois que o lote é quitado.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="tfoot">
          <a class="link-gold" href="{{ route('portal.financeiro.notas') }}">Ver todas as notas fiscais emitidas →</a>
        </div>
      </div>

      <p class="notice notice--gold">
        <x-velaro.icon name="info" />
        <span>A Velaro emite a NF da venda B2B ao lojista. O lojista é responsável por emitir o documento fiscal da venda ao seu consumidor final.</span>
      </p>
    </div>

    <aside class="drawer">
      <header class="drawer__head">
        <div>
          <h2 class="title">Pagamento à Velaro</h2>
          <p class="drawer__sub">Pagar lote semanal</p>
        </div>
      </header>

      @if($resumoDoLote === null)
        <div class="drawer__body">
          <p class="notice notice--ok">
            <x-velaro.icon name="check" />
            <span><strong>Nenhum lote em aberto.</strong> Assim que a semana fechar, o lote aparece aqui para pagamento.</span>
          </p>
        </div>
      @else
        <div class="drawer__body">
          <div class="pickitem is-on">
            <span class="eyebrow">Lote selecionado</span>
            <div class="pickitem__top"><strong>Lote semanal {{ $resumoDoLote['rotulo'] }}</strong></div>
            <small>{{ $resumoDoLote['periodo'] }}</small>
          </div>

          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="calendar" /> Data limite para pagamento</span>
            <span class="datarow__v"><span style="color:var(--color-error-700)">{{ $resumoDoLote['prazo'] }}</span></span>
          </div>

          <div class="pickitem">
            <div class="pickitem__top">
              <strong>Pedidos no lote</strong>
              <span class="cell-strong num">{{ $resumoDoLote['total_formatado'] }}</span>
            </div>
            <small>{{ $resumoDoLote['pedidos_rotulo'] }}</small>
            <a class="link-gold" href="{{ route('portal.financeiro.index') }}#pedidos">Ver detalhes dos pedidos ⌄</a>
          </div>

          <div>
            <span class="eyebrow">Resumo do pagamento</span>
            <div class="datarow">
              <span class="datarow__k">Subtotal (custos Velaro)</span>
              <span class="datarow__v"><span class="num">{{ $resumoDoLote['totais']['subtotal'] }}</span></span>
            </div>
            <div class="datarow">
              <span class="datarow__k">Descontos</span>
              <span class="datarow__v"><span class="num">− {{ $resumoDoLote['totais']['descontos'] }}</span></span>
            </div>
            <div class="spread" style="padding-top:10px">
              <strong>Total a pagar</strong>
              <span class="money money--action">{{ $resumoDoLote['totais']['total'] }}</span>
            </div>
          </div>

          <p class="notice notice--gold">
            <x-velaro.icon name="info" />
            <span>A produção dos pedidos deste lote será liberada <strong>após a confirmação do pagamento</strong>.</span>
          </p>

          <div>
            <span class="eyebrow">Método de pagamento</span>
            <div class="stack" style="margin-top:8px">
              @foreach($meiosDePagamento as $meio)
                <a class="payopt{{ $meio['ativo'] ? ' is-on' : '' }}" href="{{ $meio['url'] }}">
                  <span class="radio{{ $meio['ativo'] ? ' is-on' : '' }}" aria-hidden="true"></span>
                  <x-velaro.icon :name="$meio['icone']" />
                  <strong>{{ $meio['rotulo'] }}</strong>
                  <small>{{ $meio['nota'] }}</small>
                </a>
              @endforeach
            </div>
          </div>
        </div>

        <div class="drawer__foot">
          <a class="btn btn--primary" href="{{ $resumoDoLote['url'] }}">
            <x-velaro.icon name="lock" /> Realizar pagamento à Velaro
          </a>
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Após a confirmação do pagamento, o lote será liberado para produção e você receberá a confirmação por e-mail.</span>
          </p>
        </div>
      @endif
    </aside>
  </div>
</x-velaro.layouts.portal>
