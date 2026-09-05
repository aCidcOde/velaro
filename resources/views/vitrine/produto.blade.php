{{--
[Modulo: resources/views/vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Ficha da peca na vitrine: galeria, ficha tecnica, aro disponivel lido do cofre e o preco B2C do lojista.
--}}
{{-- `single` porque a ficha é uma tela de leitura: o painel do carrinho abre na
     tela 2.10, para onde levam os chips de aro e o botão do rodapé. --}}
<x-velaro.layouts.vitrine :store="$loja" :abas="$abas" :ativo="$aba" :title="$produto['nome']"
                          :itensNoCarrinho="$sacola" single>

  <p class="shop__crumb">
    @foreach($trilha as $passo)
      @if($passo['url'])
        <a href="{{ $passo['url'] }}" style="color:var(--shop-muted)">{{ $passo['rotulo'] }}</a> &rsaquo;
      @else
        <b>{{ $passo['rotulo'] }}</b>
      @endif
    @endforeach
  </p>

  <div class="pdp2">
    <div class="pdp2__gal">
      <div class="pdp2__main">
        @if($imagens !== [])
          <img src="{{ $imagens[0]['src'] }}" alt="{{ $imagens[0]['alt'] }}" style="width:100%;height:auto">
        @else
          <x-velaro.ring :alt="$produto['nome']" style="width:100%;height:auto" />
        @endif
      </div>

      @if(count($imagens) > 1)
        <div class="pdp2__thumbs">
          @foreach($imagens as $indice => $imagem)
            <span @class(['is-on' => $indice === 0])>
              <img src="{{ $imagem['src'] }}" alt="{{ $imagem['alt'] }}" loading="lazy" style="width:100%;height:auto">
            </span>
          @endforeach
        </div>
      @endif

      <p class="pdp2__note">Fotos ilustrativas. A peça é produzida no aro escolhido.</p>

      @if($ficha !== [])
        <div class="sbox">
          <header><h3>Ficha técnica</h3></header>
          <div>
            @foreach($ficha as $linha)
              <div class="srow"><span>{{ $linha['rotulo'] }}</span><b>{{ $linha['valor'] }}</b></div>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    <div class="pdp2__info">
      <div>
        @if($produto['referencia'] !== '')
          <span class="pdp2__ref">{{ $produto['referencia'] }}</span>
        @endif
        <h1>
          {{ $produto['nome'] }}
          @if($favorito)
            <span role="img" aria-label="Peça favoritada" style="color:var(--shop-primary)">&hearts;</span>
          @endif
        </h1>
        @if($produto['descricao'])
          <p class="pdp2__note">{{ $produto['descricao'] }}</p>
        @endif
      </div>

      {{-- Preço ao consumidor, resolvido pelas regras do próprio lojista —
           nunca o custo que a Velaro cobra dele. A simulação de parcela é só
           texto: quem parcela é o caixa da loja, e a vitrine não cobra nada. --}}
      <div>
        @if($preco)
          <div class="pdp2__price">{{ $preco }}</div>
          @if($parcelamento)
            <p class="pdp2__note">ou <strong>{{ $parcelamento }}</strong> — parcelamento simulado, acertado no caixa da loja.</p>
          @endif
        @else
          <div class="pdp2__price">Consulte na loja</div>
          <p class="pdp2__note">O valor desta peça é informado pela nossa equipe no balcão.</p>
        @endif
      </div>

      {{-- Cada aro disponível é um link que soma a peça daquele tamanho ao
           carrinho da loja. Não é compra: é o pedido do balcão sendo montado.
           O aro riscado fica sem endereço — a peça não está pronta naquele
           tamanho, e o prazo é conversa com a equipe. --}}
      @if($aros !== [])
        <div class="sopt">
          <span>Tamanho do aro</span>
          <div class="sopt__row">
            @foreach($aros as $item)
              @if($item['url'])
                <a class="schip" href="{{ $item['url'] }}"
                   aria-label="Adicionar o aro {{ $item['aro'] }} ao carrinho">{{ $item['aro'] }}</a>
              @else
                <span class="schip is-off">{{ $item['aro'] }}</span>
              @endif
            @endforeach
          </div>
          <p class="pdp2__note">Escolha o aro para somar a peça ao carrinho da loja. Aro riscado está sem peça
            pronta: a nossa equipe confirma o prazo de produção no balcão.</p>
        </div>
      @endif

      @if($gravacao['permite'])
        <div class="engrave">
          <header><h4>Gravação adicional</h4><span style="font-size:11px;color:var(--shop-muted)">(opcional)</span></header>
          <p style="margin:0;font-size:var(--text-sm);color:var(--shop-muted)">
            Esta peça aceita gravação de texto e data.
            @if($gravacao['maxChars']) Até {{ $gravacao['maxChars'] }} caracteres. @endif
          </p>
          <div class="spread" style="margin-top:8px">
            <span class="counter">Cobrada à parte, por peça, e escolhida com a nossa equipe.</span>
            @if($gravacao['preco'])
              <strong class="num" style="font-size:var(--text-sm);color:var(--shop-text)">{{ $gravacao['preco'] }}</strong>
            @endif
          </div>
        </div>
      @endif

      {{-- O botão soma a peça ao carrinho da loja — não a uma compra online.
           Não há checkout aqui: o pedido é fechado e pago no caixa do lojista. --}}
      <a class="btn-checkout" href="{{ $urlAdicionar }}">
        <x-velaro.icon name="bag" class="ic" /> Adicionar ao carrinho
      </a>

      <a class="btn-ghost" href="{{ $urlCarrinho }}">
        <x-velaro.icon name="cart" class="ic" /> Ver o carrinho
      </a>

      <p class="pickup">
        <x-velaro.icon name="store" class="ic" />
        <span><strong style="color:var(--shop-text)">
          @if($retirada['apenasRetirada']) Retirada exclusiva na loja. @else Entrega combinada com a loja. @endif
        </strong> {{ $retirada['aviso'] }}</span>
      </p>

      @if($contato['whatsapp'])
        <a class="btn-ghost" href="{{ $contato['whatsappUrl'] }}" target="_blank" rel="noopener noreferrer">
          <x-velaro.icon name="whats" class="ic" /> Falar com a nossa equipe
        </a>
      @endif
    </div>
  </div>

  @if($relacionados !== [])
    <section class="shop__section">
      <h3>Você também pode gostar</h3>
      <div class="prods">
        @foreach($relacionados as $cartao)
          @include('vitrine.partials.card', ['cartao' => $cartao])
        @endforeach
      </div>
    </section>
  @endif
</x-velaro.layouts.vitrine>
