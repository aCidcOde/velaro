{{--
[Modulo: resources/views/vitrine/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Paginacao da grade da vitrine no padrao .pagination/.pnum, repintada pelas cores da loja.
--}}
{{-- `.pnum.is-on` do design system usa `--action`, que é o vinho da Velaro.
     Dentro da loja quem pinta é `--shop-primary`: sem esta troca a única cor
     fora da paleta do lojista na tela inteira seria a da página atual. --}}
@if($paginacao['ultima'] > 1)
  <div class="pagination" style="--action:var(--shop-primary);--border:var(--shop-border);--ink-body:var(--shop-muted)">
    <span style="color:var(--shop-muted);font-size:var(--text-sm)">
      Exibindo {{ $paginacao['de'] }} a {{ $paginacao['ate'] }} de {{ $paginacao['total'] }} peças
    </span>
    <span class="pnums">
      @if($paginacao['anterior'])
        <a class="pnum" href="{{ $paginacao['anterior'] }}" rel="prev" aria-label="Página anterior">&lsaquo;</a>
      @else
        <span class="pnum" aria-hidden="true">&lsaquo;</span>
      @endif

      @foreach($paginacao['paginas'] as $pagina)
        <a class="pnum @if($pagina['atual']) is-on @endif" href="{{ $pagina['url'] }}"
           @if($pagina['atual']) aria-current="page" @endif>{{ $pagina['numero'] }}</a>
      @endforeach

      @if($paginacao['proxima'])
        <a class="pnum" href="{{ $paginacao['proxima'] }}" rel="next" aria-label="Próxima página">&rsaquo;</a>
      @else
        <span class="pnum" aria-hidden="true">&rsaquo;</span>
      @endif
    </span>
  </div>
@endif
