{{--
[Modulo: resources/views/vitrine/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Painel .cart da tela 2.10: linhas, gravacao a parte, as quatro linhas de valor e o registro do pedido pago no caixa.
--}}
{{-- Tela 2.10 — o carrinho do atendimento presencial em tablet.

     Duas coisas explicam a marcação daqui:

     1. O stepper, a lixeira e os rádios de gravação são LINKS. O grupo `vitrine.`
        tem uma rota POST só, a de registrar o pedido; as demais ações chegam
        como parâmetro de `GET /loja/{slug}/carrinho` e o controller responde com
        redirect para a URL limpa (PRG).
     2. O único <form> desta tela POSTa para `vitrine.finalizar` e **não cobra
        nada** (regra 2 da tela 2.10): não há campo de cartão, de Pix nem de
        link de pagamento. O pedido nasce e o dinheiro é recebido no caixa da
        loja. --}}
<aside class="cart">
  <header class="cart__head">
    <x-velaro.icon name="cart" class="ic" style="color:var(--shop-primary)" />
    <h3>Carrinho de compras</h3>
    <span class="chip chip--flat chip--neutral push">
      {{ $carrinho['pecas'] }} {{ $carrinho['pecas'] === 1 ? 'item' : 'itens' }}
    </span>

    {{-- O "X para fechar" da seção 5 da tela 2.10. É um link para a vitrine, e
         não um botão que esconde a coluna: o painel só existe em /carrinho, e o
         carrinho mora na sessão — fechar não descarta peça nenhuma. --}}
    <a href="{{ $carrinho['urlFechar'] }}" aria-label="Fechar o carrinho e voltar à loja"
       style="display:inline-flex;color:var(--shop-muted)">
      <x-velaro.icon name="x" class="ic" />
    </a>
  </header>

  {{-- O <form> é o corpo inteiro do painel para que os campos da gravação e os
       da identificação fiquem no mesmo envio. O flex column repõe o que o
       `.cart` esperava dos filhos diretos. --}}
  <form method="POST" action="{{ $carrinho['urlFinalizar'] }}" novalidate
        style="display:flex;flex-direction:column;flex:1;min-width:0;
               --action:var(--shop-primary);--border:var(--shop-border);--ink:var(--shop-text)">
    @csrf

    <div class="cart__body">
      @if($carrinho['aviso'])
        <p class="pickup" role="status">
          <x-velaro.icon name="info" class="ic" />
          <span>{{ $carrinho['aviso'] }}</span>
        </p>
      @endif

      @error('carrinho')
        <p class="pickup" role="alert" style="border-color:#e6b3ad;background:#fdf3f2">
          <x-velaro.icon name="info" class="ic" style="color:#b42318" />
          <span>{{ $message }}</span>
        </p>
      @enderror

      @if($carrinho['vazio'])
        <p class="pickup">
          <x-velaro.icon name="bag" class="ic" />
          <span><strong style="color:var(--shop-text)">O carrinho está vazio.</strong>
            Escolha uma peça na grade ao lado e abra a ficha para somá-la ao atendimento.</span>
        </p>
      @endif

      @foreach($carrinho['linhas'] as $linha)
        <div class="line">
          <span class="line__img">
            @if($linha['imagem'])
              <img src="{{ $linha['imagem']['src'] }}" alt="{{ $linha['imagem']['alt'] }}" loading="lazy"
                   style="width:100%;height:100%;object-fit:contain">
            @else
              <x-velaro.ring :alt="$linha['nome']" style="width:100%;height:100%;object-fit:contain" />
            @endif
          </span>

          <div>
            <h5><a href="{{ $linha['urlProduto'] }}" style="color:var(--shop-text)">{{ $linha['nome'] }}</a></h5>
            <small>{{ $linha['especificacao'] }}</small>

            {{-- Stepper. `−` com quantidade 1 leva a zero, e zero é o mesmo que
                 tirar a linha — é o que o vendedor espera do botão. --}}
            <span class="qty">
              <a href="{{ $linha['urlMenos'] }}" aria-label="Diminuir a quantidade de {{ $linha['nome'] }}"
                 style="display:grid;place-items:center;width:30px;height:30px;color:var(--shop-muted)">&minus;</a>
              <span class="num">{{ $linha['quantidade'] }}</span>
              @if($linha['urlMais'])
                <a href="{{ $linha['urlMais'] }}" aria-label="Aumentar a quantidade de {{ $linha['nome'] }}"
                   style="display:grid;place-items:center;width:30px;height:30px;color:var(--shop-muted)">+</a>
              @else
                <span aria-hidden="true"
                      style="display:grid;place-items:center;width:30px;height:30px;color:var(--shop-border)">+</span>
              @endif
            </span>
          </div>

          {{-- Valor e lixeira dividem a terceira coluna: `.line` é um grid de
               três, e um quarto filho quebraria o alinhamento da linha. --}}
          <span style="display:grid;justify-items:end;gap:6px">
            <span class="money">{{ $linha['valor'] }}</span>
            <a href="{{ $linha['urlRemover'] }}" aria-label="Remover {{ $linha['nome'] }} do carrinho"
               style="color:var(--shop-muted);display:inline-flex">
              <x-velaro.icon name="trash" class="ic" />
            </a>
          </span>
        </div>
      @endforeach

      {{-- Gravação adicional: opcional, escolhida com o cliente e **cobrada à
           parte**, uma vez por aliança (regra 3 da tela 2.10). O limite de
           caracteres e o preço vêm de `settings`, não do código. --}}
      @if($carrinho['gravacao']['disponivel'])
        <div class="engrave">
          <header>
            <h4>Gravação adicional</h4>
            <span style="font-size:11px;color:var(--shop-muted)">(opcional)</span>
          </header>

          <p style="margin:0 0 10px;font-size:var(--text-sm);color:var(--shop-muted)">Deseja gravação adicional?</p>

          <a class="opt" href="{{ $carrinho['gravacao']['urlSim'] }}" style="color:var(--shop-text)">
            <span @class(['radio', 'is-on' => $carrinho['gravacao']['ativa']])></span> Sim, desejo gravação
          </a>
          <a class="opt" href="{{ $carrinho['gravacao']['urlNao'] }}" style="color:var(--shop-text)">
            <span @class(['radio', 'is-on' => ! $carrinho['gravacao']['ativa']])></span> Não, obrigado
          </a>

          @if($carrinho['gravacao']['ativa'])
            <div class="grid2">
              <div>
                <label for="engraving-text" style="font-size:var(--text-xs);color:var(--shop-muted);display:block;margin-bottom:4px">Texto / nome</label>
                <input class="input" type="text" id="engraving-text" name="engraving_text"
                       value="{{ old('engraving_text') }}" placeholder="Ana &amp; Pedro"
                       @if($carrinho['gravacao']['maxChars']) maxlength="{{ $carrinho['gravacao']['maxChars'] }}" @endif>
                @error('engraving_text')<small style="color:#b42318;font-size:11px">{{ $message }}</small>@enderror
              </div>
              <div>
                <label for="engraving-date" style="font-size:var(--text-xs);color:var(--shop-muted);display:block;margin-bottom:4px">Data</label>
                <input class="input" type="date" id="engraving-date" name="engraving_date" value="{{ old('engraving_date') }}">
                @error('engraving_date')<small style="color:#b42318;font-size:11px">{{ $message }}</small>@enderror
              </div>
            </div>
          @endif

          <div class="spread" style="margin-top:8px">
            <span class="counter">
              @if($carrinho['gravacao']['maxChars']) Limite: {{ $carrinho['gravacao']['maxChars'] }} caracteres · @endif
              cobrada à parte por aliança ({{ $carrinho['gravacao']['pecas'] }}).
            </span>
            @if($carrinho['gravacao']['preco'])
              <strong class="num" style="font-size:var(--text-sm);color:var(--shop-text)">{{ $carrinho['gravacao']['preco'] }}</strong>
            @endif
          </div>
        </div>
      @endif
    </div>

    {{-- As quatro linhas de valor e o TOTAL, na ordem do protótipo. A conta que
         fecha é sempre a mesma: total = subtotal + gravação + frete − descontos. --}}
    <div class="totals">
      <div class="row"><span>Subtotal</span><strong>{{ $carrinho['totais']['subtotal'] }}</strong></div>
      <div class="row"><span>Adicional de gravação</span><strong>{{ $carrinho['totais']['gravacao'] }}</strong></div>
      <div class="row"><span>Frete</span><strong style="color:#0a7a48">{{ $carrinho['totais']['frete'] }}</strong></div>
      <div class="row"><span>Descontos</span><strong>{{ $carrinho['totais']['desconto'] }}</strong></div>
      <div class="grand"><span>Total</span><strong>{{ $carrinho['totais']['total'] }}</strong></div>
    </div>

    <div class="cart__foot">
      {{-- Identificação do cliente final. Ele não tem login: fica cadastrado na
           carteira desta loja e acompanha o pedido pelo WhatsApp. --}}
      <div class="sfields">
        <label class="sfield sfield--full" for="cliente-nome">
          <span>Nome completo<i>*</i></span>
          <input class="input" type="text" id="cliente-nome" name="name" value="{{ old('name') }}"
                 maxlength="255" autocomplete="off" placeholder="Como está no documento">
          @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
        </label>

        <label class="sfield" for="cliente-whatsapp">
          <span>WhatsApp<i>*</i></span>
          <input class="input" type="tel" id="cliente-whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                 maxlength="50" inputmode="tel" autocomplete="off" placeholder="(00) 00000-0000">
          <small>Canal do aviso de retirada.</small>
          @error('whatsapp')<small style="color:#b42318">{{ $message }}</small>@enderror
        </label>

        <label class="sfield" for="cliente-documento">
          <span>CPF<i>*</i></span>
          <input class="input" type="text" id="cliente-documento" name="document" value="{{ old('document') }}"
                 maxlength="20" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00">
          <small>Usado na nota e na retirada.</small>
          @error('document')<small style="color:#b42318">{{ $message }}</small>@enderror
        </label>

        <label class="sfield" for="cliente-email">
          <span>E-mail</span>
          <input class="input" type="email" id="cliente-email" name="email" value="{{ old('email') }}"
                 maxlength="255" autocomplete="off" placeholder="opcional">
          <small>Opcional — segunda via do comprovante.</small>
          @error('email')<small style="color:#b42318">{{ $message }}</small>@enderror
        </label>

        <label class="sfield" for="cliente-casamento">
          <span>Data do casamento</span>
          <input class="input" type="date" id="cliente-casamento" name="wedding_date" value="{{ old('wedding_date') }}">
          <small>Opcional.</small>
          @error('wedding_date')<small style="color:#b42318">{{ $message }}</small>@enderror
        </label>
      </div>

      {{-- Consentimento LGPD, opcional e separado: o aviso de retirada é
           transacional e não depende dele. --}}
      <label class="scheck" for="cliente-aceite" style="cursor:pointer">
        <input type="checkbox" id="cliente-aceite" name="accept_marketing" value="1"
               @checked(old('accept_marketing')) style="width:17px;height:17px;flex:none;accent-color:var(--shop-primary)">
        <span>Aceito receber novidades e ofertas da <strong style="color:var(--shop-text)">{{ $loja->name }}</strong> por WhatsApp.</span>
      </label>

      <p class="pickup">
        <x-velaro.icon name="store" class="ic" />
        <span><strong style="color:var(--shop-text)">
          @if($carrinho['retirada']['apenasRetirada']) Retirada exclusiva na loja. @else Entrega combinada com a loja. @endif
        </strong> {{ $carrinho['retirada']['aviso'] }}</span>
      </p>

      {{-- O botão registra o pedido. Ele NÃO cobra: o texto é a orientação de
           onde o pagamento acontece, e é o que o protótipo escreve. --}}
      <button class="btn-checkout" type="submit" @disabled($carrinho['vazio'])>
        <x-velaro.icon name="store" class="ic" />
        Pagamento realizado no caixa da loja
      </button>

      <p class="cart__note">Esta loja não processa Pix, cartão ou link de pagamento.
        O recebimento é feito no balcão, pela nossa equipe.</p>
    </div>
  </form>
</aside>
