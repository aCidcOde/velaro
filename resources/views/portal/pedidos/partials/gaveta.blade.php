{{--
[Modulo: resources/views/portal/pedidos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Gaveta lateral da lista de pedidos: dados, gravacao, itens e as quatro linhas de valor do pedido selecionado.
--}}
{{--
  A gaveta é servida pelo servidor: o pedido aberto vem de `?pedido=` e é
  resolvido dentro do escopo do revendedor (ver PedidosService::gaveta). Número
  de outro lojista não abre gaveta nenhuma — e não abre em silêncio, porque uma
  mensagem de "não é seu" já confirmaria que o pedido existe.
--}}
<aside class="drawer" aria-labelledby="gaveta-titulo">
  <header class="drawer__head">
    <div><h2 class="title" id="gaveta-titulo">Pedido #{{ $gaveta['numero'] }}</h2></div>
    <span class="chip {{ $gaveta['operacional']['chip'] }}">{{ $gaveta['operacional']['rotulo'] }}</span>
    <a class="drawer__x" href="{{ $gaveta['fecharUrl'] }}" aria-label="Fechar o resumo do pedido"><x-velaro.icon name="x" /></a>
  </header>

  <div class="drawer__body">
    <div class="datarow">
      <span class="datarow__k">Cliente</span>
      <span class="datarow__v">{{ $gaveta['cliente'] ?? '—' }}</span>
    </div>
    <div class="datarow">
      <span class="datarow__k">Data do pedido</span>
      <span class="datarow__v">{{ $gaveta['criadoEm'] ?? '—' }}</span>
    </div>
    <div class="datarow">
      <span class="datarow__k">Entrega prevista</span>
      <span class="datarow__v">{{ $gaveta['previsao'] ?? '—' }}</span>
    </div>
    <div class="datarow">
      <span class="datarow__k">Status do pedido</span>
      <span class="datarow__v"><span class="chip {{ $gaveta['operacional']['chip'] }}">{{ $gaveta['operacional']['rotulo'] }}</span></span>
    </div>
    <div class="datarow">
      <span class="datarow__k">Status do pagamento</span>
      <span class="datarow__v"><span class="chip {{ $gaveta['pagamento']['chip'] }}">{{ $gaveta['pagamento']['rotulo'] }}</span></span>
    </div>

    <div class="engravebox">
      <div class="row" style="gap:8px">
        <x-velaro.icon :name="$gaveta['gravacao']['solicitada'] ? 'check' : 'x'" />
        <strong>Gravação interna</strong>
      </div>
      <div class="datarow">
        <span class="datarow__k">Solicitada</span>
        <span class="datarow__v">{{ $gaveta['gravacao']['solicitada'] ? 'Sim' : 'Não' }}</span>
      </div>
      @foreach($gaveta['gravacao']['textos'] as $texto)
        <div class="datarow">
          <span class="datarow__k">Texto</span>
          <span class="datarow__v">{{ $texto['texto'] }}</span>
        </div>
      @endforeach
      @if($gaveta['gravacao']['limite'])
        <div class="datarow">
          <span class="datarow__k">Limite</span>
          <span class="datarow__v">{{ $gaveta['gravacao']['limite'] }}</span>
        </div>
      @endif
      <div class="datarow">
        <span class="datarow__k">Custo adicional</span>
        <span class="datarow__v">{{ $gaveta['gravacao']['custo'] }}</span>
      </div>
    </div>

    <div>
      <span class="eyebrow">Itens do pedido ({{ count($gaveta['itens']) }})</span>
      <div class="stack" style="margin-top:8px">
        @foreach($gaveta['itens'] as $item)
          <div class="orderitem">
            <span class="thumb">
              @if($item['imagem'])
                <img src="{{ $item['imagem'] }}" alt="{{ $item['alt'] }}" loading="lazy">
              @else
                <x-velaro.ring :alt="$item['alt']" thumb />
              @endif
            </span>
            <div>
              <strong>{{ $item['nome'] }}</strong>
              @if($item['especificacao'])<small>{{ $item['especificacao'] }}</small>@endif
              @if($item['aro'])<small>Aro: {{ $item['aro'] }}</small>@endif
            </div>
            <div style="text-align:right">
              <small class="muted">Qtd: {{ $item['quantidade'] }}</small><br>
              <span class="cell-strong num">{{ $item['total'] }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- As quatro linhas do resumo são colunas de `orders`: o que a loja deve à
         Velaro está gravado no pedido, não é conta feita na tela. --}}
    <div>
      <span class="eyebrow">Resumo do pedido (custo Velaro)</span>
      @foreach($gaveta['valores']['linhas'] as $linha)
        <div class="datarow">
          <span class="datarow__k">{{ $linha['rotulo'] }}</span>
          <span class="datarow__v">
            <span class="num" @if($linha['destaque']) style="color:var(--color-success-700)" @endif>{{ $linha['valor'] }}</span>
          </span>
        </div>
      @endforeach
      <div class="spread" style="padding-top:10px">
        <strong>Total do pedido (custo Velaro)</strong>
        <span class="money money--action">{{ $gaveta['valores']['total'] }}</span>
      </div>
    </div>
  </div>

  <div class="drawer__foot">
    <a class="btn btn--secondary" href="{{ $gaveta['url'] }}">Ver detalhes</a>
    <a class="btn btn--gold" href="{{ $gaveta['pagamentoUrl'] }}"><x-velaro.icon name="coin" /> Faturamento / Pagamento</a>
  </div>
</aside>
