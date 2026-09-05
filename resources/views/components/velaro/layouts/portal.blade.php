{{-- Portal do Lojista (portal_shell). Tudo aqui é escopado pelo revendedor logado. --}}
@props(['title' => null, 'titulo' => 'Portal do Lojista'])
@php($reseller = auth()->user()?->reseller)
<x-velaro.layouts.base :title="$title">
<div class="shell">
  <aside class="sidebar">
    <div class="sidebar__brand"><x-velaro.logo :size="30" /><x-velaro.wordmark :size="19" /></div>
    <nav class="nav"><x-velaro.nav-links :items="config('velaro-nav.portal')" /></nav>
    <div class="brandbox">
      <div class="brandbox__logo">
        <span class="brandbox__mark">{{ mb_strtoupper(mb_substr($reseller?->nome_fantasia ?? 'L', 0, 1)) }}</span>
        <span style="display:grid;line-height:1.1">
          <strong style="font-family:var(--font-display);font-size:14px;letter-spacing:.22em;color:var(--color-gold-200);font-weight:500">{{ mb_strtoupper($reseller?->nome_fantasia ?? 'Sua loja') }}</strong>
          <small style="font-size:8px;letter-spacing:.28em;color:rgba(255,255,255,.42)">PARCEIRO PREMIUM</small></span>
      </div>
      <dl style="margin:0"><dt>Cód. revendedor</dt><dd class="num">{{ $reseller?->code ?? '—' }}</dd><dt>Plano</dt><dd>Parceiro Premium ◆</dd></dl>
    </div>
    <div class="helpbox"><x-velaro.icon name="support" style="color:var(--color-gold-300)" /><div><strong>Precisa de ajuda?</strong><p>Fale com nosso time sempre que precisar.</p></div></div>
  </aside>
  <div>
    <header class="topbar">
      <x-velaro.mobile-nav :items="config('velaro-nav.portal')" />
      <span class="eyebrow topbar__identity" style="color:var(--color-gold-300)">{{ $titulo }}</span>
      <x-velaro.search placeholder="Buscar pedido, cliente ou produto…" />
      <div class="row push topbar__actions">
        <a class="btn btn--gold btn--sm" href="{{ route('portal.suporte.create') }}">Solicitar atendimento</a>
        <span class="avatar" style="background:var(--color-gold-500);color:#06110f">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span>
        <span style="display:grid;line-height:1.2"><strong style="font-size:var(--text-sm);color:#fff">{{ $reseller?->nome_fantasia ?? auth()->user()->name }}</strong><small style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">Parceiro Premium</small></span>
      </div>
    </header>
    <main class="main">{{ $slot }}</main>
  </div>
</div>
</x-velaro.layouts.base>
