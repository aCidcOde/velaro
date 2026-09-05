{{--
[Modulo: resources/views/portal/clientes/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rodape da tabela de clientes no padrao .pagination/.pnum, com a contagem literal do prototipo.
--}}
<div class="tfoot">
  <div class="pagination">
    <span class="muted">Mostrando {{ $clientes->firstItem() ?? 0 }} a {{ $clientes->lastItem() ?? 0 }} de {{ $clientes->total() }} {{ $clientes->total() === 1 ? 'cliente' : 'clientes' }}</span>

    @if($clientes->hasPages())
      <span class="pnums">
        @if($clientes->onFirstPage())
          <span class="pnum" aria-hidden="true">‹</span>
        @else
          <a class="pnum" href="{{ $clientes->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>
        @endif

        @foreach($clientes->getUrlRange(max(1, $clientes->currentPage() - 2), min($clientes->lastPage(), $clientes->currentPage() + 2)) as $numero => $url)
          <a class="pnum @if($numero === $clientes->currentPage()) is-on @endif" href="{{ $url }}"
             @if($numero === $clientes->currentPage()) aria-current="page" @endif>{{ $numero }}</a>
        @endforeach

        @if($clientes->hasMorePages())
          <a class="pnum" href="{{ $clientes->nextPageUrl() }}" rel="next" aria-label="Próxima página">›</a>
        @else
          <span class="pnum" aria-hidden="true">›</span>
        @endif
      </span>
    @endif
  </div>
</div>
