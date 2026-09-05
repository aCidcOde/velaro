{{--
[Modulo: resources/views/portal/precos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
As tres abas da tela 2.7: produtos com custo Velaro e preco sugerido, por colecao e regras de margem.
--}}
@if($filtros['aba'] === $abaProdutos)

  <div class="table-scroll"><table class="table">
    <thead><tr>
      <th>Produto</th>
      <th>Coleção</th>
      <th class="cell-num">Custo Velaro</th>
      <th class="cell-num">Margem (%)</th>
      <th class="cell-num">Markup (%)</th>
      <th class="cell-num">Preço sugerido</th>
      <th>Status</th>
      <th class="cell-num">Ações</th>
    </tr></thead>
    <tbody>
    @forelse($linhas as $linha)
      <tr>
        <td>
          <div class="row" style="gap:10px">
            <span class="thumb"><x-velaro.ring thumb :alt="$linha['nome']" style="width:100%;height:auto" /></span>
            <span><strong style="color:var(--ink)">{{ $linha['nome'] }}</strong><br>
              <small class="muted">{{ $linha['referencia'] }}</small></span>
          </div>
        </td>
        <td>{{ $linha['colecao'] }}</td>
        {{-- O custo B2B aparece: é a tela em que o lojista vê quanto paga. --}}
        <td class="cell-num"><span class="num">{{ $linha['custo_fmt'] }}</span></td>
        <td class="cell-num"><span class="inline-edit num">{{ $linha['margem_fmt'] }}</span></td>
        <td class="cell-num"><span class="num">{{ $linha['markup_fmt'] }}</span></td>
        <td class="cell-num"><span class="inline-edit inline-edit--on num">{{ $linha['preco_fmt'] }}</span></td>
        <td><span class="chip {{ $linha['status_chip'] }}">{{ $linha['status_rotulo'] }}</span></td>
        <td class="cell-num">
          <a class="link-gold" href="{{ route('portal.catalogo') }}" aria-label="Ver {{ $linha['nome'] }} no catálogo">Ver</a>
        </td>
      </tr>
    @empty
      <tr><td colspan="8"><p class="muted" style="margin:0">Nenhum produto encontrado com estes filtros.</p></td></tr>
    @endforelse
    </tbody>
  </table></div>

  <div class="tfoot">
    <div class="pagination">
      <span class="muted">
        @if($produtos->total() > 0)
          Exibindo {{ $produtos->firstItem() }} a {{ $produtos->lastItem() }} de {{ $produtos->total() }} produtos
        @else
          Nenhum produto no filtro atual
        @endif
      </span>
      <span class="pnums">
        @if($produtos->onFirstPage())
          <span class="pnum" aria-hidden="true">‹</span>
        @else
          <a class="pnum" href="{{ $produtos->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>
        @endif
        @foreach($produtos->getUrlRange(max(1, $produtos->currentPage() - 2), min($produtos->lastPage(), $produtos->currentPage() + 2)) as $numero => $url)
          <a class="pnum @if($numero === $produtos->currentPage()) is-on @endif" href="{{ $url }}"
             @if($numero === $produtos->currentPage()) aria-current="page" @endif>{{ $numero }}</a>
        @endforeach
        @if($produtos->hasMorePages())
          <a class="pnum" href="{{ $produtos->nextPageUrl() }}" rel="next" aria-label="Próxima página">›</a>
        @else
          <span class="pnum" aria-hidden="true">›</span>
        @endif
      </span>

      <form method="GET" action="{{ route('portal.precos.edit') }}">
        @foreach(['q' => $filtros['q'], 'colecao' => $filtros['colecao'], 'material' => $filtros['material'], 'acabamento' => $filtros['acabamento'], 'aba' => $filtros['aba']] as $campo => $valor)
          @if($valor)<input type="hidden" name="{{ $campo }}" value="{{ $valor }}">@endif
        @endforeach
        <select class="select select--compact" name="por_pagina" onchange="this.form.submit()"
                aria-label="Produtos por página">
          @foreach($porPaginaOpcoes as $opcao)
            <option value="{{ $opcao }}" @selected($filtros['por_pagina'] === $opcao)>{{ $opcao }} por página</option>
          @endforeach
        </select>
      </form>
    </div>
  </div>

@elseif($filtros['aba'] === $abaColecoes)

  <div class="table-scroll"><table class="table">
    <thead><tr>
      <th>Coleção</th>
      <th class="cell-num">Produtos</th>
      <th class="cell-num">Custo Velaro médio</th>
      <th class="cell-num">Margem média</th>
      <th class="cell-num">Preço sugerido médio</th>
    </tr></thead>
    <tbody>
    @forelse($colecoes as $linha)
      <tr>
        <td><strong style="color:var(--ink)">{{ $linha['colecao'] }}</strong></td>
        <td class="cell-num"><span class="num">{{ $linha['produtos'] }}</span></td>
        <td class="cell-num"><span class="num">{{ $linha['custo_medio_fmt'] }}</span></td>
        <td class="cell-num"><span class="num">{{ $linha['margem_media_fmt'] }}</span></td>
        <td class="cell-num"><span class="cell-strong num">{{ $linha['preco_medio_fmt'] }}</span></td>
      </tr>
    @empty
      <tr><td colspan="5"><p class="muted" style="margin:0">Nenhuma coleção ativa no catálogo.</p></td></tr>
    @endforelse
    </tbody>
  </table></div>

@else

  {{-- As exceções deste lojista, e só dele: a regra de preço é o segredo
       comercial da loja e nunca aparece para outro revendedor. --}}
  <div class="table-scroll"><table class="table">
    <thead><tr>
      <th>Alcance</th>
      <th>Aplica a</th>
      <th>Modo</th>
      <th class="cell-num">Valor</th>
      <th class="cell-num">Prioridade</th>
      <th>Situação</th>
    </tr></thead>
    <tbody>
    @forelse($regras as $regra)
      <tr>
        <td><strong style="color:var(--ink)">{{ $regra['escopo'] }}</strong></td>
        <td>{{ $regra['alvo'] }}</td>
        <td>{{ $regra['modo'] }}</td>
        <td class="cell-num"><span class="num">{{ $regra['valor'] }}</span></td>
        <td class="cell-num"><span class="num">{{ $regra['prioridade'] }}</span></td>
        <td>
          @if($regra['ativa'])
            <span class="chip chip--ok">Ativa</span>
          @else
            <span class="chip chip--neutral">Inativa</span>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="6"><p class="muted" style="margin:0">Nenhuma regra de exceção cadastrada — o catálogo
        inteiro segue a configuração global acima.</p></td></tr>
    @endforelse
    </tbody>
  </table></div>

  <p class="notice notice--gold" style="margin-top:var(--space-3)"><x-velaro.icon name="info" /><span>A regra mais
    específica vence: <strong>produto</strong> antes de <strong>coleção</strong>, e coleção antes da regra global.
    Empate na mesma faixa é decidido pela prioridade.</span></p>

@endif
