{{--
[Modulo: resources/views/portal/catalogo/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Paginacao da grade do catalogo revendedor no padrao .pagination/.pnum; some quando ha uma pagina so.
--}}
@if($produtos->hasPages())
  <div class="pagination">
    <span class="muted">Exibindo {{ $produtos->firstItem() }} a {{ $produtos->lastItem() }} de {{ $produtos->total() }} peças</span>
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
  </div>
@endif
