{{--
[Modulo: resources/views/portal/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Telas 2.5 e 2.11 — detalhe do pedido: timeline, itens, gravacao, valores, entrega, pagamento, nota e retirada.
--}}
<x-velaro.layouts.portal :title="'Pedido #'.$numero" titulo="Portal do Lojista">

  <div class="page-head">
    <div>
      <h1 class="display-md">Pedido #{{ $numero }}</h1>
      <p class="lede">Detalhe completo do pedido — linha do tempo, itens, entrega, pagamento e nota fiscal.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ $acoes['voltar'] }}">← Voltar para pedidos</a>
      <a class="btn btn--secondary" href="{{ $acoes['chamado'] }}"><x-velaro.icon name="support" /> Abrir chamado sobre este pedido</a>
      <a class="btn btn--gold" href="{{ $acoes['pagamento'] }}"><x-velaro.icon name="coin" /> Faturamento / Pagamento</a>
    </div>
  </div>

  <div class="card">
    <div class="spread">
      <div class="row row--wrap" style="gap:10px">
        {{-- Dois chips, sempre: os status são independentes e nenhum é derivado do outro. --}}
        <span class="chip {{ $operacional['chip'] }}">{{ $operacional['rotulo'] }}</span>
        <span class="chip {{ $pagamentoStatus['chip'] }}">Pagamento: {{ $pagamentoStatus['rotulo'] }}</span>
        <span class="muted" style="font-size:var(--text-sm)">
          Criado em {{ $criadoEm }}@if($atualizadoEm) · atualizado em {{ $atualizadoEm }}@endif
        </span>
      </div>
    </div>

    <div class="identbar" style="margin-top:var(--space-4)">
      @foreach($identidade as $celula)
        <div class="identcell">
          <span>
            <small>{{ $celula['rotulo'] }}</small>
            <strong>{{ $celula['valor'] }}@if($celula['detalhe'])<br><small class="muted">{{ $celula['detalhe'] }}</small>@endif</strong>
          </span>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Tela 2.11: o bloco de retirada aparece a partir da chegada na loja. --}}
  @if($retirada)
    @include('portal.pedidos.partials.retirada')
  @endif

  <div class="split split--wide">
    <div class="stack">

      <div class="card">
        <div class="card__head">
          <h2 class="title">Linha do tempo do pedido</h2>
          <span class="chip {{ $operacional['chip'] }}">{{ $operacional['rotulo'] }}</span>
        </div>
        <ul class="timeline">
          @foreach($linhaDoTempo as $degrau)
            <li class="tl tl--{{ $degrau['estado'] }}">
              <span class="tl__dot"></span>
              <span class="tl__body">
                <strong>{{ $degrau['rotulo'] }}</strong>
                @if($degrau['descricao'])<span class="tl__desc">{{ $degrau['descricao'] }}</span>@endif
              </span>
              <span class="tl__when">{{ $degrau['quando'] }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Itens do pedido ({{ count($itens) }})</h2></div>

        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Produto</th>
                <th>SKU</th>
                <th>Especificações</th>
                <th class="cell-num">Qtd</th>
                <th class="cell-num">Preço unitário (custo Velaro)</th>
                <th class="cell-num">Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($itens as $item)
                <tr>
                  <td>
                    <div class="row" style="gap:10px">
                      <span class="thumb">
                        @if($item['imagem'])
                          <img src="{{ $item['imagem'] }}" alt="{{ $item['alt'] }}" loading="lazy">
                        @else
                          <x-velaro.ring :alt="$item['alt']" thumb />
                        @endif
                      </span>
                      <span><strong style="color:var(--ink)">{{ $item['nome'] }}</strong><br><small class="muted">Par de alianças</small></span>
                    </div>
                  </td>
                  <td>@if($item['sku'])<code>{{ $item['sku'] }}</code>@else<span class="muted">—</span>@endif</td>
                  <td>
                    {{ $item['especificacao'] !== '' ? $item['especificacao'] : '—' }}
                    @if($item['aro'])<br><small class="muted">Aro: {{ $item['aro'] }}</small>@endif
                  </td>
                  <td class="cell-num"><span class="num">{{ $item['quantidade'] }}</span></td>
                  <td class="cell-num"><span class="num">{{ $item['unitario'] }}</span></td>
                  <td class="cell-num"><span class="cell-strong num">{{ $item['total'] }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="tfoot">
          @foreach($valores['linhas'] as $linha)
            <div class="spread">
              <span @if($linha['destaque']) style="color:var(--color-success-700)" @endif>{{ $linha['rotulo'] }}</span>
              <span class="num">{{ $linha['valor'] }}</span>
            </div>
          @endforeach
          <div class="spread" style="padding-top:10px">
            <strong>Total do pedido (custo Velaro)</strong>
            <span class="money money--action">{{ $valores['total'] }}</span>
          </div>
        </div>

        {{-- O card de gravação aparece mesmo sem gravação, dizendo "Não": ausência
             de card não é resposta. --}}
        <div class="engravebox">
          <div class="row" style="gap:8px">
            <x-velaro.icon :name="$gravacao['solicitada'] ? 'check' : 'x'" />
            <strong>Gravação interna</strong>
          </div>
          <div class="datarow">
            <span class="datarow__k">Solicitada</span>
            <span class="datarow__v">{{ $gravacao['solicitada'] ? 'Sim' : 'Não' }}</span>
          </div>
          @foreach($gravacao['textos'] as $texto)
            <div class="datarow">
              <span class="datarow__k">Texto · {{ $texto['produto'] }}</span>
              <span class="datarow__v">{{ $texto['texto'] }}
                @if($texto['caracteres'])<small class="muted">({{ $texto['caracteres'] }} caracteres)</small>@endif
              </span>
            </div>
            @if($texto['data'])
              <div class="datarow">
                <span class="datarow__k">Data gravada</span>
                <span class="datarow__v">{{ $texto['data'] }}</span>
              </div>
            @endif
          @endforeach
          @if($gravacao['limite'])
            <div class="datarow">
              <span class="datarow__k">Limite</span>
              <span class="datarow__v">{{ $gravacao['limite'] }}</span>
            </div>
          @endif
          @if($gravacao['solicitada'])
            <div class="datarow">
              <span class="datarow__k">Aplicada em</span>
              <span class="datarow__v">{{ $gravacao['unidades'] }} {{ $gravacao['unidades'] === 1 ? 'unidade' : 'unidades' }}</span>
            </div>
          @endif
          <div class="datarow">
            <span class="datarow__k">Custo adicional</span>
            <span class="datarow__v">{{ $gravacao['custo'] }}</span>
          </div>
        </div>
      </div>

      <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
        <div class="card">
          <div class="card__head"><h2 class="title">Entrega e retirada</h2></div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="truck" /> Modo de entrega</span>
            <span class="datarow__v"><span class="chip chip--neutral chip--flat">{{ $entrega['modo'] }}</span></span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="store" /> Loja de destino</span>
            <span class="datarow__v">{{ $entrega['loja'] ?? '—' }}</span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="pin" /> Endereço</span>
            <span class="datarow__v">{{ $entrega['endereco'] ?? '—' }}</span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="calendar" /> Chegada prevista na loja</span>
            <span class="datarow__v">{{ $entrega['chegadaPrevista'] ?? '—' }}</span>
          </div>
          @if($entrega['chegadaEm'])
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon name="check" /> Chegou em</span>
              <span class="datarow__v">{{ $entrega['chegadaEm'] }}</span>
            </div>
          @endif
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="box" /> Transportadora</span>
            <span class="datarow__v">{{ $entrega['transportadora'] ?? 'Definida na expedição' }}</span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="link" /> Código de rastreio</span>
            <span class="datarow__v">
              @if($entrega['rastreio'] && $entrega['rastreioUrl'])
                <a class="link-gold" href="{{ $entrega['rastreioUrl'] }}" rel="noopener">{{ $entrega['rastreio'] }}</a>
              @else
                {{ $entrega['rastreio'] ?? '—' }}
              @endif
            </span>
          </div>

          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Quando o pedido chega à loja, a plataforma dispara WhatsApp e e-mail ao cliente
              <strong>em nome da {{ $entrega['loja'] ?? 'sua loja' }}</strong>.</span>
          </p>
        </div>

        <div class="card">
          <div class="card__head"><h2 class="title">Pagamento</h2></div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="coin" /> Status do pagamento</span>
            <span class="datarow__v"><span class="chip {{ $pagamento['status']['chip'] }}">{{ $pagamento['status']['rotulo'] }}</span></span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="box" /> Lote</span>
            <span class="datarow__v">{{ $pagamento['lote'] ?? '—' }}
              @if($pagamento['loteJanela'])<br><small class="muted">corte em {{ $pagamento['loteJanela'] }}</small>@endif
            </span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="clock" /> Prazo máximo para pagamento</span>
            <span class="datarow__v">
              @if($pagamento['vencimento'])
                <span @if(! $pagamento['quitadoEm']) style="color:var(--color-error-700)" @endif>{{ $pagamento['vencimento'] }}</span>
              @else
                —
              @endif
            </span>
          </div>
          @if($pagamento['quitadoEm'])
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon name="check" /> Quitado em</span>
              <span class="datarow__v">{{ $pagamento['quitadoEm'] }}</span>
            </div>
          @endif
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="card" /> Forma de pagamento</span>
            <span class="datarow__v">{{ $pagamento['forma'] ?? 'A escolher no pagamento do lote' }}</span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="coin" /> Valor deste pedido no lote</span>
            <span class="datarow__v"><span class="cell-strong num">{{ $pagamento['valorDoPedido'] }}</span></span>
          </div>

          <div class="row row--wrap" style="margin-top:var(--space-4)">
            <a class="btn btn--gold" href="{{ $pagamento['url'] }}"><x-velaro.icon name="coin" /> Pagar lote à Velaro</a>
            <a class="btn btn--secondary" href="{{ $acoes['financeiro'] }}"><x-velaro.icon name="chart" /> Ver financeiro</a>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Nota fiscal</h2></div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="doc" /> Situação</span>
          <span class="datarow__v"><span class="chip {{ $nota['chip'] }}">{{ $nota['situacao'] }}</span></span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="factory" /> Emitente</span>
          <span class="datarow__v">Velaro Alianças<br><small class="muted">Venda B2B ao lojista</small></span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="store" /> Destinatário</span>
          <span class="datarow__v">{{ $nota['destinatario'] ?? '—' }}
            @if($nota['cnpj'])<br><small class="muted">CNPJ {{ $nota['cnpj'] }}</small>@endif
          </span>
        </div>
        @if($nota['numero'])
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="doc" /> Número / série</span>
            <span class="datarow__v"><span class="num">{{ $nota['numero'] }}</span> / {{ $nota['serie'] ?? '1' }}</span>
          </div>
        @endif
        @if($nota['competencia'])
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="calendar" /> Competência</span>
            <span class="datarow__v">{{ $nota['competencia'] }}</span>
          </div>
        @endif
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="coin" /> Valor a faturar</span>
          <span class="datarow__v"><span class="cell-strong num">{{ $nota['valorDoPedido'] }}</span></span>
        </div>

        <p class="notice notice--gold">
          <x-velaro.icon name="info" />
          <span>A NF-e é emitida <strong>após a quitação do lote</strong>.
            <a class="link-gold" href="{{ $nota['url'] }}">Ver notas fiscais emitidas →</a></span>
        </p>
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span>A Velaro emite a NF da venda B2B ao lojista. O documento fiscal da venda ao
            <strong>consumidor final é responsabilidade da sua loja</strong>.</span>
        </p>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Histórico de atualizações</h2></div>
        @if($historico === [])
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Nenhum evento registrado para este pedido ainda.</span>
          </p>
        @else
          @foreach($historico as $evento)
            <div class="datarow">
              <span class="datarow__k">
                <x-velaro.icon name="clock" />
                <span>
                  <strong style="display:block;color:var(--ink)">{{ $evento['rotulo'] }}</strong>
                  <small>{{ $evento['escopo'] }}@if($evento['de']) · de {{ $evento['de'] }}@endif@if($evento['ator']) · por {{ $evento['ator'] }}@endif</small>
                  @if($evento['nota'])<small>{{ $evento['nota'] }}</small>@endif
                </span>
              </span>
              <span class="datarow__v">{{ $evento['quando'] ?? '—' }}</span>
            </div>
          @endforeach
        @endif
      </div>

      <p class="notice notice--gold">
        <x-velaro.icon name="info" />
        <span><strong>Status do pedido</strong> e <strong>status do pagamento</strong> são campos independentes
          (Anexo I §6). O pedido só entra em produção após a quitação do lote.</span>
      </p>
    </div>

    <div class="stack">
      <div class="card">
        <div class="card__head"><h2 class="title">Resumo do pedido (custo Velaro)</h2></div>
        @foreach($valores['linhas'] as $linha)
          <div class="datarow">
            <span class="datarow__k">{{ $linha['rotulo'] }}</span>
            <span class="datarow__v">
              <span class="num" @if($linha['destaque']) style="color:var(--color-success-700)" @endif>{{ $linha['valor'] }}</span>
            </span>
          </div>
        @endforeach
        <div class="spread" style="padding-top:10px">
          <strong>Total do pedido</strong>
          <span class="money money--action">{{ $valores['total'] }}</span>
        </div>
        <small class="fhint">Valor devido pela sua loja à Velaro. O preço cobrado do consumidor final é definido
          por você em <a class="link-gold" href="{{ route('portal.precos.edit') }}">Preços e margens</a>.</small>
      </div>

      @if($cliente['existe'])
        <div class="card">
          <div class="card__head"><h2 class="title">Cliente final</h2></div>
          <div class="row" style="gap:10px;margin-bottom:var(--space-3)">
            <span class="avatar" style="background:var(--color-gold-100);color:var(--color-gold-800)">{{ $cliente['iniciais'] }}</span>
            <span><strong style="color:var(--ink)">{{ $cliente['nome'] }}</strong>
              @if($cliente['desde'])<br><small class="muted">Cliente desde {{ $cliente['desde'] }}</small>@endif
            </span>
          </div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="card" /> CPF</span><span class="datarow__v">{{ $cliente['documento'] ?? '—' }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="phone" /> Telefone</span><span class="datarow__v">{{ $cliente['telefone'] ?? '—' }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="mail" /> E-mail</span><span class="datarow__v">{{ $cliente['email'] ?? '—' }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="pin" /> Cidade/UF</span><span class="datarow__v">{{ $cliente['cidadeUf'] ?? '—' }}</span></div>
          <a class="btn btn--secondary" href="{{ $cliente['url'] }}" style="margin-top:var(--space-3)">
            <x-velaro.icon name="user" /> Ver ficha do cliente
          </a>
        </div>
      @endif

      <div class="card">
        <div class="card__head"><h2 class="title">Ações do pedido</h2></div>
        <a class="btn btn--primary" href="{{ $acoes['pagamento'] }}"><x-velaro.icon name="coin" /> Pagar lote à Velaro</a>
        <a class="btn btn--secondary" href="{{ $acoes['chamado'] }}"><x-velaro.icon name="support" /> Abrir chamado sobre este pedido</a>
        <a class="btn btn--secondary" href="{{ $acoes['catalogo'] }}"><x-velaro.icon name="book" /> Montar outro pedido</a>
      </div>

      @if($observacao)
        <div class="card">
          <div class="card__head"><h2 class="title">Observações</h2></div>
          <p class="lede" style="font-size:var(--text-sm)">{{ $observacao }}</p>
        </div>
      @endif

      <p class="notice notice--info">
        <x-velaro.icon name="shield" />
        <span>Rota <code>/portal/pedidos/{{ $numero }}</code>. O pedido é sempre acessado pelo
          <strong>número público</strong> — o id interno nunca é exposto (Anexo I §4.5).</span>
      </p>
    </div>
  </div>
</x-velaro.layouts.portal>
