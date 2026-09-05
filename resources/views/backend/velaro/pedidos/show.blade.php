{{--
[Modulo: resources/views/backend/velaro/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.6 (detalhe) — o pedido inteiro: itens, valores, os dois status, timeline, retirada e notificacoes.
--}}
<x-velaro.layouts.master :title="'Pedido #'.$pedido->public_number">

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <div class="split" style="--gcols:2fr 1fr">
    <div class="stack">
      @include('backend.velaro.pedidos._detalhe', ['voltar' => true])
    </div>
    <div class="stack">
      @include('backend.velaro.pedidos._status')
    </div>
  </div>

</x-velaro.layouts.master>
