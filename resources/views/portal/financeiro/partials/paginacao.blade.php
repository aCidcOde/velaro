{{--
[Modulo: resources/views/portal/financeiro/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rodape de paginacao das tabelas do financeiro, no padrao .pagination/.pnum do design system.
--}}
<div class="pagination">
  <span class="muted">
    {{-- O rotulo vem pronto do service ("pedidos do lote 24/2026", "notas
         fiscais"), entao a contagem nunca e prefixada por artigo: "0 notas
         fiscais" concorda com qualquer rotulo, "Nenhum notas fiscais" nao. --}}
    @if($paginador->total() === 0)
      0 {{ $rotulo }}
    @elseif($paginador->isEmpty())
      Esta página não tem resultado — são {{ $paginador->total() }} {{ $rotulo }} no total
    @else
      Mostrando {{ $paginador->firstItem() }} a {{ $paginador->lastItem() }} de {{ $paginador->total() }} {{ $rotulo }}
    @endif
  </span>

  @if($paginador->hasPages())
    @php
      // A pagina corrente e presa a ultima existente antes de virar janela: com
      // `?page=999999999` o paginador responde `currentPage() = 999999999` e um
      // `getUrlRange()` centrado nele tentaria materializar um bilhao de links.
      $paginaAtual = min($paginador->currentPage(), $paginador->lastPage());
      $inicio = max(1, $paginaAtual - 2);
      $fim = min($paginador->lastPage(), $paginaAtual + 2);
    @endphp
    <span class="pnums">
      @if($paginador->onFirstPage())
        <span class="pnum" aria-hidden="true">‹</span>
      @else
        <a class="pnum" href="{{ $paginador->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>
      @endif

      @foreach($paginador->getUrlRange($inicio, $fim) as $numero => $url)
        <a class="pnum{{ $numero === $paginaAtual ? ' is-on' : '' }}" href="{{ $url }}"
           @if($numero === $paginaAtual) aria-current="page" @endif>{{ $numero }}</a>
      @endforeach

      @if($paginador->hasMorePages())
        <a class="pnum" href="{{ $paginador->nextPageUrl() }}" rel="next" aria-label="Próxima página">›</a>
      @else
        <span class="pnum" aria-hidden="true">›</span>
      @endif
    </span>
  @endif

  @isset($seletorDePagina)
    {{-- Tamanho de pagina sem JavaScript: select + botao, para o rodape continuar
         funcionando com script desligado. --}}
    <form class="row" style="gap:6px" method="GET" action="{{ $seletorDePagina['action'] }}">
      @foreach($seletorDePagina['ocultos'] as $nome => $valor)
        <input type="hidden" name="{{ $nome }}" value="{{ $valor }}">
      @endforeach
      <select class="select select--compact" name="por_pagina" aria-label="Itens por página">
        @foreach($seletorDePagina['opcoes'] as $opcao)
          <option value="{{ $opcao }}" @selected($opcao === $seletorDePagina['atual'])>{{ $opcao }} por página</option>
        @endforeach
      </select>
      <button class="btn btn--secondary btn--sm" type="submit">Aplicar</button>
    </form>
  @endisset
</div>
