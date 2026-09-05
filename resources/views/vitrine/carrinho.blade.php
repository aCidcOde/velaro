{{--
[Modulo: resources/views/vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.10 — carrinho do balcao em tablet: a grade da loja a esquerda e o painel do pedido a direita.
--}}
{{-- Sem `single`: aqui a coluna de 400px do grid da loja é ocupada pelo painel
     do carrinho, exatamente como o protótipo do tablet mostra. O vendedor
     continua navegando a grade com o cliente enquanto o pedido é montado. --}}
<x-velaro.layouts.vitrine :store="$loja" :abas="$abas" :ativo="$aba"
                          :itensNoCarrinho="$sacola" title="Carrinho de compras">

  @include('vitrine.partials.banner')
  @include('vitrine.partials.grade')

  <x-slot:aside>
    @include('vitrine.partials.carrinho-painel')
  </x-slot:aside>
</x-velaro.layouts.vitrine>
