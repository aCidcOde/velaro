{{--
[Modulo: resources/views/site/catalogo/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Barra de filtros do catalogo publico: busca, colecao, material, acabamento, largura e feitio.
--}}
<form class="filters" method="GET" action="{{ route('site.catalogo') }}" role="search">
  <span class="input-shell" style="flex:1;min-width:240px">
    <x-velaro.icon name="search" class="ic input-shell__icon" />
    <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
           placeholder="Buscar modelos, coleções ou materiais…"
           aria-label="Buscar modelos, coleções ou materiais">
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

  <div class="row row--wrap push">
    {{-- Seletor de visao do prototipo: o feitio da peca, com "Todos os modelos" como padrao. --}}
    <select class="select select--compact" name="formato" aria-label="Feitio" style="min-width:150px">
      <option value="">Todos os modelos</option>
      @foreach($opcoesDeFiltro['formatos'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
    <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
    @if(array_filter($filtros, static fn ($valor) => $valor !== null))
      <a class="btn btn--secondary btn--sm" href="{{ route('site.catalogo') }}"><x-velaro.icon name="x" /> Limpar</a>
    @endif
  </div>
</form>
