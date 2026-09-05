{{-- Portal do Lojista (portal_shell). Tudo aqui é escopado pelo revendedor logado.

     A moldura acompanha o estágio da jornada: aprovado, é o portal completo do
     Parceiro Premium; antes disso, os itens de negócio aparecem desabilitados com
     explicação — visíveis de propósito, porque as telas em ordem contam a
     história do cliente. Quem decide o que abre é o {@see \App\Support\EstagioDoLojista},
     perguntando ao roteador, e não uma segunda lista escrita aqui. --}}
@props(['title' => null, 'titulo' => 'Portal do Lojista'])
@php($reseller = auth()->user()?->reseller)
@php($estagio = \App\Support\EstagioDoLojista::de($reseller))
@php($bloqueados = $estagio->rotasBloqueadas())
<x-velaro.layouts.base :title="$title">
<div class="shell">
  <aside class="sidebar">
    <div class="sidebar__brand"><x-velaro.logo :size="30" /><x-velaro.wordmark :size="19" /></div>
    <nav class="nav"><x-velaro.nav-links :items="config('velaro-nav.portal')" :bloqueados="$bloqueados" :motivo="$estagio->motivoDoBloqueio()" /></nav>
    <div class="brandbox">
      <div class="brandbox__logo">
        <span class="brandbox__mark">{{ mb_strtoupper(mb_substr($reseller?->trade_name ?? 'L', 0, 1)) }}</span>
        <span style="display:grid;line-height:1.1">
          <strong style="font-family:var(--font-display);font-size:14px;letter-spacing:.22em;color:var(--color-gold-200);font-weight:500">{{ mb_strtoupper($reseller?->trade_name ?? 'Sua loja') }}</strong>
          <small style="font-size:8px;letter-spacing:.28em;color:rgba(255,255,255,.42)">{{ mb_strtoupper($estagio->rotuloDoPlano()) }}</small></span>
      </div>
      {{-- Antes da aprovação não existe código de revendedor: o identificador do
           lojista é o protocolo da solicitação, que é também o que ele informa
           ao falar com a equipe. --}}
      <dl style="margin:0">
        @if($estagio->aprovado())
          <dt>Cód. revendedor</dt><dd class="num">{{ $reseller?->code ?? '—' }}</dd>
          <dt>Plano</dt><dd>{{ $estagio->rotuloDoPlano() }}</dd>
        @else
          <dt>Protocolo</dt><dd class="num">{{ $reseller?->protocol ?? '—' }}</dd>
          <dt>Situação</dt><dd>{{ $estagio->rotuloDoPlano() }}</dd>
        @endif
      </dl>
    </div>
    <div class="helpbox"><x-velaro.icon name="support" style="color:var(--color-gold-300)" /><div><strong>Precisa de ajuda?</strong><p>Fale com nosso time sempre que precisar.</p></div></div>
  </aside>
  <div>
    <header class="topbar">
      <x-velaro.mobile-nav :items="config('velaro-nav.portal')" :bloqueados="$bloqueados" :motivo="$estagio->motivoDoBloqueio()" />
      <span class="eyebrow topbar__identity" style="color:var(--color-gold-300)">{{ $titulo }}</span>
      {{-- A busca varre pedido, cliente e produto — nada disso existe antes da
           aprovação, e uma barra que não acha nada só frustra. --}}
      @if($estagio->aprovado())
        <x-velaro.search placeholder="Buscar pedido, cliente ou produto…" />
      @else
        <span class="push"></span>
      @endif
      <div class="row push topbar__actions">
        {{-- O atendimento do portal é uma das 18 rotas fechadas: antes da
             aprovação o botão leva ao canal público, que atende quem ainda não
             tem chamado para abrir. --}}
        <a class="btn btn--gold btn--sm" href="{{ $estagio->aprovado() ? route('portal.suporte.create') : route('site.contato') }}">Solicitar atendimento</a>
        <span class="avatar" style="background:var(--color-gold-500);color:#06110f">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span>
        <span style="display:grid;line-height:1.2"><strong style="font-size:var(--text-sm);color:#fff">{{ $reseller?->trade_name ?? auth()->user()->name }}</strong><small style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">{{ $estagio->rotuloDoPlano() }}</small></span>
      </div>
    </header>
    <main class="main">{{ $slot }}</main>
  </div>
</div>
</x-velaro.layouts.base>
