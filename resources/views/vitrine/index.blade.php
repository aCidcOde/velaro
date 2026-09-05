{{--
[Modulo: resources/views/vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.9 — vitrine white label: banner da loja, abas de categoria e a grade com o preco B2C do revendedor.
--}}
{{-- `single` porque a vitrine sozinha não tem o painel do carrinho ao lado: ele
     aparece na tela 2.10, que é a mesma loja com a coluna de 400px ocupada. --}}
<x-velaro.layouts.vitrine :store="$loja" :abas="$abas" :ativo="$aba" :itensNoCarrinho="$sacola" single>

  @include('vitrine.partials.banner')
  @include('vitrine.partials.grade')

  {{-- Retirada e pagamento no balcão do lojista. A vitrine não processa Pix,
       cartão nem link de pagamento (regra 3 da tela 2.10): estes dois avisos
       são a orientação, não um meio de cobrança. --}}
  <section class="shop__section">
    <p class="pickup">
      <x-velaro.icon name="store" class="ic" />
      <span><strong style="color:var(--shop-text)">
        @if($retirada['apenasRetirada']) Retirada exclusiva na loja. @else Entrega combinada com a loja. @endif
      </strong> {{ $retirada['aviso'] }}</span>
    </p>
  </section>

  @if($contato['whatsapp'] || $contato['phone'] || $contato['email'] || $contato['address'])
    <section class="shop__section">
      <div class="sbox">
        <header><h3>Atendimento</h3></header>
        <div>
          @if($contato['address'])
            <div class="srow"><span>Endereço</span><b>{{ $contato['address'] }}</b></div>
          @endif
          @if($contato['phone'])
            <div class="srow"><span>Telefone</span><b><a href="{{ $contato['phoneUrl'] }}" style="color:var(--shop-text)">{{ $contato['phone'] }}</a></b></div>
          @endif
          @if($contato['email'])
            <div class="srow"><span>E-mail</span><b><a href="mailto:{{ $contato['email'] }}" style="color:var(--shop-text)">{{ $contato['email'] }}</a></b></div>
          @endif
        </div>

        @if($contato['whatsapp'])
          <a class="btn-ghost" href="{{ $contato['whatsappUrl'] }}" target="_blank" rel="noopener noreferrer">
            <x-velaro.icon name="whats" class="ic" /> Iniciar atendimento
          </a>
        @endif
      </div>
    </section>
  @endif
</x-velaro.layouts.vitrine>
