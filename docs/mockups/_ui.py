# -*- coding: utf-8 -*-
"""Biblioteca de componentes dos mockups Velaro.
Um shell por ambiente + blocos reutilizáveis. Cada tela vira só conteúdo."""

import html as _h
import re, importlib.util as _il

_spec = _il.spec_from_file_location("rings", "_gen_rings.py")
rings = _il.module_from_spec(_spec); _spec.loader.exec_module(rings)

FONTS = ('<link rel="preconnect" href="https://fonts.googleapis.com">\n'
 '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n'
 '<link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600'
 '&family=Inter+Tight:wght@300;400;500;600;700&display=swap" rel="stylesheet">')

CSS = ('<link rel="stylesheet" href="velaro-tokens.css">\n'
       '<link rel="stylesheet" href="velaro-ui.css">\n'
       '<link rel="stylesheet" href="velaro-screens.css">')

MOCKNAV = """
<nav class="mocknav" aria-label="Navegação entre os mockups">
  <span>Velaro</span>
  <a href="index.html">Índice</a>
  <a href="01-site-publico.html">Site</a>
  <a href="02-portal-lojista.html">Portal</a>
  <a href="03-vitrine-pdv.html">Vitrine</a>
  <a href="04-painel-master.html">Master</a>
  <a class="map" href="mapa.html">Mapa · 31 telas</a>
</nav>
<script>
(function(){
  var f = (location.pathname.split('/').pop() || 'index.html');
  var a = document.querySelector('.mocknav a[href="' + f + '"]');
  if (a) a.classList.add('is-on');
})();
</script>
"""

def e(s): return _h.escape(str(s))

