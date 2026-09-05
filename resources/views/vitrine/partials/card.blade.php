{{--
[Modulo: resources/views/vitrine/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Card .prod da grade da vitrine: foto, nome, ficha curta, aro disponivel e o preco B2C do lojista.
--}}
{{-- Cinco filhos, na ordem do protótipo: foto, nome, ficha, preço e ação.
     No mobile o CSS da vitrine faz a foto ocupar `grid-row: 1 / span 4` ao lado
     dessas quatro linhas — um sexto filho aqui quebraria o cartão. Por isso o
     coração mora dentro de .prod__img, e não como irmão dele. --}}
<div class="prod">
  <div class="prod__img" style="position:relative">
    @if($cartao['imagem'])
      <img src="{{ $cartao['imagem']['src'] }}" alt="{{ $cartao['imagem']['alt'] }}" loading="lazy"
           style="width:100%;height:100%;object-fit:contain">
    @else
      <x-velaro.ring :alt="$cartao['nome']" style="width:100%;height:100%;object-fit:contain" />
    @endif

    {{-- O coração é indicativo: `favorites` guarda o gosto do visitante por
         `visitor_token` (o consumidor não faz login), mas o grupo `vitrine.`
         ainda não tem rota para gravar — ver a nota em VitrineCatalogoService. --}}
    @if($cartao['favorito'])
      <span role="img" aria-label="Peça favoritada"
            style="position:absolute;top:6px;right:8px;font-size:17px;line-height:1;color:var(--shop-primary)">&hearts;</span>
    @else
      <span aria-hidden="true"
            style="position:absolute;top:6px;right:8px;font-size:17px;line-height:1;color:var(--shop-muted)">&#9825;</span>
    @endif
  </div>

  <h4>{{ $cartao['nome'] }}</h4>
  <small>{{ $cartao['especificacao'] }}@if($cartao['aro'])<br>Aro: {{ $cartao['aro'] }}@endif</small>

  {{-- Preço ao consumidor, resolvido pelas regras do lojista. Nunca o custo
       B2B. Com `show_prices` desligado o valor não sai nem daqui nem do
       service: o cliente consulta com a equipe da loja. --}}
  <span class="price num">{{ $cartao['preco'] ?? 'Consulte na loja' }}</span>

  <a class="btn btn--secondary btn--sm" href="{{ $cartao['url'] }}">Ver detalhes</a>
</div>
