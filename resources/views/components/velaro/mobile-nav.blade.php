{{-- Hamburger do topo (portal/master). <details>: funciona sem JS. --}}
@props(['items', 'bloqueados' => [], 'motivo' => null])
<details class="mobile-navigation"><summary aria-label="Abrir navegação"></summary><div class="mobile-navigation__panel"><nav class="nav" aria-label="Navegação principal"><x-velaro.nav-links :items="$items" :bloqueados="$bloqueados" :motivo="$motivo" /></nav></div></details>