# ─────────────────────────────────── ÍCONES ───────────────────────────────────
_IC = {
 "home":'<path d="M4 11l8-7 8 7v8a1 1 0 01-1 1h-4v-6H9v6H5a1 1 0 01-1-1z"/>',
 "book":'<path d="M3 5.5h7a2 2 0 012 2V19a2.4 2.4 0 00-2-1.4H3zM21 5.5h-7a2 2 0 00-2 2V19a2.4 2.4 0 012-1.4h7z"/>',
 "user":'<circle cx="12" cy="8" r="3.4"/><path d="M4.5 20a7.5 7.5 0 0115 0"/>',
 "users":'<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.3"/><path d="M3 19a6 6 0 0112 0M15 19a5 5 0 016-4.6"/>',
 "user-plus":'<circle cx="10" cy="8" r="3.2"/><path d="M3.5 20a6.5 6.5 0 0113 0M18 9v6M15 12h6"/>',
 "coin":'<circle cx="12" cy="12" r="9"/><path d="M12 7v10M14.5 9.5A2.5 2.5 0 0012 8.6c-1.4 0-2.5.8-2.5 1.9 0 2.6 5 1.4 5 4 0 1.1-1.1 1.9-2.5 1.9a2.5 2.5 0 01-2.5-.9"/>',
 "bag":'<path d="M8 4h8l1 3H7zM6 7h12l1 12a1 1 0 01-1 1H6a1 1 0 01-1-1z"/>',
 "store":'<path d="M4 9l1.5-4h13L20 9M4 9h16v10H4zM9 19v-6h6v6"/>',
 "tag":'<path d="M3 12l9-9 9 9-9 9z"/><circle cx="9" cy="9" r="1"/>',
 "promo":'<path d="M4 12V5h7l9 9-7 7z"/><circle cx="8" cy="9" r="1.3"/>',
 "chart":'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
 "support":'<path d="M4 13v-1a8 8 0 0116 0v1M4 13h3v6H5a1 1 0 01-1-1zM20 13h-3v6h2a1 1 0 001-1z"/>',
 "gear":'<circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/>',
 "box":'<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/>',
 "truck":'<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
 "doc":'<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4M9 12h6M9 16h6"/>',
 "card":'<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>',
 "search":'<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-3.6-3.6"/>',
 "check":'<path d="M4 12.5l5 5L20 6.5"/>',
 "shield":'<path d="M12 3l8 3v6c0 5-3.4 7.7-8 9-4.6-1.3-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
 "info":'<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
 "clock":'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 "calendar":'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
 "bell":'<path d="M6 9a6 6 0 1112 0v4l2 3H4l2-3z"/><path d="M10 19a2 2 0 004 0"/>',
 "brain":'<path d="M12 3a4 4 0 014 4v1a4 4 0 01-1 8H9a4 4 0 01-1-8V7a4 4 0 014-4z"/>',
 "globe":'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 010 18a15 15 0 010-18"/>',
 "cart":'<path d="M3 5h2l2.2 10.2a1.5 1.5 0 001.5 1.2h8.1a1.5 1.5 0 001.5-1.1L20 8H6"/><circle cx="9.5" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
 "download":'<path d="M12 4v10M8 11l4 4 4-4M4 19h16"/>',
 "upload":'<path d="M12 18V8M8 11l4-4 4 4M4 19h16"/>',
 "filter":'<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
 "edit":'<path d="M4 20h4l10-10-4-4L4 16z"/><path d="M14 6l4 4"/>',
 "eye":'<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.6"/>',
 "plus":'<path d="M12 5v14M5 12h14"/>',
 "x":'<path d="M6 6l12 12M18 6L6 18"/>',
 "phone":'<path d="M5 4h4l2 5-2.5 1.5a12 12 0 005 5L15 13l5 2v4a1 1 0 01-1 1A16 16 0 014 5a1 1 0 011-1z"/>',
 "mail":'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
 "pin":'<path d="M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
 "whats":'<path d="M4 20l1.3-4A8 8 0 1120 12a8 8 0 01-12.6 6.6z"/>',
 "diamond":'<path d="M6 9h12l-6 11z"/><path d="M9 9l3 5 3-5M6 9l6 3 6-3"/>',
 "factory":'<path d="M4 20V11l5 3V11l5 3V7l6 4v9z"/>',
 "sparkle":'<path d="M12 4l1.8 5.2L19 11l-5.2 1.8L12 18l-1.8-5.2L5 11l5.2-1.8z"/>',
 "ring":'<ellipse cx="12" cy="13" rx="6" ry="7"/><path d="M9 5h6l-1.5 3h-3z"/>',
 "lock":'<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',
 "refresh":'<path d="M4 12a8 8 0 0113.7-5.7L20 8M20 12a8 8 0 01-13.7 5.7L4 16"/><path d="M20 4v4h-4M4 20v-4h4"/>',
 "list":'<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
 "print":'<path d="M7 9V4h10v5M7 18H5a1 1 0 01-1-1v-5a1 1 0 011-1h14a1 1 0 011 1v5a1 1 0 01-1 1h-2M7 15h10v6H7z"/>',
 "link":'<path d="M10 14a4 4 0 006 0l3-3a4 4 0 10-6-6l-1 1"/><path d="M14 10a4 4 0 00-6 0l-3 3a4 4 0 106 6l1-1"/>',
 "trash":'<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
 "arrow-up":'<path d="M12 19V5M6 11l6-6 6 6"/>',
 "arrow-down":'<path d="M12 5v14M6 13l6 6 6-6"/>',
}
def ic(name, cls="ic", style=""):
    p = _IC.get(name, _IC["info"])
    st = f' style="{style}"' if style else ""
    return (f'<svg class="{cls}"{st} viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            f'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{p}</svg>')

# ─────────────────────────────────── MARCA ───────────────────────────────────
def wordmark(size=19, sub="ALIANÇAS", color="#fff"):
    return (f'<span style="display:grid;line-height:1">'
            f'<strong style="font-family:var(--font-display);font-size:{size}px;letter-spacing:.30em;'
            f'color:{color};font-weight:400">VELARO</strong>'
            f'<small style="font-size:{max(7,int(size*0.42))}px;letter-spacing:.34em;'
            f'color:var(--color-gold-400);margin-top:4px">{sub}</small></span>')

def logo(size=30):
    return (f'<svg width="{size}" height="{size}" viewBox="0 0 40 40" fill="none">'
            f'<path d="M6 9h28l-14 22z" stroke="var(--color-gold-300)" stroke-width="1.6" stroke-linejoin="round"/>'
            f'<path d="M13 9l7 11 7-11M6 9l14 8 14-8" stroke="var(--color-gold-400)" stroke-width="1.1"/></svg>')

def page(title, body, extra_css="", body_class=""):
    bc = f' class="{body_class}"' if body_class else ""
    ex = f"\n<style>\n{extra_css}\n</style>" if extra_css else ""
    return f'''<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{e(title)}</title>
{FONTS}
{CSS}{ex}
</head>
<body{bc}>
{body}
{MOCKNAV}</body>
</html>'''

# ═══════════════════════════════ SHELL · SITE PÚBLICO ═══════════════════════════════
SITE_NAV = [("Início","01-site-publico.html"),("Sobre nós","10-site-sobre.html"),
            ("Catálogo","11-site-catalogo.html"),("Seja um revendedor","12-site-cadastro.html"),
            ("Fale conosco","11-site-catalogo.html#contato")]

def _sitenav_mobile(links):
    """Menu do site no celular. .site-nav__links some abaixo de 1100px e nada
    tomava o lugar dela: 9 das 10 telas do site ficavam sem navegacao nenhuma no
    telefone. Mesmo <details> do painel — sem JS."""
    return ('<details class="site-nav__mobile">'
            '<summary aria-label="Abrir navegação"></summary>'
            f'<nav class="site-nav__mobile__panel">{links}</nav>'
            '</details>')

def site_shell(active, hero, body, autenticado=None, foot_pillars=None):
    def _lnk(t, h):
        cls = ' class="is-active"' if t == active else ''
        return f'<a href="{h}"{cls}>{e(t)}</a>'
    links = "".join(_lnk(t, h) for t, h in SITE_NAV)
    conta = (f'<span class="site-nav__enter">{ic("user")} {e(autenticado)} ⌄</span>'
             if autenticado else
             f'<a class="site-nav__enter" href="20-login.html">{ic("user")} Entrar</a>')
    pil = foot_pillars or [
      ("factory","Compra direto da fábrica","Alianças de alta qualidade com preço de fábrica."),
      ("ring","Produção sob demanda","Peças personalizadas com agilidade e precisão."),
      ("diamond","Suporte especializado","Time preparado para atender sua empresa com excelência."),
      ("truck","Entrega para todo o Brasil","Logística ágil e segura para todo o país."),
    ]
    pilars = "".join(
      f'<div class="pillar">{ic(i, style="width:32px;height:32px;color:var(--color-gold-400)")}'
      f'<div><h3>{e(t)}</h3><p>{e(d)}</p></div></div>' for i, t, d in pil)
    return f'''
<header class="site-nav">
  <a class="row" href="01-site-publico.html" style="gap:12px">{logo(34)}{wordmark(23)}</a>
  <nav class="site-nav__links">{links}</nav>
  {_sitenav_mobile(links)}
  <div class="site-nav__account">
    {conta}
    <a class="btn btn--gold" href="12-site-cadastro.html">{ic("user")}
      <span class="cta-stack"><strong>Solicitar atendimento</strong><small>Exclusivo para lojistas</small></span></a>
  </div>
</header>
{hero}
{body}
<section class="pillars"><div class="pillars__inner">{pilars}</div></section>
<footer class="site-foot">
  <div class="site-foot__inner">
    <div>
      <div class="row" style="gap:12px;margin-bottom:12px">{logo(30)}{wordmark(20)}</div>
      <p>Excelência em alianças para o seu negócio.</p>
    </div>
    <div><h4>Links rápidos</h4>
      <a href="01-site-publico.html">Início</a><br><a href="10-site-sobre.html">Sobre nós</a><br>
      <a href="11-site-catalogo.html">Catálogo</a><br><a href="12-site-cadastro.html">Seja um revendedor</a><br>
      <a href="20-login.html">Entrar</a></div>
    <div><h4>Atendimento</h4>
      <p>+55 (16) 99487-7800<br>vendas@velaro.com.br<br>Segunda a sexta, das 8h às 18h.</p></div>
    <div><h4>Formas de pagamento B2B</h4><p>Pix · Boleto · Transferência</p>
      <p class="muted" style="font-size:var(--text-xs);line-height:18px;margin-top:8px;color:rgba(255,255,255,.42)">
        Cobrança exclusiva Velaro → lojista. A plataforma não processa pagamento do consumidor final.</p></div>
  </div>
  <div class="site-foot__bar">
    <span>© 2026 Velaro Alianças. Todos os direitos reservados.</span>
    <span><a href="#">Política de Privacidade</a> &nbsp;|&nbsp; <a href="#">Termos de Uso</a></span>
  </div>
</footer>'''

def site_hero(titulo, eyebrow=None, sub=None, texto=None, ctas="", extra="", art=""):
    eb = f'<span class="badge-b2b">{ic("users")} {e(eyebrow)}</span>' if eyebrow else ""
    sb = f'<p class="hero-sub">{e(sub)}</p>' if sub else ""
    tx = f'<p class="lede">{e(texto)}</p>' if texto else ""
    ar = f'<div class="hero__art">{art}</div>' if art else ""
    grid = "hero__inner" + ("" if art else " hero__inner--single")
    return f'''<section class="hero"><div class="{grid}">
  <div>{eb}<h1>{titulo}</h1>{sb}{tx}{ctas}{extra}</div>{ar}
</div></section>'''

# ═══════════════════════════════ SHELL · PORTAL / MASTER ═══════════════════════════════
PORTAL_NAV = [
 ("home","Dashboard","02-portal-lojista.html"),
 ("book","Catálogo Revendedor","30-portal-catalogo.html"),
 ("users","Clientes","31-portal-clientes.html"),
 ("coin","Financeiro","32-portal-financeiro.html"),
 ("bag","Pedidos","33-portal-pedidos.html"),
 ("store","Personalização da loja","34-portal-loja.html"),
 ("tag","Preços e margens","35-portal-precos.html"),
 ("support","Suporte","36-portal-suporte.html"),
 ("cart","Vitrine para clientes","37-portal-vitrine.html"),
 ("truck","Pedido pronto para retirada","38-portal-retirada.html"),
]
MASTER_NAV = [
 ("home","Dashboard","04-painel-master.html"),
 ("users","Clientes","50-master-clientes.html"),
 ("gear","Configurações","51-master-config.html"),
 ("box","Estoque","52-master-estoque.html"),
 ("coin","Financeiro","53-master-financeiro.html"),
 ("bag","Pedidos","54-master-pedidos.html"),
 ("tag","Produtos","55-master-produtos.html"),
 ("promo","Promoções","56-master-promocoes.html"),
 ("chart","Relatórios","57-master-relatorios.html"),
 ("store","Revendedores","58-master-revendedores.html"),
 ("user-plus","Solicitações pré-cadastro","59-master-precadastro.html"),
 ("support","Suporte","60-master-suporte.html"),
]

def _busca(placeholder):
    """Busca do topo. No desktop e a barra de sempre; no celular vira so o icone,
    que abre o campo ao toque. E um <details>, o mesmo recurso do hamburger:
    funciona sem JS, que o prototipo nao tem."""
    return ('<details class="topbar__search">'
            f'<summary>{ic("search")}'
            f'<span class="topbar__search__ph">{e(placeholder)}</span>'
            '<span class="kbd">Ctrl K</span></summary>'
            '<div class="topbar__search__panel">'
            f'<span class="input-fake">{e(placeholder)}</span>'
            '</div></details>')

def _mobilenav(items, active):
    """Hamburger do topo. As 4 telas originais (01-04) tinham este bloco escrito a
    mao; portal_shell e master_shell nao, e por isso as outras 31 telas ficavam
    SEM navegacao nenhuma no celular — a .sidebar some abaixo de 1100px e nada
    tomava o lugar dela."""
    return ('<details class="mobile-navigation">'
            '<summary aria-label="Abrir navegação"></summary>'
            '<div class="mobile-navigation__panel">'
            f'<nav class="nav" aria-label="Navegação principal">{_nav(items, active)}</nav>'
            '</div></details>')

def _nav(items, active):
    return "".join(
      f'<a href="{h}" class="{"is-active" if h==active else ""}">{ic(i)} {e(t)}</a>'
      for i, t, h in items)

def portal_shell(active, body, titulo="Portal do Lojista"):
    return f'''<div class="shell">
  <aside class="sidebar">
    <div class="sidebar__brand">{logo(30)}{wordmark(19)}</div>
    <nav class="nav">{_nav(PORTAL_NAV, active)}</nav>
    <div class="brandbox">
      <div class="brandbox__logo">
        <span class="brandbox__mark">T</span>
        <span style="display:grid;line-height:1.1">
          <strong style="font-family:var(--font-display);font-size:14px;letter-spacing:.22em;color:var(--color-gold-200);font-weight:500">TOMAZELLI</strong>
          <small style="font-size:8px;letter-spacing:.28em;color:rgba(255,255,255,.42)">ALIANÇAS</small></span>
      </div>
      <dl style="margin:0"><dt>Cód. revendedor</dt><dd class="num">00876</dd>
        <dt>Plano</dt><dd>Parceiro Premium ◆</dd></dl>
    </div>
    <div class="helpbox">{ic("support", style="color:var(--color-gold-300)")}
      <div><strong>Precisa de ajuda?</strong><p>Fale com nosso time sempre que precisar.</p></div></div>
  </aside>
  <div>
    <header class="topbar">
      {_mobilenav(PORTAL_NAV, active)}
      <span class="eyebrow topbar__identity" style="color:var(--color-gold-300)">{e(titulo)}</span>
      {_busca("Buscar pedido, cliente ou produto…")}
      <div class="row push topbar__actions">
        <a class="btn btn--gold btn--sm" href="36-portal-suporte.html">Solicitar atendimento</a>
        <span class="avatar" style="background:var(--color-gold-500);color:#06110f">TA</span>
        <span style="display:grid;line-height:1.2">
          <strong style="font-size:var(--text-sm);color:#fff">Tomazelli Alianças</strong>
          <small style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">Parceiro Premium</small></span>
      </div>
    </header>
    <main class="main">{body}</main>
  </div>
</div>'''

def master_shell(active, body):
    return f'''<div class="shell">
  <aside class="sidebar">
    <div class="sidebar__brand">{logo(30)}{wordmark(19)}</div>
    <nav class="nav">{_nav(MASTER_NAV, active)}</nav>
    <div class="plan">
      <div class="plan__mark">{logo(26)}
        <strong style="font-family:var(--font-display);font-size:15px;letter-spacing:.30em;color:#fff;font-weight:400">VELARO</strong>
        <small style="font-size:8px;letter-spacing:.30em;color:var(--color-gold-400)">ADMINISTRAÇÃO</small></div>
      <p style="margin:0;font-size:var(--text-sm);color:rgba(255,255,255,.72)">Plano: <strong style="color:#fff">Master</strong> ◆</p>
      <a class="btn btn--ghost-gold btn--sm" style="margin-top:10px;width:100%" href="51-master-config.html">Ver meu plano</a>
    </div>
  </aside>
  <div>
    <header class="topbar">
      {_mobilenav(MASTER_NAV, active)}
      <span class="row topbar__identity" style="gap:10px">{ic("shield", style="color:var(--color-gold-400)")}
        <span style="display:grid;line-height:1.25">
          <strong style="font-size:var(--text-sm);color:#fff">Painel Interno</strong>
          <small style="font-size:11px;color:rgba(255,255,255,.5)">Gestão de Revendedores</small></span></span>
      {_busca("Buscar revendedor, pedido, cliente…")}
      <div class="row push topbar__actions" style="gap:var(--space-4)">
        <a class="storeswitch" href="03-vitrine-pdv.html"><small>Acessar loja</small><strong>Tomazelli Alianças ↗</strong></a>
        <a class="impersonate" href="02-portal-lojista.html" title="Ação auditada">{ic("store")}
          <span><strong>Painel Revendedor</strong><small>Ver como revendedor</small></span></a>
        <span class="bell">{ic("bell", style="color:inherit")}<b>3</b></span>
        <span class="avatar">VA</span>
        <span style="display:grid;line-height:1.2">
          <strong style="font-size:var(--text-sm);color:#fff">Velaro Alianças</strong>
          <small style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">Admin</small></span>
      </div>
    </header>
    <main class="main">{body}</main>
  </div>
</div>'''

# ═══════════════════════════════ BLOCOS REUTILIZÁVEIS ═══════════════════════════════
def head(titulo, sub=None, acoes=""):
    s = f'<p class="lede">{e(sub)}</p>' if sub else ""
    a = f'<div class="row row--wrap">{acoes}</div>' if acoes else ""
    return f'<div class="page-head"><div><h1 class="display-md">{e(titulo)}</h1>{s}</div>{a}</div>'

def kpis(items, cls="g4"):
    """items: (icone, rotulo, valor, extra_html|None, tom)"""
    out = []
    for it in items:
        i, lab, val, extra, tom = (list(it) + [None, None])[:5]
        tom = tom or "gold"
        ex = f'<div class="kpi__delta {extra[0]}">{extra[1]}</div>' if isinstance(extra, tuple) else (
             f'<div class="kpi__delta">{extra}</div>' if extra else "")
        out.append(f'''<div class="card card--compact">
  <div class="kpi"><span class="kpi__icon kpi__icon--{tom}">{ic(i)}</span>
    <div><div class="kpi__label">{e(lab)}</div><div class="kpi__value">{e(val)}</div>{ex}</div></div>
</div>''')
    return f'<section class="grid {cls}">{"".join(out)}</section>'

def up(t):   return ("kpi__delta--up", f"↑ {e(t)}")
def down(t): return ("kpi__delta--down", f"↓ {e(t)}")
def flat(t): return ("kpi__delta--flat", e(t))

def filtros(busca, selects=(), acoes=""):
    sel = "".join(
      f'<label class="fbox"><span>{e(l)}</span><span class="select-fake">{e(v)}</span></label>'
      for l, v in selects)
    ac = f'<div class="row row--wrap push">{acoes}</div>' if acoes else ""
    return f'''<section class="filters">
  <span class="fsearch">{ic("search")} {e(busca)}</span>{sel}{ac}
</section>'''

def tabela(cols, rows, foot=None, check=False):
    """cols: (titulo, classe) · rows: lista de listas de HTML"""
    ck = '<th class="tcheck"><span class="cbox"></span></th>' if check else ""
    th = "".join(f'<th class="{c}">{e(t)}</th>' for t, c in cols)
    tr = ""
    for r in rows:
        cells = "".join(f'<td class="{cols[i][1]}">{c}</td>' for i, c in enumerate(r))
        c0 = '<td class="tcheck"><span class="cbox"></span></td>' if check else ""
        tr += f"<tr>{c0}{cells}</tr>"
    ft = f'<div class="tfoot">{foot}</div>' if foot else ""
    return f'''<div class="table-scroll"><table class="table">
  <thead><tr>{ck}{th}</tr></thead><tbody>{tr}</tbody></table></div>{ft}'''

def pag(texto, paginas="1 2 3 … 12", extra=""):
    nums = "".join(
      f'<span class="pnum{" is-on" if i==0 else ""}">{e(n)}</span>'
      for i, n in enumerate(paginas.split()))
    return (f'<div class="pagination"><span class="muted">{e(texto)}</span>'
            f'<span class="pnums"><span class="pnum">‹</span>{nums}<span class="pnum">›</span></span>{extra}</div>')

def card(titulo, body, acao="", cls="", head_extra=""):
    h = ""
    if titulo:
        h = (f'<div class="card__head"><h2 class="title">{e(titulo)}</h2>'
             f'{head_extra}{acao}</div>')
    return f'<div class="card {cls}">{h}{body}</div>'

def chip(texto, tom="neutral", flat=False):
    f = " chip--flat" if flat else ""
    return f'<span class="chip chip--{tom}{f}">{e(texto)}</span>'

def btn(texto, tom="secondary", icone=None, href="#", sm=True):
    i = ic(icone) + " " if icone else ""
    s = " btn--sm" if sm else ""
    return f'<a class="btn btn--{tom}{s}" href="{href}">{i}{e(texto)}</a>'

def campo(rotulo, valor="", obrig=False, tipo="input", hint=None, largura=None):
    req = '<i class="req">*</i>' if obrig else ""
    hn = f'<small class="fhint">{e(hint)}</small>' if hint else ""
    st = f' style="grid-column:span {largura}"' if largura else ""
    if tipo == "select":
        ctl = f'<span class="select-fake select-fake--full">{e(valor)}</span>'
    elif tipo == "textarea":
        ctl = f'<span class="input-fake input-fake--area">{e(valor)}</span>'
    elif tipo == "check":
        ctl = f'<span class="checkline"><span class="cbox is-on">✓</span>{e(valor)}</span>'
    else:
        ph = " is-ph" if not valor else ""
        ctl = f'<span class="input-fake{ph}">{e(valor)}</span>'
    lab = f'<label>{e(rotulo)}{req}</label>' if rotulo else ""
    return f'<div class="field"{st}>{lab}{ctl}{hn}</div>'

def form(campos, cols=3, titulo=None):
    t = f'<h3 class="fsec">{e(titulo)}</h3>' if titulo else ""
    return f'{t}<div class="fgrid fgrid--{cols}">{"".join(campos)}</div>'

def toggle(rotulo, hint=None, on=True):
    h = f'<small>{e(hint)}</small>' if hint else ""
    return (f'<div class="toggleline"><div><strong>{e(rotulo)}</strong>{h}</div>'
            f'<span class="switch{" is-on" if on else ""}"></span></div>')

def linha_dado(rotulo, valor, icone=None):
    i = ic(icone) + " " if icone else ""
    return f'<div class="datarow"><span class="datarow__k">{i}{e(rotulo)}</span><span class="datarow__v">{valor}</span></div>'

def drawer(titulo, body, sub=None, acoes="", chip_html=""):
    s = f'<p class="drawer__sub">{e(sub)}</p>' if sub else ""
    a = f'<div class="drawer__foot">{acoes}</div>' if acoes else ""
    return f'''<aside class="drawer">
  <header class="drawer__head"><div><h2 class="title">{e(titulo)}</h2>{s}</div>
    {chip_html}<span class="drawer__x">{ic("x")}</span></header>
  <div class="drawer__body">{body}</div>{a}
</aside>'''

def stepper(passos):
    """passos: (rotulo, estado, nota) — estado: done|now|todo|locked"""
    out = []
    for n, (lab, st, nota) in enumerate(passos, 1):
        mark = "✓" if st == "done" else ("🔒" if st == "locked" else str(n))
        out.append(f'''<li class="step step--{st}">
  <span class="step__dot">{mark}</span>
  <span class="step__lab">{e(lab)}</span><span class="step__note">{e(nota)}</span></li>''')
    return f'<ol class="stepper">{"".join(out)}</ol>'

def timeline(eventos, cls=""):
    """eventos: (estado, titulo, descricao|None, quando)"""
    out = []
    for st, t, d, q in eventos:
        dd = f'<span class="tl__desc">{e(d)}</span>' if d else ""
        out.append(f'''<li class="tl tl--{st}"><span class="tl__dot"></span>
  <span class="tl__body"><strong>{e(t)}</strong>{dd}</span><span class="tl__when">{e(q)}</span></li>''')
    return f'<ul class="timeline {cls}">{"".join(out)}</ul>'

def tabs(items, active):
    return '<div class="tabs">' + "".join(
      f'<span class="tab{" is-on" if t==active else ""}">{e(t)}</span>' for t in items) + '</div>'

def notice(html_txt, tom="gold"):
    return f'<p class="notice notice--{tom}">{ic("info")}<span>{html_txt}</span></p>'

def checklist(itens):
    return '<ul class="cklist">' + "".join(
      f'<li class="ck--{st}">{ic("check") if st=="ok" else ic("clock")}<span>{e(t)}</span>'
      f'{f"<b>{e(v)}</b>" if v else ""}</li>' for st, t, v in itens) + '</ul>'

def ringimg(variant, uid, cls="thumb"):
    return f'<span class="{cls}">{rings.thumb(variant, uid)}</span>'

def prodcard(variant, uid, sku, nome, specs, preco, extra="", chip_html="", acoes=""):
    return f'''<div class="prod">
  <div class="prod__img">{rings.svg(variant, uid)}</div>
  <small class="prod__sku">{e(sku)}</small>
  <h4>{e(nome)}</h4><small class="prod__spec">{specs}</small>
  <span class="prod__price">{e(preco)}</span>{chip_html}{extra}
  <div class="prod__acts">{acoes}</div>
</div>'''


# ═══════════════════════════ RELIGAÇÃO DOS BOTÕES (pós-processamento) ═══════════════════════════
#
# Todo botão nasce com href="#" (o default de btn()). Este passo, rodado por W()
# ao gravar cada tela, decide o destino de cada um:
#
#   1. se o rótulo estiver em DESTINOS (por tela) ou DESTINOS_GLOBAIS, aponta para a TELA;
#   2. senão, aponta para mapa.html#<slug-da-tela> — que é a decisão consciente para
#      botão de AÇÃO (Salvar, Exportar, Aprovar): num protótipo estático ele não navega,
#      e o mapa explica em que tela aquilo vive.
#
# Sem este passo o HTML gerado sai com href="#" e o protótipo fica mudo. Ele existia
# antes só no HTML versionado, não no gerador: regenerar a pasta apagava os links.

DESTINOS_GLOBAIS = {
    "Política de Privacidade": "17-site-privacidade.html",
    "Termos de Uso":           "18-site-termos.html",
    "Esqueci minha senha":     "21-login-senha.html",
}

DESTINOS = {
  "11-site-catalogo.html": {
    "Ver detalhes": "16-site-produto.html",
  },
  "03-vitrine-pdv.html": {
    "Ver detalhes": "07-vitrine-produto.html",
    "Pagamento realizado no caixa da loja": "08-vitrine-pedido-confirmado.html",
    "Finalizar pedido": "08-vitrine-pedido-confirmado.html",
  },
  "14-site-status.html": {
    "Falar com nossa equipe": "11-site-catalogo.html#contato",
  },
  "32-portal-financeiro.html": {
    "Ver todas as notas fiscais emitidas": "40-portal-notas.html",
    "Realizar pagamento à Velaro":         "41-portal-pagamento.html",
  },
  "33-portal-pedidos.html": {
    "Ver detalhes": "39-portal-pedido.html",
  },
  "35-portal-precos.html": {
    "Saiba mais sobre precificação": "43-portal-ajuda.html",
  },
  "36-portal-suporte.html": {
    "Abrir chamado":            "42-portal-chamado.html",
    "Perguntas frequentes":     "43-portal-ajuda.html",
    "Guias e manuais":          "43-portal-ajuda.html",
    "Vídeos tutoriais":         "43-portal-ajuda.html",
    "Acessar central de ajuda": "43-portal-ajuda.html",
  },
  "51-master-config.html": {
    "Perfil da empresa":        "51h-master-config-empresa.html",
    "Usuários e permissões":    "51a-master-config-usuarios.html",
    "Notificações":             "51b-master-config-notificacoes.html",
    "Integrações":              "51c-master-config-integracoes.html",
    "Segurança":                "51d-master-config-seguranca.html",
    "Financeiro":               "51e-master-config-financeiro.html",
    "Personalização":           "51f-master-config-personalizacao.html",
    "Backup e dados":           "51g-master-config-backup.html",
    "Configurar notas fiscais": "51e-master-config-financeiro.html",
    "Gerenciar formas de pagamento": "51e-master-config-financeiro.html",
  },
  "52-master-estoque.html": {
    "+ Nova movimentação": "52a-master-estoque-movimentacao.html",
    "Ajustar estoque":     "52a-master-estoque-movimentacao.html",
    "Registrar entrada":   "52a-master-estoque-movimentacao.html",
    "Solicitar produção":  "52a-master-estoque-movimentacao.html",
    "Gerar pedido":        "52a-master-estoque-movimentacao.html",
    "Ver reservas":        "52b-master-estoque-historico.html",
    "Ver todas":           "52b-master-estoque-historico.html",
  },
  "53-master-financeiro.html": {
    "+ Novo recebimento": "53a-master-financeiro-recebimento.html",
    "Ver nota fiscal":    "53b-master-financeiro-nota.html",
  },
  "54-master-pedidos.html": { "+ Novo pedido":  "61-master-pedido-novo.html" },
  "55-master-produtos.html": { "+ Novo produto": "62-master-produto-novo.html" },
  "56-master-promocoes.html": {
    "+ Nova promoção":         "63-master-promocao-nova.html",
    "Relatório de desempenho": "64-master-promocao-desempenho.html",
  },
  "57-master-relatorios.html": {
    "Agendar relatórios":               "68-master-relatorios-agendados.html",
    "Gerenciar agendamentos":           "68-master-relatorios-agendados.html",
    "Ver ranking completo":             "66-master-relatorio-revendedores.html",
    "Ver todos os produtos":            "67-master-relatorio-produtos.html",
    "Ver todos os relatórios":          "69-master-relatorios-biblioteca.html",
    "Vendas por período":               "65-master-relatorio-vendas.html",
    "Top produtos":                     "67-master-relatorio-produtos.html",
    "Produtos mais vendidos":           "67-master-relatorio-produtos.html",
    "Ranking de revendedores":          "66-master-relatorio-revendedores.html",
    "Ver relatório financeiro completo":"53-master-financeiro.html",
    "Pedidos por status":               "54-master-pedidos.html",
    "Estoque atual":                    "52-master-estoque.html",
    "Financeiro":                       "53-master-financeiro.html",
  },
  "60-master-suporte.html": {
    "Voltar para todas as solicitações": "70-master-suporte-lista.html",
    "← Voltar para todas as solicitações": "70-master-suporte-lista.html",
  },
}

_A_STUB = re.compile(r'<a\b([^>]*?)href="#"([^>]*?)>(.*?)</a>', re.S)

def _rotulo(interno):
    """Texto visível de um <a>: tira as tags (o ícone SVG entra como espaço)."""
    return " ".join(re.sub(r"<[^>]+>", " ", interno).split())

# Tela nova -> a tela CONTRATADA de que ela e o detalhe. O mapa documenta so as
# 31 do escopo, entao a ancora de acao das internas aponta para o verbete do pai.
# Sem isto, 29 telas mandavam o leitor para uma ancora que nao existe.
PAI_NO_MAPA = {
  "vitrine-produto": "portal-vitrine",          "vitrine-pedido-confirmado": "portal-carrinho",
  "site-produto": "site-catalogo",           "site-privacidade": "site-home",
  "site-termos": "site-home",             "login-senha": "login",
  "portal-pedido": "portal-pedidos",         "portal-notas": "portal-financeiro",
  "portal-pagamento": "portal-financeiro",   "portal-chamado": "portal-suporte",
  "portal-ajuda": "portal-suporte",
  "master-estoque-movimentacao": "master-estoque",
  "master-estoque-historico": "master-estoque",
  "master-financeiro-recebimento": "master-financeiro",
  "master-financeiro-nota": "master-financeiro",
  "master-pedido-novo": "master-pedidos",    "master-produto-novo": "master-produtos",
  "master-promocao-nova": "master-promocoes","master-promocao-desempenho": "master-promocoes",
  "master-relatorio-vendas": "master-relatorios",
  "master-relatorio-revendedores": "master-relatorios",
  "master-relatorio-produtos": "master-relatorios",
  "master-relatorios-agendados": "master-relatorios",
  "master-relatorios-biblioteca": "master-relatorios",
  "master-suporte-lista": "master-suporte",
}

def slug_da_tela(arquivo):
    """'33-portal-pedidos.html' -> 'portal-pedidos' (a âncora no mapa)."""
    return re.sub(r"^\d+[a-z]?-", "", arquivo).replace(".html", "")

def religar(html, arquivo):
    tabela = DESTINOS.get(arquivo, {})
    bruto = slug_da_tela(arquivo)
    # As subtelas de Configuracoes (51a..51h) caem todas no verbete master-config.
    pai = PAI_NO_MAPA.get(bruto) or ("master-config" if bruto.startswith("master-config-") else bruto)
    ancora = "mapa.html#" + pai

    def destino(rot):
        for fonte in (tabela, DESTINOS_GLOBAIS):
            if rot in fonte:
                return fonte[rot]
            # Os cards levam título e subtítulo no mesmo <a> ("Segurança Senha, 2FA
            # e sessões ativas"), e alguns rótulos terminam com seta. Casa por prefixo.
            for chave, alvo in fonte.items():
                if rot.startswith(chave):
                    return alvo
        return ancora

    def troca(m):
        return f'<a{m.group(1)}href="{destino(_rotulo(m.group(3)))}"{m.group(2)}>{m.group(3)}</a>'

    return _A_STUB.sub(troca, html)
