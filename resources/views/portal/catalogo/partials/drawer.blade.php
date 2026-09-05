{{--
[Modulo: resources/views/portal/catalogo/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Painel lateral da peca: galeria, custo para o lojista, ficha tecnica, saldo por aro e entrada no pedido.
--}}
<aside class="drawer" id="ficha" aria-label="Detalhe da peça">
  @if($ficha === null)
    {{-- Sem `?ver=SKU` o painel fica no estado de repouso: ele é parte fixa do
         layout da tela, e sumir com ele faria a grade saltar de largura. --}}
    <header class="drawer__head">
      <div>
        <h2 class="title">Detalhe da peça</h2>
        <p class="drawer__sub">Escolha um modelo na grade para ver a ficha completa.</p>
      </div>
    </header>
    <div class="drawer__body">
      <p class="notice notice--info">
        <x-velaro.icon name="eye" />
        <span>Clique em <strong>Ver detalhes</strong> em qualquer card para abrir aqui a galeria, o custo,
          a ficha técnica e o saldo por aro.</span>
      </p>
    </div>
  @else
    <header class="drawer__head">
      <div>
        <h2 class="title">{{ $ficha['nome'] }}</h2>
        <p class="drawer__sub">Ref. {{ $ficha['sku'] }}</p>
      </div>
      <span class="chip {{ $ficha['disponibilidade']['chip'] }}">{{ $ficha['disponibilidade']['rotulo'] }}</span>
      <a class="drawer__x" href="{{ route('portal.catalogo') }}" aria-label="Fechar detalhe"><x-velaro.icon name="x" /></a>
    </header>

    <div class="drawer__body">
      <div class="prod__img" style="height:170px">
        @if($ficha['capa'])
          <img src="{{ $ficha['capa']['src'] }}" alt="{{ $ficha['capa']['alt'] }}"
               style="width:100%;height:100%;object-fit:contain">
        @else
          <x-velaro.ring :alt="$ficha['nome']" style="width:100%;height:100%;object-fit:contain" />
        @endif
      </div>

      @if(count($ficha['miniaturas']) > 1)
        <div class="row row--wrap" style="gap:6px">
          @foreach($ficha['miniaturas'] as $miniatura)
            <span class="thumb" style="width:52px;height:52px">
              <img src="{{ $miniatura['src'] }}" alt="{{ $miniatura['alt'] }}" loading="lazy"
                   style="width:100%;height:100%;object-fit:contain">
            </span>
          @endforeach
        </div>
      @endif

      <div>
        <span class="eyebrow">Custo para o lojista</span>
        <div class="money money--action" style="margin-top:4px">{{ $ficha['custo'] }}</div>
        <small class="muted" style="font-size:var(--text-xs)">Preço interno. Não exibir a clientes.</small>
      </div>

      @foreach($ficha['ficha'] as $linha)
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon :name="$linha['icone']" /> {{ $linha['rotulo'] }}</span>
          <span class="datarow__v">{{ $linha['valor'] }}</span>
        </div>
      @endforeach

      @if($ficha['aros'] !== [])
        {{-- Saldo por aro, lido de stock_items. O portal consulta; o controle
             físico do cofre é da Velaro (regra 2 da tela 2.2). --}}
        <div>
          <span class="eyebrow">Disponibilidade por aro</span>
          <div class="row row--wrap" style="gap:6px;margin-top:8px">
            @foreach($ficha['aros'] as $aro)
              <span class="chip chip--flat {{ $aro['disponivel'] ? 'chip--ok' : 'chip--neutral' }}">
                Aro {{ $aro['aro'] }} · {{ $aro['saldo'] }}
              </span>
            @endforeach
          </div>
        </div>
      @endif

      {{-- O item entra no pedido na tela 2.5, que é onde o pedido é montado; daqui
           seguem só o SKU e a quantidade. O passo −/+ é o do próprio `number`:
           um par de botões falsos, sem JS por trás, seria pior que nenhum. --}}
      <form method="GET" action="{{ route('portal.pedidos.index') }}" id="adicionar-ao-pedido">
        <input type="hidden" name="produto" value="{{ $ficha['sku'] }}">
        <div class="field">
          <label for="quantidade">Quantidade (unid.)</label>
          <input id="quantidade" class="input input--compact num" type="number" name="quantidade"
                 value="1" min="1" max="999" step="1" inputmode="numeric" style="max-width:140px">
        </div>
      </form>
    </div>

    <div class="drawer__foot">
      <button class="btn btn--gold" type="submit" form="adicionar-ao-pedido">
        <x-velaro.icon name="cart" /> Adicionar ao pedido
      </button>
    </div>
  @endif
</aside>
