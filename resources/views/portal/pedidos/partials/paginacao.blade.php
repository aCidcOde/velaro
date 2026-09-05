{{--
[Modulo: resources/views/portal/pedidos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rodape da tabela de pedidos: contagem literal do prototipo, navegacao de paginas e o tamanho de pagina em uso.
--}}
<div class="tfoot">
  <div class="pagination">
    <span class="muted">Exibindo {{ $pedidos->firstItem() ?? 0 }} a {{ $pedidos->lastItem() ?? 0 }} de {{ $pedidos->total() }} {{ $pedidos->total() === 1 ? 'pedido' : 'pedidos' }}</span>

    @if($pedidos->hasPages())
      <span class="pnums">
        @if($pedidos->onFirstPage())
          <span class="pnum" aria-hidden="true">‹</span>
        @else
          <a class="pnum" href="{{ $pedidos->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>
        @endif

        @foreach($pedidos->getUrlRange(max(1, $pedidos->currentPage() - 2), min($pedidos->lastPage(), $pedidos->currentPage() + 2)) as $numero => $url)
          <a class="pnum @if($numero === $pedidos->currentPage()) is-on @endif" href="{{ $url }}"
             @if($numero === $pedidos->currentPage()) aria-current="page" @endif>{{ $numero }}</a>
        @endforeach

        @if($pedidos->hasMorePages())
          <a class="pnum" href="{{ $pedidos->nextPageUrl() }}" rel="next" aria-label="Próxima página">›</a>
        @else
          <span class="pnum" aria-hidden="true">›</span>
        @endif
      </span>
    @endif

    <span class="muted">{{ $filtros['porPagina'] }} por página</span>
  </div>
</div>
