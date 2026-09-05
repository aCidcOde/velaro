{{--
[Modulo: resources/views/portal/precos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Barra de filtros da tabela de precos: busca, colecao, material e acabamento — sempre GET.
--}}
{{-- GET separado do formulário de gravação: filtro entra na URL, para o lojista
     poder guardar o link da visão que ele usa todo dia. --}}
<form class="filters" method="GET" action="{{ route('portal.precos.edit') }}" role="search">
  <input type="hidden" name="aba" value="{{ $filtros['aba'] }}">
  <input type="hidden" name="por_pagina" value="{{ $filtros['por_pagina'] }}">

  <span class="input-shell" style="flex:1;min-width:240px">
    <x-velaro.icon name="search" class="ic input-shell__icon" />
    <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
           placeholder="Buscar por produto, código ou referência…"
           aria-label="Buscar por produto, código ou referência">
  </span>

  <label class="fbox"><span>Coleção</span>
    <select class="select select--compact" name="colecao">
      <option value="">Todas</option>
      @foreach($opcoes['colecoes'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Material</span>
    <select class="select select--compact" name="material">
      <option value="">Todos</option>
      @foreach($opcoes['materiais'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Acabamento</span>
    <select class="select select--compact" name="acabamento">
      <option value="">Todos</option>
      @foreach($opcoes['acabamentos'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($opcao['selecionado'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <div class="row row--wrap push">
    <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
    <a class="btn btn--secondary btn--sm" href="{{ route('portal.precos.edit') }}">Limpar</a>
  </div>
</form>
