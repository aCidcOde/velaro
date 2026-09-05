{{--
[Modulo: resources/views/portal/financeiro]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Pagamento do lote a Velaro: conferencia, pedidos incluidos e os meios B2B — exibicao, sem gateway.
--}}
<x-velaro.layouts.portal :title="'Pagamento do lote '.$resumo['rotulo']" titulo="Financeiro">

  <div class="page-head">
    <div>
      <h1 class="display-md">Pagamento do lote à Velaro</h1>
      <p class="lede">Lote semanal {{ $resumo['rotulo'] }} · período de {{ $resumo['periodo'] }} · {{ $resumo['pedidos_rotulo'] }}.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary" href="{{ route('portal.financeiro.index') }}">← Voltar para o financeiro</a>
    </div>
  </div>

  @if($resumo['quitado'])
    <p class="notice notice--ok">
      <x-velaro.icon name="check" />
      <span><strong>Este lote está quitado.</strong> A produção dos pedidos já foi liberada e a NF-e da venda B2B é emitida na sequência.</span>
    </p>
  @else
    <p class="notice notice--danger">
      <x-velaro.icon name="info" />
      <span>
        <strong>Este lote {{ $resumo['vencido'] ? 'venceu em' : 'vence em' }} {{ $resumo['prazo'] }}.</strong>
        Depois do vencimento os pedidos saem da fila de produção e voltam para o lote seguinte.
      </span>
    </p>
  @endif

  <div class="card">
    <ol class="stepper">
      @foreach($passos as $passo)
        <li class="step step--{{ $passo['estado'] }}">
          <span class="step__dot">{{ $passo['estado'] === 'done' ? '✓' : $loop->iteration }}</span>
          <span class="step__lab">{{ $passo['rotulo'] }}</span>
          <span class="step__note">{{ $passo['nota'] }}</span>
        </li>
      @endforeach
    </ol>
  </div>

  <div class="split split--wide">
    <div class="stack">

      <div class="card">
        <div class="card__head"><h2 class="title">① Confira o lote</h2></div>
        <div class="identbar">
          <div class="identcell"><span><small>Lote</small><strong>{{ $resumo['rotulo'] }}</strong></span></div>
          <div class="identcell"><span><small>Período</small><strong>{{ $resumo['periodo'] }}</strong></span></div>
          <div class="identcell"><span><small>Pedidos</small><strong>{{ $resumo['pedidos'] }}</strong></span></div>
          <div class="identcell"><span><small>Data limite</small><strong>{{ $resumo['prazo'] }}</strong></span></div>
          <div class="identcell"><span><small>Total a pagar</small><strong>{{ $totais['total'] }}</strong></span></div>
        </div>
        <div style="margin-top:var(--space-4)">
          <div class="datarow">
            <span class="datarow__k">Subtotal (custos Velaro)</span>
            <span class="datarow__v"><span class="num">{{ $totais['subtotal'] }}</span></span>
          </div>
          <div class="datarow">
            <span class="datarow__k">Descontos</span>
            <span class="datarow__v"><span class="num">− {{ $totais['descontos'] }}</span></span>
          </div>
          <div class="datarow">
            <span class="datarow__k">Acréscimos por atraso</span>
            <span class="datarow__v"><span class="num">{{ $totais['acrescimos'] }}</span></span>
          </div>
          <div class="spread" style="padding-top:10px">
            <strong>Total a pagar à Velaro</strong>
            <span class="money money--action">{{ $totais['total'] }}</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">② Pedidos incluídos no lote ({{ $resumo['pedidos'] }})</h2></div>
        <div class="table-scroll" tabindex="0" aria-label="Pedidos incluídos no lote">
          <table class="table">
            <thead>
              <tr>
                <th>Pedido</th>
                <th>Cliente final</th>
                <th>Data do pedido</th>
                <th class="cell-num">Valor (custo Velaro)</th>
                <th>Status do pagamento</th>
              </tr>
            </thead>
            <tbody>
              @forelse($linhasDePedido as $linha)
                <tr>
                  <td><a class="link-gold" href="{{ $linha['url'] }}"><strong>{{ $linha['numero'] }}</strong></a></td>
                  <td>
                    <div class="row" style="gap:8px">
                      <span class="avatar avatar--sm" style="background:var(--color-gold-100);color:var(--color-gold-800)">{{ $linha['iniciais'] }}</span>{{ $linha['cliente'] }}
                    </div>
                  </td>
                  <td>{{ $linha['data'] }}</td>
                  <td class="cell-num"><span class="cell-strong num">{{ $linha['valor'] }}</span></td>
                  <td><span class="chip {{ $linha['status']['classe'] }}">{{ $linha['status']['rotulo'] }}</span></td>
                </tr>
              @empty
                <tr><td colspan="5" class="muted">Este lote ainda não tem pedidos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="tfoot">
          @include('portal.financeiro.partials.paginacao', ['paginador' => $pedidos, 'rotulo' => 'pedidos do lote '.$resumo['rotulo']])
        </div>
        <p class="notice notice--gold">
          <x-velaro.icon name="info" />
          <span>Todos os pedidos do lote são quitados juntos. Não há pagamento avulso por pedido — a fatura é do lote (Anexo I §6).</span>
        </p>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">③ Escolha a forma de pagamento</h2></div>
        <div class="stack" style="margin-top:4px">
          @foreach($meios as $meio)
            <a class="payopt{{ $meio['ativo'] ? ' is-on' : '' }}" href="{{ $meio['url'] }}"
               @if($meio['ativo']) aria-current="true" @endif>
              <span class="radio{{ $meio['ativo'] ? ' is-on' : '' }}" aria-hidden="true"></span>
              <x-velaro.icon :name="$meio['icone']" />
              <strong>{{ $meio['rotulo'] }}</strong>
              <small>{{ $meio['nota'] }}</small>
            </a>
          @endforeach
        </div>
      </div>

      @if($ehPix)
        <div class="card">
          <div class="card__head">
            <h2 class="title">Pix — aprovação imediata</h2>
            <span class="chip chip--ok">Selecionado</span>
          </div>
          @if($pix['disponivel'])
            <div class="paydoc">
              <div class="qrbox">
                {!! $pix['qr'] !!}
                <small>Aponte a câmera do app do seu banco.<br>Pague até {{ $pix['validade'] }}.</small>
              </div>
              <div class="paydoc__side">
                <div class="codebox">
                  <span>Pix copia e cola</span>
                  <code>{{ $pix['payload'] }}</code>
                </div>
                <div class="datarow">
                  <span class="datarow__k">Beneficiário</span>
                  <span class="datarow__v">{{ $pix['beneficiario'] }}</span>
                </div>
                <div class="datarow">
                  <span class="datarow__k">Identificador</span>
                  <span class="datarow__v"><span class="num">{{ $pix['identificador'] }}</span></span>
                </div>
                <div class="datarow">
                  <span class="datarow__k">Valor</span>
                  <span class="datarow__v"><span class="cell-strong num">{{ $pix['valor'] }}</span></span>
                </div>
              </div>
            </div>
            <p class="notice notice--ok">
              <x-velaro.icon name="check" />
              <span>A baixa do Pix é automática. Assim que o banco confirmar, o lote muda para <strong>Pago</strong> e a produção é liberada.</span>
            </p>
          @else
            <p class="notice notice--info">
              <x-velaro.icon name="info" />
              <span><strong>A chave Pix da Velaro ainda não está configurada.</strong> Use o boleto ou a transferência, ou fale com o financeiro pelo suporte.</span>
            </p>
          @endif
        </div>
      @endif

      @if($ehBoleto)
        <div class="card">
          <div class="card__head">
            <h2 class="title">Boleto bancário</h2>
            <span class="chip chip--ok">Selecionado</span>
          </div>
          @if($boleto['disponivel'])
            <div class="codebox">
              <span>Linha digitável</span>
              <code>{{ $boleto['linha_digitavel'] }}</code>
            </div>
          @else
            <p class="notice notice--info">
              <x-velaro.icon name="info" />
              <span><strong>O boleto deste lote ainda não foi emitido.</strong> Ele aparece aqui assim que o financeiro da Velaro registrar a cobrança.</span>
            </p>
          @endif
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="calendar" /> Vencimento</span>
            <span class="datarow__v"><span style="color:var(--color-error-700)">{{ $boleto['vencimento'] }}</span></span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="store" /> Beneficiário</span>
            <span class="datarow__v">{{ $beneficiario['razao_social'] }}</span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="user" /> Pagador</span>
            <span class="datarow__v">{{ $lote->reseller?->legal_name }}@if($lote->reseller?->cnpj) · CNPJ {{ $lote->reseller->cnpj }}@endif</span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="coin" /> Valor do documento</span>
            <span class="datarow__v"><span class="cell-strong num">{{ $boleto['valor'] }}</span></span>
          </div>
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="clock" /> Compensação</span>
            <span class="datarow__v">{{ $boleto['compensacao'] }}</span>
          </div>
          <p class="notice notice--gold">
            <x-velaro.icon name="info" />
            <span>Boleto pago após o vencimento não libera o lote automaticamente — o pedido volta para a fila do lote seguinte.</span>
          </p>
        </div>
      @endif

      @if($ehTransferencia)
        <div class="card">
          <div class="card__head">
            <h2 class="title">Transferência bancária</h2>
            <span class="chip chip--ok">Selecionado</span>
          </div>
          @if($transferencia['disponivel'])
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon name="store" /> Favorecido</span>
              <span class="datarow__v">{{ $transferencia['favorecido'] }}@if($transferencia['cnpj']) · CNPJ {{ $transferencia['cnpj'] }}@endif</span>
            </div>
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon name="card" /> Banco</span>
              <span class="datarow__v">{{ $transferencia['banco'] }}</span>
            </div>
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon name="pin" /> Agência</span>
              <span class="datarow__v"><span class="num">{{ $transferencia['agencia'] }}</span></span>
            </div>
            <div class="datarow">
              <span class="datarow__k"><x-velaro.icon name="doc" /> Conta corrente</span>
              <span class="datarow__v"><span class="num">{{ $transferencia['conta'] }}</span></span>
            </div>
          @else
            <p class="notice notice--info">
              <x-velaro.icon name="info" />
              <span><strong>Os dados bancários da Velaro ainda não estão configurados.</strong> Use o Pix ou o boleto, ou peça a conta ao financeiro pelo suporte.</span>
            </p>
          @endif
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="tag" /> Identificação obrigatória</span>
            <span class="datarow__v">{{ $transferencia['identificacao'] }}</span>
          </div>
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Na transferência, a baixa <strong>não é automática</strong>: envie o comprovante ao financeiro para acelerar a conferência.</span>
          </p>
        </div>
      @endif
    </div>

    <div class="stack">
      <div class="card">
        <div class="card__head"><h2 class="title">Total a pagar</h2></div>
        <div class="pickitem is-on">
          <span class="eyebrow">Lote selecionado</span>
          <div class="pickitem__top"><strong>Lote semanal {{ $resumo['rotulo'] }}</strong></div>
          <small>{{ $resumo['periodo'] }} · {{ $resumo['pedidos_rotulo'] }}</small>
        </div>
        <div class="datarow">
          <span class="datarow__k">Forma escolhida</span>
          <span class="datarow__v"><span class="chip chip--ok chip--flat">{{ $metodoRotulo }}</span></span>
        </div>
        <div class="datarow">
          <span class="datarow__k">Data limite</span>
          <span class="datarow__v"><span style="color:var(--color-error-700)">{{ $resumo['prazo'] }}</span></span>
        </div>
        <div class="datarow">
          <span class="datarow__k">Situação do lote</span>
          <span class="datarow__v"><span class="chip {{ $resumo['status']['classe'] }}">{{ $resumo['status']['rotulo'] }}</span></span>
        </div>
        <div class="spread" style="padding-top:10px">
          <strong>Total</strong>
          <span class="money money--action">{{ $totais['total'] }}</span>
        </div>

        {{-- A cobranca ja existe: o Portal a exibe, nao a gera. Confirmar o
             pagamento e ato do banco, e a baixa entra pela conciliacao do Master. --}}
        @if($cobranca)
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Cobrança registrada pelo financeiro da Velaro — <strong>{{ $metodoRotulo }}</strong>, com vencimento em {{ $resumo['prazo'] }}. Pague pelos dados ao lado; a confirmação chega por e-mail.</span>
          </p>
        @else
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>Ainda não há cobrança registrada para este lote. Assim que o financeiro emitir, os dados de pagamento aparecem aqui.</span>
          </p>
        @endif

        <a class="btn btn--secondary" href="{{ route('portal.suporte.create') }}">
          <x-velaro.icon name="upload" /> Já paguei — enviar comprovante
        </a>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Comprovante</h2></div>
        @if($comprovante['disponivel'])
          <div class="docfile">
            <x-velaro.icon name="doc" />
            <span>
              <strong>{{ $comprovante['nome'] }}</strong>
              <small>{{ $comprovante['data'] }}</small>
            </span>
            @if($comprovante['url'])
              <a class="docfile__ok" href="{{ $comprovante['url'] }}" download aria-label="Baixar o comprovante">↓</a>
            @else
              <b class="docfile__ok" aria-hidden="true">✓</b>
            @endif
          </div>
        @else
          <p class="lede" style="font-size:var(--text-sm)">Nenhum comprovante anexado a este lote. Se você já pagou, envie o arquivo pelo chamado de Financeiro que o time da Velaro faz a conferência.</p>
          <a class="btn btn--secondary" href="{{ route('portal.suporte.create') }}">
            <x-velaro.icon name="support" /> Enviar comprovante pelo suporte
          </a>
        @endif
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Precisa de ajuda com o pagamento?</h2></div>
        <p class="lede" style="font-size:var(--text-sm)">Divergência de valor, boleto vencido ou dúvida sobre o lote? Fale com o financeiro da Velaro.</p>
        <a class="btn btn--secondary" href="{{ route('portal.suporte.create') }}"><x-velaro.icon name="support" /> Abrir chamado · Financeiro</a>
        <a class="btn btn--secondary" href="{{ route('portal.ajuda') }}"><x-velaro.icon name="book" /> Ver artigos sobre pagamento</a>
      </div>

      <p class="notice notice--gold">
        <x-velaro.icon name="info" />
        <span>A cobrança é <strong>Velaro → lojista</strong>. A plataforma não processa pagamento do consumidor final: o cliente paga no caixa da sua loja (Anexo I §4.10).</span>
      </p>
    </div>
  </div>
</x-velaro.layouts.portal>
