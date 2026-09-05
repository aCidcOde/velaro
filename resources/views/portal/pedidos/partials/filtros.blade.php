{{--
[Modulo: resources/views/portal/pedidos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Barra de filtros da lista de pedidos: busca, periodo, os dois status independentes e a linha de filtros avancados.
--}}
<form method="GET" action="{{ route('portal.pedidos.index') }}" role="search" class="stack">
  <section class="filters">
    <span class="input-shell" style="flex:1;min-width:240px">
      <x-velaro.icon name="search" class="ic input-shell__icon" />
      <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
             placeholder="Buscar por número do pedido, cliente ou produto…"
             aria-label="Buscar por número do pedido, cliente ou produto">
    </span>

    <label class="fbox"><span>Período</span>
      <select class="select select--compact" name="periodo">
        @foreach($opcoes['periodos'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected((string) $filtros['periodo'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>

    {{-- Dois selects, e não um: status do pedido e status do pagamento são
         campos independentes (Anexo I §6). --}}
    <label class="fbox"><span>Status do pedido</span>
      <select class="select select--compact" name="status">
        <option value="">Todos</option>
        @foreach($opcoes['operacional'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected($filtros['status'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>

    <label class="fbox"><span>Status do pagamento</span>
      <select class="select select--compact" name="pagamento">
        <option value="">Todos</option>
        @foreach($opcoes['pagamento'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected($filtros['pagamento'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>

    <div class="row row--wrap push">
      <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
      @if($temFiltro)
        <a class="btn btn--secondary btn--sm" href="{{ route('portal.pedidos.index') }}"><x-velaro.icon name="x" /> Limpar filtros</a>
      @endif
    </div>
  </section>

  <section class="filters" aria-label="Filtros avançados">
    <span class="eyebrow" style="align-self:center">Filtros avançados</span>

    <label class="fbox"><span>Lote</span>
      <select class="select select--compact" name="lote">
        <option value="">Todos</option>
        @foreach($opcoes['lotes'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected($filtros['lote'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>

    <label class="fbox"><span>Gravação interna</span>
      <select class="select select--compact" name="gravacao">
        <option value="">Todos</option>
        @foreach($opcoes['gravacao'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected($filtros['gravacao'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>

    <label class="fbox"><span>Linhas por página</span>
      <select class="select select--compact" name="por_pagina">
        @foreach($opcoes['porPagina'] as $opcao)
          <option value="{{ $opcao['valor'] }}" @selected((string) $filtros['porPagina'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
        @endforeach
      </select>
    </label>
  </section>
</form>
