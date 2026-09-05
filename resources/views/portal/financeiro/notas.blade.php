{{--
[Modulo: resources/views/portal/financeiro]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Notas fiscais emitidas contra o lojista: filtro, lista com PDF e XML, resumo por competencia e dados fiscais.
--}}
@php($loja = auth()->user()?->reseller?->trade_name ?? 'sua loja')
<x-velaro.layouts.portal title="Notas fiscais emitidas" titulo="Financeiro">

  <div class="page-head">
    <div>
      <h1 class="display-md">Notas fiscais emitidas</h1>
      <p class="lede">Todas as NF-e que a Velaro emitiu contra a {{ $loja }} — a venda B2B fábrica → lojista.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.financeiro.index') }}">← Voltar para o financeiro</a>
    </div>
  </div>

  <section class="grid g4">
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--gold"><x-velaro.icon name="doc" /></span>
        <div>
          <div class="kpi__label">Notas emitidas</div>
          <div class="kpi__value">{{ $kpis['emitidas'] }}</div>
          <div class="kpi__delta">Este mês</div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--ok"><x-velaro.icon name="coin" /></span>
        <div>
          <div class="kpi__label">Valor total faturado</div>
          <div class="kpi__value">{{ $kpis['faturado'] }}</div>
          <div class="kpi__delta">Este mês</div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--info"><x-velaro.icon name="calendar" /></span>
        <div>
          <div class="kpi__label">Última emissão</div>
          <div class="kpi__value">{{ $kpis['ultima_emissao'] }}</div>
          <div class="kpi__delta">{{ $kpis['ultima_nota'] }}</div>
        </div>
      </div>
    </div>
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon kpi__icon--danger"><x-velaro.icon name="x" /></span>
        <div>
          <div class="kpi__label">Notas canceladas</div>
          <div class="kpi__value">{{ $kpis['canceladas'] }}</div>
          <div class="kpi__delta">Últimos 90 dias</div>
        </div>
      </div>
    </div>
  </section>

  <div class="split split--wide">
    <div class="stack">

      <form class="filters" method="GET" action="{{ route('portal.financeiro.notas') }}" role="search">
        <input type="hidden" name="aba" value="{{ $filtros['aba'] }}">
        <span class="input-shell" style="flex:1;min-width:240px">
          <x-velaro.icon name="search" class="ic input-shell__icon" />
          <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
                 placeholder="Buscar por número da NF-e, pedido ou lote…"
                 aria-label="Buscar por número da NF-e, pedido ou lote">
        </span>

        <label class="fbox"><span>Período</span>
          <select class="select select--compact" name="periodo">
            @foreach($periodos as $valor => $rotulo)
              <option value="{{ $valor }}" @selected($filtros['periodo'] === (string) $valor)>{{ $rotulo }}</option>
            @endforeach
          </select>
        </label>

        <label class="fbox"><span>Competência</span>
          <select class="select select--compact" name="competencia">
            <option value="">Todas</option>
            @foreach($competencias as $valor => $rotulo)
              <option value="{{ $valor }}" @selected($filtros['competencia'] === $valor)>{{ $rotulo }}</option>
            @endforeach
          </select>
        </label>

        <label class="fbox"><span>Série</span>
          <select class="select select--compact" name="serie">
            <option value="">Todas</option>
            @foreach($series as $serie)
              <option value="{{ $serie }}" @selected($filtros['serie'] === $serie)>{{ $serie }}</option>
            @endforeach
          </select>
        </label>

        <div class="row row--wrap push">
          <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
          @if($algumFiltroAtivo)
            <a class="btn btn--secondary btn--sm" href="{{ route('portal.financeiro.notas') }}"><x-velaro.icon name="x" /> Limpar filtros</a>
          @endif
        </div>
      </form>

      <div class="card">
        <div class="tabs">
          @foreach($abas as $item)
            <a class="tab{{ $item['ativa'] ? ' is-on' : '' }}" href="{{ $item['url'] }}"
               @if($item['ativa']) aria-current="page" @endif>{{ $item['rotulo'] }} ({{ $item['total'] }})</a>
          @endforeach
        </div>

        <div class="table-scroll" tabindex="0" aria-label="Notas fiscais emitidas contra a loja">
          <table class="table">
            <thead>
              <tr>
                <th>Número NF-e</th>
                <th class="cell-num">Série</th>
                <th>Emissão</th>
                <th>Competência</th>
                <th class="cell-num">Valor total</th>
                <th>Lote</th>
                <th>Pedido vinculado</th>
                <th>Status</th>
                <th class="cell-num">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse($linhas as $linha)
                <tr>
                  <td><strong style="color:var(--ink)">{{ $linha['numero'] }}</strong></td>
                  <td class="cell-num"><span class="num">{{ $linha['serie'] }}</span></td>
                  <td>{{ $linha['emissao'] }}</td>
                  <td>{{ $linha['competencia'] }}</td>
                  <td class="cell-num"><span class="cell-strong num">{{ $linha['valor'] }}</span></td>
                  <td>
                    @if($linha['lote_url'])
                      <a class="link-gold num" href="{{ $linha['lote_url'] }}">{{ $linha['lote'] }}</a>
                    @else
                      <span class="num">{{ $linha['lote'] }}</span>
                    @endif
                  </td>
                  <td>
                    @if($linha['pedido'])
                      <a class="link-gold" href="{{ $linha['pedido_url'] }}">{{ $linha['pedido'] }}</a>
                      @if($linha['pedidos_restantes'] > 0)
                        <small class="muted">+{{ $linha['pedidos_restantes'] }} no lote</small>
                      @endif
                    @else
                      <span class="muted">—</span>
                    @endif
                  </td>
                  <td><span class="chip {{ $linha['status']['classe'] }}">{{ $linha['status']['rotulo'] }}</span></td>
                  <td class="cell-num">
                    <span class="row" style="gap:10px;justify-content:flex-end">
                      @if($linha['cancelada'])
                        <a class="link-gold" href="{{ route('portal.suporte.create') }}">Ver motivo</a>
                      @else
                        @if($linha['pdf']['disponivel'])
                          <a class="link-gold" href="{{ $linha['pdf']['url'] }}" download>Baixar NF</a>
                        @else
                          <span class="muted" title="O PDF ainda não está publicado para download">Baixar NF</span>
                        @endif
                        @if($linha['xml']['disponivel'])
                          <a class="link-gold" href="{{ $linha['xml']['url'] }}" download>Baixar XML</a>
                        @else
                          <span class="muted" title="O XML ainda não está publicado para download">Baixar XML</span>
                        @endif
                      @endif
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="muted">Nenhuma nota fiscal neste recorte.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="tfoot">
          @include('portal.financeiro.partials.paginacao', [
            'paginador' => $notas,
            'rotulo' => 'notas fiscais',
            'seletorDePagina' => [
              'action' => route('portal.financeiro.notas'),
              'ocultos' => $parametros + ['aba' => $filtros['aba']],
              'opcoes' => $tamanhos,
              'atual' => $filtros['por_pagina'],
            ],
          ])
        </div>
      </div>

      <p class="notice notice--gold">
        <x-velaro.icon name="info" />
        <span>A Velaro emite a NF da venda B2B ao lojista. O lojista é responsável por emitir o documento fiscal da venda ao seu consumidor final.</span>
      </p>
    </div>

    <div class="stack">
      <div class="card">
        <div class="card__head"><h2 class="title">Resumo por competência</h2></div>
        @forelse($resumoPorCompetencia as $mes)
          <div class="datarow">
            <span class="datarow__k">
              <x-velaro.icon name="calendar" />
              <span>
                <strong style="display:block;color:var(--ink)">{{ $mes['competencia'] }}</strong>
                <small>{{ $mes['detalhe'] }}</small>
              </span>
            </span>
            <span class="datarow__v"><span class="cell-strong num">{{ $mes['total'] }}</span></span>
          </div>
        @empty
          <p class="lede" style="font-size:var(--text-sm)">Nenhuma competência com nota emitida até agora.</p>
        @endforelse
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Como funciona a nota fiscal</h2></div>
        <ul class="cklist">
          <li class="ck--ok"><x-velaro.icon name="check" /><span>Lote fechado no fim da semana</span><b>semanal</b></li>
          <li class="ck--ok"><x-velaro.icon name="check" /><span>Pagamento à Velaro confirmado</span><b>até a data limite</b></li>
          <li class="ck--ok"><x-velaro.icon name="check" /><span>NF-e emitida contra o CNPJ da sua loja</span><b>D+1</b></li>
          <li class="ck--wait"><x-velaro.icon name="clock" /><span>PDF e XML disponíveis nesta tela</span><b>automático</b></li>
          <li class="ck--wait"><x-velaro.icon name="clock" /><span>Liberação dos pedidos para produção</span><b>após a NF</b></li>
        </ul>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Dados do destinatário</h2></div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="store" /> Razão social</span>
          <span class="datarow__v">{{ $destinatario['razao_social'] }}</span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="doc" /> CNPJ</span>
          <span class="datarow__v"><span class="num">{{ $destinatario['cnpj'] }}</span></span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="shield" /> Inscrição estadual</span>
          <span class="datarow__v"><span class="num">{{ $destinatario['inscricao_estadual'] }}</span></span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="pin" /> Endereço fiscal</span>
          <span class="datarow__v">{{ $destinatario['endereco'] }}</span>
        </div>
        <a class="btn btn--secondary" href="{{ route('portal.loja.edit') }}"><x-velaro.icon name="edit" /> Atualizar dados fiscais</a>
      </div>

      <p class="notice notice--info">
        <x-velaro.icon name="info" />
        <span>Divergência em alguma nota? <a class="link-gold" href="{{ route('portal.suporte.create') }}">Abra um chamado</a> na categoria Financeiro que o time da Velaro corrige a emissão.</span>
      </p>
    </div>
  </div>
</x-velaro.layouts.portal>
