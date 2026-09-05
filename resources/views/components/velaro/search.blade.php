{{-- Busca do topo: barra no desktop, ícone que abre no celular. --}}
@props(['placeholder' => 'Buscar…'])
<details class="topbar__search"><summary><x-velaro.icon name="search" /><span class="topbar__search__ph">{{ $placeholder }}</span><span class="kbd">Ctrl K</span></summary><div class="topbar__search__panel"><input class="input" type="search" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"></div></details>
