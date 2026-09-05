{{--
[Modulo: resources/views/portal/clientes]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.3 — ficha do cliente: cadastro, datas de relacionamento, consentimento LGPD e historico de pedidos.
--}}
<x-velaro.layouts.portal :title="'Cliente · '.$identidade['nome']" titulo="Portal do Lojista">

  <div class="page-head">
    <div>
      <h1 class="display-md">{{ $identidade['nome'] }}</h1>
      <p class="lede">Ficha do cliente — dados, relacionamento, consentimento e histórico de pedidos na sua loja.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.clientes.index') }}">← Voltar para clientes</a>
      <a class="btn btn--secondary" href="{{ route('portal.pedidos.index', ['q' => $identidade['nome'], 'periodo' => 0]) }}">
        <x-velaro.icon name="bag" /> Pedidos deste cliente
      </a>
    </div>
  </div>

  <div class="card">
    <div class="spread">
      <div class="row row--wrap" style="gap:10px">
        <span class="avatar" style="background:var(--color-gold-100);color:var(--color-gold-800)">{{ $identidade['iniciais'] }}</span>
        <span class="chip {{ $identidade['chip'] }}">{{ $identidade['situacao'] }}</span>
        <span class="chip chip--neutral chip--flat">{{ $identidade['tipoDePessoa'] }}</span>
        @if($identidade['cidadeUf'])
          <span class="muted" style="font-size:var(--text-sm)">{{ $identidade['cidadeUf'] }}</span>
        @endif
        @if($identidade['clienteDesde'])
          <span class="muted" style="font-size:var(--text-sm)">Cliente desde {{ $identidade['clienteDesde'] }}</span>
        @endif
      </div>
    </div>

    <div class="identbar" style="margin-top:var(--space-4)">
      <div class="identcell"><span><small>Pedidos</small><strong>{{ $resumo['pedidos'] }}</strong></span></div>
      <div class="identcell"><span><small>Em aberto</small><strong>{{ $resumo['emAberto'] }}</strong></span></div>
      <div class="identcell"><span><small>Total (custo Velaro)</small><strong>{{ $resumo['total'] }}</strong></span></div>
      <div class="identcell"><span><small>Ticket médio</small><strong>{{ $resumo['ticket'] }}</strong></span></div>
    </div>
  </div>

  <div class="split split--wide">
    <div class="stack">

      <div class="card">
        <div class="card__head"><h2 class="title">Dados cadastrais</h2></div>
        @foreach($cadastro as $campo)
          @if($campo['valor'])
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon :name="$campo['icone']" /> {{ $campo['rotulo'] }}</span>
              <span class="datarow__v">{{ $campo['valor'] }}</span>
            </div>
          @endif
        @endforeach
        @if($cliente->notes)
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="doc" /> Observações</span>
            <span class="datarow__v">{{ $cliente->notes }}</span>
          </div>
        @endif
      </div>

      {{-- As datas ficam visíveis aqui porque são dado do cadastro. O que depende
           de consentimento é usá-las como gatilho de campanha — e essa decisão
           está no card ao lado, resolvida no service, não neste arquivo. --}}
      <div class="card">
        <div class="card__head"><h2 class="title">Datas de relacionamento</h2></div>
        @php($temData = collect($relacionamento)->contains(fn ($data) => $data['data'] !== null))
        @if(! $temData)
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Nenhuma data registrada. Aniversário, casamento e início do namoro são o que dá motivo
              para a loja voltar a falar com o cliente.</span>
          </p>
        @else
          @foreach($relacionamento as $data)
            @if($data['data'])
              <div class="datarow">
                <span class="datarow__k"><x-velaro.icon :name="$data['icone']" /> {{ $data['rotulo'] }}</span>
                <span class="datarow__v">
                  {{ $data['data'] }}
                  <small class="muted">· próxima em {{ $data['proxima'] }}
                    @if($data['faltam'] !== null)({{ $data['faltam'] === 0 ? 'hoje' : 'faltam '.$data['faltam'].' dias' }})@endif
                  </small>
                </span>
              </div>
            @endif
          @endforeach
        @endif
      </div>

      <div class="card">
        <div class="card__head">
          <h2 class="title">Histórico de pedidos ({{ $totalDePedidos }})</h2>
          <a class="link-gold" href="{{ route('portal.pedidos.index', ['q' => $identidade['nome'], 'periodo' => 0]) }}">Ver na lista →</a>
        </div>

        @if($pedidos === [])
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span><strong>Este cliente ainda não tem pedidos.</strong>
              Monte o primeiro pelo <a class="link-gold" href="{{ route('portal.catalogo') }}">catálogo revendedor</a>.</span>
          </p>
        @else
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th>Pedido</th>
                  <th>Data</th>
                  <th class="cell-num">Itens</th>
                  <th class="cell-num">Valor (custo Velaro)</th>
                  <th>Status do pedido</th>
                  <th>Status do pagamento</th>
                  <th>Entrega prevista</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pedidos as $pedido)
                  <tr>
                    <td><strong style="color:var(--ink)"><a href="{{ $pedido['url'] }}">#{{ $pedido['numero'] }}</a></strong></td>
                    <td>{{ $pedido['data'] }}<br><small class="muted">{{ $pedido['hora'] }}</small></td>
                    <td class="cell-num"><span class="num">{{ $pedido['itens'] }}</span></td>
                    <td class="cell-num"><span class="cell-strong num">{{ $pedido['valor'] }}</span></td>
                    <td><span class="chip {{ $pedido['operacional']['chip'] }}">{{ $pedido['operacional']['rotulo'] }}</span></td>
                    <td><span class="chip {{ $pedido['pagamento']['chip'] }}">{{ $pedido['pagamento']['rotulo'] }}</span></td>
                    <td>{{ $pedido['previsao'] ?? '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <div class="stack">

      {{-- Consentimento é registrável E revogável: o que vale é a última linha de
           cada tipo, e uma linha concedida com `revoked_at` preenchido é
           consentimento retirado. --}}
      <div class="card">
        <div class="card__head"><h2 class="title">Consentimento (LGPD)</h2></div>

        @foreach(['marketing', 'transactional'] as $tipo)
          @php($consentimento = $consentimentos[$tipo])
          <div class="datarow">
            <span class="datarow__k">{{ $consentimento['rotulo'] }}</span>
            <span class="datarow__v"><span class="chip {{ $consentimento['chip'] }}">{{ $consentimento['situacao'] }}</span></span>
          </div>
          @if($consentimento['concedidoEm'])
            <div class="datarow">
              <span class="datarow__k"><small class="muted">Concedido em</small></span>
              <span class="datarow__v"><small class="muted">{{ $consentimento['concedidoEm'] }}@if($consentimento['canal']) · {{ $consentimento['canal'] }}@endif</small></span>
            </div>
          @endif
          @if($consentimento['revogadoEm'])
            <div class="datarow">
              <span class="datarow__k"><small class="muted">Revogado em</small></span>
              <span class="datarow__v"><small class="muted">{{ $consentimento['revogadoEm'] }}</small></span>
            </div>
          @endif
        @endforeach

        @if($consentimentos['historico'] !== [])
          <span class="eyebrow" style="display:block;margin-top:var(--space-4)">Histórico</span>
          @foreach($consentimentos['historico'] as $registro)
            <div class="datarow">
              <span class="datarow__k">
                <x-velaro.icon :name="$registro['concedido'] ? 'check' : 'x'" />
                <span><strong style="display:block;color:var(--ink)">{{ $registro['acao'] }} · {{ $registro['rotulo'] }}</strong>
                  @if($registro['evidencia'])<small>{{ $registro['evidencia'] }}</small>@endif</span>
              </span>
              <span class="datarow__v"><small class="muted">{{ $registro['quando'] ?? '—' }}</small></span>
            </div>
          @endforeach
        @endif
      </div>

      {{-- Regra 1 da tela 2.3: sem consentimento de marketing válido a lista de
           datas de campanha sai vazia do service — não há o que a view esconder. --}}
      <div class="card">
        <div class="card__head">
          <h2 class="title">Campanhas em datas especiais</h2>
          <span class="chip {{ $campanhas['liberado'] ? 'chip--ok' : 'chip--danger' }}">
            {{ $campanhas['liberado'] ? 'Liberadas' : 'Bloqueadas' }}
          </span>
        </div>

        <p class="notice {{ $campanhas['liberado'] ? 'notice--ok' : 'notice--danger' }}">
          <x-velaro.icon :name="$campanhas['liberado'] ? 'check' : 'lock'" />
          <span>{{ $campanhas['motivo'] }}</span>
        </p>

        @if($campanhas['datas'] === [])
          <p class="fhint">Nenhuma data alimenta campanha para este cliente.</p>
        @else
          @foreach($campanhas['datas'] as $data)
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon :name="$data['icone']" /> {{ $data['rotulo'] }}</span>
              <span class="datarow__v">{{ $data['proxima'] }}</span>
            </div>
          @endforeach
        @endif

        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span>Aviso de pedido pronto é <strong>comunicação transacional</strong>: não depende de consentimento
            de marketing e é registrado separadamente das campanhas promocionais.</span>
        </p>
      </div>
    </div>
  </div>
</x-velaro.layouts.portal>
