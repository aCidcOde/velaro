{{-- Site público (site_shell). Slots: hero, slot (corpo), pillars (opcional). --}}
@props(['title' => null, 'hero' => null, 'pillars' => null, 'autenticado' => null])
<x-velaro.layouts.base :title="$title" body-class="site">
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
<header class="site-nav">
  <a class="row" href="{{ route('site.home') }}" style="gap:12px"><x-velaro.logo :size="34" /><x-velaro.wordmark :size="23" /></a>
  <nav class="site-nav__links">
    @foreach(config('velaro-nav.site') as [$i, $rotulo, $rota])
      <a href="{{ route($rota) }}" @class(['is-active' => request()->routeIs($rota) || request()->routeIs($rota.'.*')])>{{ $rotulo }}</a>
    @endforeach
    <a href="{{ route('site.catalogo') }}#contato">Fale conosco</a>
  </nav>
  <details class="site-nav__mobile"><summary aria-label="Abrir navegação"></summary>
    <nav class="site-nav__mobile__panel">
      @foreach(config('velaro-nav.site') as [$i, $rotulo, $rota])
        <a href="{{ route($rota) }}" @class(['is-active' => request()->routeIs($rota)])>{{ $rotulo }}</a>
      @endforeach
      <a href="{{ route('site.catalogo') }}#contato">Fale conosco</a>
    </nav>
  </details>
  <div class="site-nav__account">
    @auth
      <a class="site-nav__enter" href="{{ route('dashboard') }}"><x-velaro.icon name="user" /> {{ auth()->user()->name }}</a>
    @else
      <a class="site-nav__enter" href="{{ route('login') }}"><x-velaro.icon name="user" /> Entrar</a>
    @endauth
    <a class="btn btn--gold" href="{{ route('site.cadastro') }}"><x-velaro.icon name="user" />
      <span class="cta-stack"><strong>Solicitar atendimento</strong><small>Exclusivo para lojistas</small></span></a>
  </div>
</header>
{{ $hero ?? '' }}
<main id="conteudo">{{ $slot }}</main>
@php($pil = $pillars ?? null)
<section class="pillars"><div class="pillars__inner">
  @if($pil){{ $pil }}@else
  @foreach([['factory','Compra direto da fábrica','Alianças de alta qualidade com preço de fábrica.'],['ring','Produção sob demanda','Peças personalizadas com agilidade e precisão.'],['diamond','Suporte especializado','Time preparado para atender sua empresa com excelência.'],['truck','Entrega para todo o Brasil','Logística ágil e segura para todo o país.']] as [$i,$t,$d])
    <div class="pillar"><x-velaro.icon :name="$i" style="width:32px;height:32px;color:var(--color-gold-400)" /><div><h3>{{ $t }}</h3><p>{{ $d }}</p></div></div>
  @endforeach
  @endif
</div></section>
<footer class="site-foot">
  <div class="site-foot__inner">
    <div><div class="row" style="gap:12px;margin-bottom:12px"><x-velaro.logo :size="30" /><x-velaro.wordmark :size="20" /></div><p>Excelência em alianças para o seu negócio.</p></div>
    <div><h4>Links rápidos</h4>
      <a href="{{ route('site.home') }}">Início</a><br><a href="{{ route('site.sobre') }}">Sobre nós</a><br>
      <a href="{{ route('site.catalogo') }}">Catálogo</a><br><a href="{{ route('site.cadastro') }}">Seja um revendedor</a><br>
      <a href="{{ route('login') }}">Entrar</a></div>
    <div><h4>Atendimento</h4><p>+55 (16) 99487-7800<br>vendas@velaro.com.br<br>Segunda a sexta, das 8h às 18h.</p></div>
    <div><h4>Formas de pagamento B2B</h4><p>Pix · Boleto · Transferência</p>
      <p class="muted" style="font-size:var(--text-xs);line-height:18px;margin-top:8px;color:rgba(255,255,255,.42)">Cobrança exclusiva Velaro → lojista. A plataforma não processa pagamento do consumidor final.</p></div>
  </div>
  <div class="site-foot__bar">
    <span>© {{ date('Y') }} Velaro Alianças. Todos os direitos reservados.</span>
    <span><a href="{{ route('site.privacidade') }}">Política de Privacidade</a> &nbsp;|&nbsp; <a href="{{ route('site.termos') }}">Termos de Uso</a></span>
  </div>
</footer>
</x-velaro.layouts.base>
