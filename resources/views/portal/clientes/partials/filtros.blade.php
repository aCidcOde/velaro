{{--
[Modulo: resources/views/portal/clientes/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Barra de filtros da carteira: busca por nome/CPF/e-mail/telefone, situacao, cidade/UF e periodo do cadastro.
--}}
<form class="filters" method="GET" action="{{ route('portal.clientes.index') }}" role="search">
  <span class="input-shell" style="flex:1;min-width:240px">
    <x-velaro.icon name="search" class="ic input-shell__icon" />
    <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
           placeholder="Buscar por nome, CPF, e-mail ou telefone…"
           aria-label="Buscar por nome, CPF, e-mail ou telefone">
  </span>

  <label class="fbox"><span>Status</span>
    <select class="select select--compact" name="situacao">
      <option value="">Todos</option>
      @foreach($opcoes['situacoes'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($filtros['situacao'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Cidade/UF</span>
    <select class="select select--compact" name="local">
      <option value="">Todas</option>
      @foreach($opcoes['locais'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($filtros['local'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <label class="fbox"><span>Período do cadastro</span>
    <select class="select select--compact" name="periodo">
      <option value="0">Todas</option>
      @foreach($opcoes['periodos'] as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected((string) $filtros['periodo'] === $opcao['valor'])>{{ $opcao['rotulo'] }}</option>
      @endforeach
    </select>
  </label>

  <div class="row row--wrap push">
    <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
    @if($temFiltro)
      <a class="btn btn--secondary btn--sm" href="{{ route('portal.clientes.index') }}"><x-velaro.icon name="x" /> Limpar filtros</a>
    @endif
  </div>
</form>
