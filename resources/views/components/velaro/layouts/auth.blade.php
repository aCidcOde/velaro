{{-- Login e recuperação de senha (tela 0 / 21): coluna escura + cartão. --}}
@props(['title' => null])
<x-velaro.layouts.base :title="$title">
<div class="loginwrap">
  <div class="loginaside">
    <a class="row" href="{{ route('site.home') }}" style="gap:12px"><x-velaro.logo :size="34" /><x-velaro.wordmark :size="24" /></a>
    <div>
      <h1 class="display-md" style="color:#fff">Um login.<br>O ambiente certo.</h1>
      <p class="lede" style="color:rgba(255,255,255,.7);margin-top:var(--space-4)">O mesmo ponto de entrada identifica o perfil autorizado e direciona o usuário ao ambiente correspondente.</p>
    </div>
    <div class="routerbox">
      <span class="eyebrow" style="color:var(--color-gold-300)">Roteamento por perfil</span>
      <div class="stack" style="margin-top:var(--space-3)">
        <div class="routerow"><span class="chip chip--brand">Perfil Master</span><x-velaro.icon name="arrow-up" style="transform:rotate(90deg)" /><code>/backend</code><small>Equipe Velaro</small></div>
        <div class="routerow"><span class="chip chip--ok">Parceiro Premium</span><x-velaro.icon name="arrow-up" style="transform:rotate(90deg)" /><code>/portal</code><small>Revendedor aprovado</small></div>
        <div class="routerow"><span class="chip chip--warn">Pré-cadastro</span><x-velaro.icon name="arrow-up" style="transform:rotate(90deg)" /><code>/solicitacao/…</code><small>Acompanha a própria solicitação</small></div>
        <div class="routerow"><span class="chip chip--danger">Reprovado / inativo</span><x-velaro.icon name="arrow-up" style="transform:rotate(90deg)" /><code>—</code><small>Não autentica</small></div>
      </div>
    </div>
    <p class="muted" style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">O cliente final não possui login. Ele existe apenas como cliente vinculado à carteira do Parceiro Premium.</p>
  </div>
  <div class="loginmain">{{ $slot }}</div>
</div>
</x-velaro.layouts.base>
