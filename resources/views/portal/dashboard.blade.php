{{--
[Modulo: resources/views/portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.1 — dashboard do lojista: indicadores, atalhos, ultimos pedidos, pendencias, vitrine e checklist da loja.
--}}
<x-velaro.layouts.portal title="Dashboard" titulo="Portal do Lojista">

  <div class="page-head">
    <div>
      <h1 class="display-md">Dashboard do Lojista</h1>
      <p class="lede">{{ $saudacao }}</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.catalogo') }}"><x-velaro.icon name="book" /> Catálogo Revendedor</a>
      <a class="btn btn--gold" href="{{ route('portal.pedidos.index') }}"><x-velaro.icon name="plus" /> Novo pedido</a>
    </div>
  </div>

  {{-- Indicadores. Cada número sai de uma agregação por reseller_id — ver PainelLojistaService. --}}
  <section class="grid g3" aria-label="Indicadores da loja">
    @foreach($indicadores as $indicador)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon {{ $indicador['variante'] }}"><x-velaro.icon :name="$indicador['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $indicador['rotulo'] }}</div>
            <div class="kpi__value">{{ number_format($indicador['valor'], 0, ',', '.') }}</div>
            <a class="kpi__delta" href="{{ $indicador['acao']['url'] }}">{{ $indicador['acao']['rotulo'] }}</a>
          </div>
        </div>
      </div>
    @endforeach
  </section>

  {{-- Atalhos --}}
  <section class="quickgrid" aria-label="Atalhos">
    @foreach($atalhos as $atalho)
      <a class="quickcard" href="{{ $atalho['url'] }}">
        <x-velaro.icon :name="$atalho['icone']" />
        <span><strong>{{ $atalho['titulo'] }}</strong><small>{{ $atalho['descricao'] }}</small></span>
        <b>›</b>
      </a>
    @endforeach
  </section>

  {{-- As colunas saem por --gcols, e nunca por grid-template-columns inline: o
       design system explica por quê (estilo inline vence media query e o colapso
       mobile do .split3 seria ignorado). A primeira coluna leva a maior fatia
       porque é a tabela; abaixo de ~1500px ela rola por dentro do .table-scroll. --}}
  <section class="split3" style="--gcols:minmax(0,1.9fr) minmax(0,1.05fr) minmax(0,1fr)">

    {{-- Últimos pedidos --}}
    <div class="card">
      <div class="card__head">
        <h2 class="title">Últimos pedidos</h2>
        <a href="{{ route('portal.pedidos.index') }}" class="link-gold">Ver todos →</a>
      </div>

      @if($ultimosPedidos === [])
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span><strong>Nenhum pedido ainda.</strong>
            Comece pelo <a href="{{ route('portal.catalogo') }}">catálogo revendedor</a> e monte o primeiro pedido do seu cliente.</span>
        </p>
      @else
        <div class="table-scroll" tabindex="0" aria-label="Últimos pedidos; deslize horizontalmente para ver todas as colunas">
          <table class="table">
            <thead>
              <tr>
                <th>Pedido</th><th>Cliente final</th><th>Status do pedido</th>
                <th>Status do pagamento</th><th class="cell-num">Custo Velaro</th><th>Previsão</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ultimosPedidos as $pedido)
                <tr>
                  <td class="cell-strong"><a href="{{ $pedido['url'] }}">{{ $pedido['numero'] }}</a></td>
                  <td>{{ $pedido['cliente'] }}</td>
                  <td><span class="chip {{ $pedido['operacional']['chip'] }}">{{ $pedido['operacional']['rotulo'] }}</span></td>
                  <td><span class="chip {{ $pedido['pagamento']['chip'] }}">{{ $pedido['pagamento']['rotulo'] }}</span></td>
                  <td class="cell-num cell-strong">{{ $pedido['custo'] }}</td>
                  <td>
                    @if($pedido['previsao'])<span class="num">{{ $pedido['previsao'] }}</span>@else<span class="muted">—</span>@endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      <p class="notice" style="margin-top:var(--space-4)">
        <x-velaro.icon name="info" />
        <span><strong>Status do pedido</strong> e <strong>status do pagamento</strong> são independentes:
          o pedido só entra em produção após a quitação do lote.</span>
      </p>
    </div>

    {{-- Ações pendentes --}}
    <div class="card">
      <div class="card__head">
        <h2 class="title">Ações pendentes</h2>
        <a href="{{ route('portal.pedidos.index') }}" class="link-gold">Ver todas →</a>
      </div>

      @if($pendencias === [])
        <p class="notice notice--ok">
          <x-velaro.icon name="check" />
          <span><strong>Nada pendente.</strong> Loja configurada, pagamentos em dia e nenhum chamado esperando você.</span>
        </p>
      @else
        <div class="stacklist">
          @foreach($pendencias as $pendencia)
            <div class="orderitem">
              <span class="kpi__icon {{ $pendencia['variante'] }}" style="width:34px;height:34px;border-radius:var(--radius-sm)">
                <x-velaro.icon :name="$pendencia['icone']" />
              </span>
              <span>
                <strong>{{ $pendencia['titulo'] }}</strong>
                <small>{{ $pendencia['descricao'] }}</small>
              </span>
              <a class="btn {{ $pendencia['acao']['estilo'] }} btn--sm" href="{{ $pendencia['acao']['url'] }}">{{ $pendencia['acao']['rotulo'] }}</a>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="stack">
      {{-- Vitrine da sua loja --}}
      <div class="card card--compact">
        <div class="card__head"><h2 class="title">Vitrine da sua loja</h2></div>

        <div class="storeprev">
          <div class="storeprev__bar">
            <strong>{{ mb_strtoupper($vitrine['nome']) }}</strong>
            <span>Alianças · Coleções · Contato</span>
          </div>
          <div class="storeprev__banner">
            <strong>{{ $vitrine['slogan'] }}</strong>
            <span>Vitrine white label da sua loja.</span>
            <em>Conheça nossas alianças</em>
          </div>
        </div>

        <div class="spread" style="margin-top:var(--space-4)">
          <span class="row" style="font-size:var(--text-sm);min-width:0">
            <x-velaro.icon name="globe" />
            <span style="overflow-wrap:anywhere">{{ $vitrine['dominio'] ?? 'Sem domínio próprio configurado' }}</span>
          </span>
          <a class="btn btn--secondary btn--sm" href="{{ $vitrine['url'] }}">
            {{ $vitrine['publicada'] ? 'Acessar vitrine →' : 'Publicar vitrine →' }}
          </a>
        </div>

        <p class="notice" style="margin-top:var(--space-3)">
          <x-velaro.icon name="shield" />
          <span>A vitrine é <strong>100% white label</strong>: o consumidor final não vê a marca Velaro.</span>
        </p>
      </div>

      {{-- Configuração da loja --}}
      <div class="card card--compact">
        <div class="spread" style="margin-bottom:var(--space-3)">
          <h2 class="title">Configuração da loja</h2>
          <span class="chip chip--flat chip--neutral num">{{ $checklist['feitos'] }} de {{ $checklist['total'] }}</span>
        </div>

        <span class="barrow__track" role="img"
              aria-label="{{ $checklist['feitos'] }} de {{ $checklist['total'] }} passos concluídos">
          <span class="barrow__fill" style="width:{{ $checklist['percentual'] }}%"></span>
        </span>

        <ul class="cklist" style="margin-top:var(--space-4)">
          @foreach($checklist['itens'] as $item)
            <li @class(['ck--ok' => $item['feito'], 'ck--wait' => ! $item['feito']])>
              <x-velaro.icon :name="$item['feito'] ? 'check' : 'clock'" />
              <span style="flex:1">{{ $item['rotulo'] }}</span>
              <a class="btn {{ $item['acao']['estilo'] }} btn--sm" href="{{ $item['acao']['url'] }}">{{ $item['acao']['rotulo'] }}</a>
            </li>
          @endforeach
        </ul>
      </div>
    </div>

  </section>
</x-velaro.layouts.portal>
