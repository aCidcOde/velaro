{{--
[Modulo: resources/views/vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Comprovante do pedido registrado no balcao: numero publico, itens, valores e a orientacao de pagamento no caixa.
--}}
{{-- Fecha o atendimento presencial iniciado no carrinho. `single` porque não há
     mais o que montar: o pedido já existe e esta tela é a via da loja.

     Duas coisas que esta tela NÃO faz, e não por esquecimento:
     · não cobra — o pedido nasce a pagar no caixa da loja (regra 2 da tela 2.10);
     · não mostra a ficha completa do cliente — CPF, telefone e e-mail saem
       mascarados, porque o endereço é público e o número do pedido é curto. --}}
<x-velaro.layouts.vitrine :store="$loja" :abas="$abas" :itensNoCarrinho="$sacola" single
                          :title="'Pedido '.$pedido['numero']">

  <div class="okhead">
    <span class="okhead__mark" style="background:#e8f6ee;color:#0a7a48">
      <x-velaro.icon name="check" class="ic" />
    </span>
    <div>
      <h1>Pedido registrado no balcão</h1>
      <p>Pedido <strong>{{ $pedido['numero'] }}</strong> · {{ $cliente['nome'] }} ·
        {{ $pedido['registradoEm'] }} · {{ $pedido['pecas'] }} {{ $pedido['pecas'] === 1 ? 'peça' : 'peças' }}</p>
    </div>
    <div class="okhead__tags">
      <span class="stag stag--ok"><x-velaro.icon name="check" class="ic" /> {{ $pedido['operacional'] }}</span>
      <span @class(['stag', 'stag--ok' => $pedido['pagoNoCaixa'], 'stag--wait' => ! $pedido['pagoNoCaixa']])>
        <x-velaro.icon name="store" class="ic" /> {{ $pedido['pagamento'] }}
      </span>
    </div>
  </div>

  <div class="stepline">
    @foreach($etapas as $etapa)
      <span class="{{ $etapa['estado'] }}"><x-velaro.icon name="check" class="ic" /> {{ $etapa['rotulo'] }}</span>
    @endforeach
  </div>

  <div class="pdp2">
    <div class="stack" style="gap:var(--space-4)">

      {{-- 1 · Identificação do cliente final --}}
      <div class="sbox">
        <header>
          <h3>Cliente</h3>
          <span class="stag stag--ok push"><x-velaro.icon name="user" class="ic" /> Cadastrado nesta loja</span>
        </header>
        <div>
          <div class="srow"><span>Nome</span><b>{{ $cliente['nome'] }}</b></div>
          @if($cliente['whatsapp'])
            <div class="srow"><span>WhatsApp</span><b>{{ $cliente['whatsapp'] }}</b></div>
          @endif
          @if($cliente['documento'])
            <div class="srow"><span>CPF</span><b>{{ $cliente['documento'] }}</b></div>
          @endif
          @if($cliente['email'])
            <div class="srow"><span>E-mail</span><b>{{ $cliente['email'] }}</b></div>
          @endif
          @if($cliente['dataCasamento'])
            <div class="srow"><span>Data do casamento</span><b>{{ $cliente['dataCasamento'] }}</b></div>
          @endif
        </div>
        <p class="pdp2__note" style="margin:0">Os dados aparecem reduzidos neste comprovante. A ficha completa
          fica com a nossa equipe.</p>
        <p class="pickup">
          <x-velaro.icon name="whats" class="ic" />
          <span>Você <strong style="color:var(--shop-text)">não precisa de login</strong>: o aviso de retirada
            chega no seu WhatsApp assim que o pedido estiver na loja.</span>
        </p>
      </div>

      {{-- 3 · Prazo e retirada --}}
      @if($prazo !== [])
        <div class="sbox">
          <header><h3>Prazo e retirada</h3></header>
          <div>
            @foreach($prazo as $linha)
              <div class="srow"><span>{{ $linha['rotulo'] }}</span><b>{{ $linha['valor'] }}</b></div>
            @endforeach
          </div>
          <p class="pickup">
            <x-velaro.icon name="store" class="ic" />
            <span><strong style="color:var(--shop-text)">
              @if($retirada['apenasRetirada']) Retirada exclusiva na loja. @else Entrega combinada com a loja. @endif
            </strong> {{ $retirada['aviso'] }}</span>
          </p>
        </div>
      @endif
    </div>

    {{-- 2 · Comprovante do pedido --}}
    <div class="sbox">
      <header>
        <h3>Comprovante do pedido</h3>
        <span class="stag push"><x-velaro.icon name="doc" class="ic" /> Via da loja</span>
      </header>

      <div>
        <div class="srow"><span>Número do pedido</span><b>{{ $pedido['numero'] }}</b></div>
        <div class="srow"><span>Registrado em</span><b>{{ $pedido['registradoEm'] }}</b></div>
      </div>

      <div>
        <h3>Itens</h3>
        <div class="stack" style="gap:var(--space-3);margin-top:var(--space-3)">
          @foreach($itens as $item)
            <div class="line">
              <span class="line__img">
                @if($item['imagem'])
                  <img src="{{ $item['imagem']['src'] }}" alt="{{ $item['imagem']['alt'] }}" loading="lazy"
                       style="width:100%;height:100%;object-fit:contain">
                @else
                  <x-velaro.ring :alt="$item['nome']" style="width:100%;height:100%;object-fit:contain" />
                @endif
              </span>
              <div>
                <h5>{{ $item['nome'] }}</h5>
                <small>{{ $item['especificacao'] }} · {{ $item['quantidade'] }}x</small>
              </div>
              <span class="money">{{ $item['valor'] }}</span>
            </div>
          @endforeach

          {{-- Gravação discriminada à parte, como no carrinho: quantas peças
               levam e quanto custou ao todo. --}}
          @if($gravacao)
            <p class="pickup">
              <x-velaro.icon name="edit" class="ic" />
              <span><strong style="color:var(--shop-text)">Gravação “{{ $gravacao['texto'] }}”@if($gravacao['data']) · {{ $gravacao['data'] }}@endif</strong>
                — {{ $gravacao['pecas'] }} {{ $gravacao['pecas'] === 1 ? 'peça' : 'peças' }} · {{ $gravacao['valor'] }}</span>
            </p>
          @endif
        </div>
      </div>

      <div>
        <div class="srow"><span>Subtotal</span><b>{{ $valores['subtotal'] }}</b></div>
        <div class="srow"><span>Adicional de gravação</span><b>{{ $valores['gravacao'] }}</b></div>
        <div class="srow"><span>Frete</span><b>{{ $valores['frete'] }}</b></div>
        <div class="srow"><span>Descontos</span><b>{{ $valores['desconto'] }}</b></div>
        <div class="srow"><span>Total</span>
          <b style="font-size:19px;color:var(--shop-primary)">{{ $valores['total'] }}</b></div>
        <div class="srow"><span>Pagamento</span><b>{{ $valores['pagamento'] }}</b></div>
      </div>

      <p class="pickup">
        <x-velaro.icon name="info" class="ic" />
        <span><strong style="color:var(--shop-text)">O pagamento é feito no caixa da loja.</strong>
          Nada é cobrado por esta página — nem Pix, nem cartão, nem link.</span>
      </p>
    </div>
  </div>

  {{-- O protótipo tem um "Enviar por WhatsApp" apontando para o número do
       cliente. Ele não veio: o telefone inteiro num `href` devolveria em texto
       puro o dado que a máscara acima acabou de esconder, e esta página é
       pública. O canal oferecido é o da loja, que já é público. --}}
  <div class="okacts">
    <a class="btn-checkout" href="{{ $urlNovoAtendimento }}">
      <x-velaro.icon name="bag" class="ic" /> Novo atendimento
    </a>
    @if($contato['whatsapp'])
      <a class="btn-ghost" href="{{ $contato['whatsappUrl'] }}" target="_blank" rel="noopener noreferrer">
        <x-velaro.icon name="support" class="ic" /> Falar com a nossa equipe
      </a>
    @endif
  </div>
</x-velaro.layouts.vitrine>
