{{--
[Modulo: resources/views/portal/catalogo/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Barra de filtros da 2.2: busca, taxonomia, largura, disponibilidade, ordem, limpar e exportar.
--}}
<form class="filters" method="GET" action="{{ route('portal.catalogo') }}" role="search">
  <span class="input-shell" style="flex:1;min-width:240px">
    <x-velaro.icon name="search" class="ic input-shell__icon" />
    <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
           placeholder="Buscar produto, código ou referência…"
           aria-label="Buscar produto, código ou referência">
  </span>

  <label class="fbox"><span>Coleção</span>
    <select class="select select--compact" name="colecao">
      <option value="">Todas</option>
      @foreach($opcoesDeFiltro['colecoes'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Material</span>
    <select class="select select--compact" name="material">
      <option value="">Todos</option>
      @foreach($opcoesDeFiltro['materiais'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Acabamento</span>
    <select class="select select--compact" name="acabamento">
      <option value="">Todos</option>
      @foreach($opcoesDeFiltro['acabamentos'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Largura</span>
    <select class="select select--compact" name="largura">
      <option value="">Todas</option>
      @foreach($opcoesDeFiltro['larguras'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  {{-- Disponibilidade lê stock_items.available: o portal consulta o cofre, nunca escreve nele. --}}
  <label class="fbox"><span>Disponibilidade</span>
    <select class="select select--compact" name="disponibilidade">
      <option value="">Todas</option>
      @foreach($opcoesDeFiltro['disponibilidades'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <div class="row row--wrap push">
    <label class="fbox"><span>Ordenar por</span>
      <select class="select select--compact" name="ordenar">
        @foreach($opcoesDeFiltro['ordens'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>

    <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>

    @if($temFiltroAtivo)
      <a class="btn btn--secondary btn--sm" href="{{ $urlLimpar }}"><x-velaro.icon name="x" /> Limpar filtros</a>
    @endif

    <a class="btn btn--secondary btn--sm" href="{{ $urlExportar }}"><x-velaro.icon name="download" /> Exportar catálogo</a>
  </div>
</form>
