{{-- Painel Interno Velaro (master_shell). Perfil Master: is_admin + gate access-backend. --}}
@props(['title' => null])
<x-velaro.layouts.base :title="$title">
<div class="shell">
  <aside class="sidebar">
    <div class="sidebar__brand"><x-velaro.logo :size="30" /><x-velaro.wordmark :size="19" /></div>
    <nav class="nav"><x-velaro.nav-links :items="config('velaro-nav.master')" /></nav>
    <div class="plan">
      <div class="plan__mark"><x-velaro.logo :size="26" />
        <strong style="font-family:var(--font-display);font-size:15px;letter-spacing:.30em;color:#fff;font-weight:400">VELARO</strong>
        <small style="font-size:8px;letter-spacing:.30em;color:var(--color-gold-400)">ADMINISTRAÇÃO</small></div>
      <p style="margin:0;font-size:var(--text-sm);color:rgba(255,255,255,.72)">Plano: <strong style="color:#fff">Master</strong> ◆</p>
      <a class="btn btn--ghost-gold btn--sm" style="margin-top:10px;width:100%" href="{{ route('backend.configuracoes.index') }}">Ver meu plano</a>
    </div>
  </aside>
  <div>
    <header class="topbar">
      <x-velaro.mobile-nav :items="config('velaro-nav.master')" />
      <span class="row topbar__identity" style="gap:10px"><x-velaro.icon name="shield" style="color:var(--color-gold-400)" />
        <span style="display:grid;line-height:1.25"><strong style="font-size:var(--text-sm);color:#fff">Painel Interno</strong><small style="font-size:11px;color:rgba(255,255,255,.5)">Gestão de Revendedores</small></span></span>
      <x-velaro.search placeholder="Buscar revendedor, pedido, cliente…" />
      <div class="row push topbar__actions" style="gap:var(--space-4)">
        <a class="storeswitch" href="{{ route('backend.revendedores.index') }}"><small>Acessar loja</small><strong>Revendedores ↗</strong></a>
        <a class="impersonate" href="{{ route('portal.dashboard') }}" title="Ação auditada"><x-velaro.icon name="store" /><span><strong>Painel Revendedor</strong><small>Ver como revendedor</small></span></a>
        <a class="bell" href="{{ route('backend.suporte.index') }}"><x-velaro.icon name="bell" style="color:inherit" /></a>
        <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span>
        <span style="display:grid;line-height:1.2"><strong style="font-size:var(--text-sm);color:#fff">{{ auth()->user()->name }}</strong><small style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">Admin</small></span>
      </div>
    </header>
    <main class="main">{{ $slot }}</main>
  </div>
</div>
</x-velaro.layouts.base>
