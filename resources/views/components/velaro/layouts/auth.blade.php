{{-- Login e recuperação de senha (tela 0 / 21): coluna escura + cartão. --}}
{{--
  A coluna escura fala com o LOJISTA — o usuário real desta tela. O protótipo aprovado
  (docs/mockups/20-login.html) trazia aqui o bloco ".routerbox" com o roteamento por perfil
  (/backend, /portal, /solicitacao/…): documentação de arquitetura interna, sem valor para quem
  só quer entrar. O produto evoluiu para "um login, um painel", então o bloco saiu e no lugar
  ficou a proposta de valor do Portal do Lojista, no mesmo vocabulário do site público (tela 1.1).
--}}
@props(['title' => null])
<x-velaro.layouts.base :title="$title">
<div class="loginwrap">
  <div class="loginaside">
    <a class="row" href="{{ route('site.home') }}" style="gap:12px"><x-velaro.logo :size="34" /><x-velaro.wordmark :size="24" /></a>
    <div>
      <h1 class="display-md" style="color:#fff">Um login.<br>Todo o seu negócio.</h1>
      <p class="lede" style="color:rgba(255,255,255,.7);margin-top:var(--space-4)">É aqui que você pede estoque, aplica os seus
        preços e acompanha cada pedido até a retirada — com as condições comerciais que a Velaro reserva para
        lojas e revendedores.</p>

      <span class="eyebrow" style="display:block;margin-top:var(--space-6);color:var(--color-gold-300)">No Portal do Lojista</span>
      <ul class="cklist cklist--dark" style="margin-top:var(--space-3)">
        <li><x-velaro.icon name="box" /><span>Pedir estoque no catálogo profissional, com custo de revenda</span></li>
        <li><x-velaro.icon name="tag" /><span>Aplicar os seus preços na sua vitrine</span></li>
        <li><x-velaro.icon name="bag" /><span>Comprar em lote e melhorar o custo por peça</span></li>
        <li><x-velaro.icon name="truck" /><span>Acompanhar o pedido do pagamento até a retirada</span></li>
      </ul>
    </div>
    <p class="muted" style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">O cliente final não possui login. Ele existe apenas como cliente vinculado à carteira do Parceiro Premium.</p>
  </div>
  <div class="loginmain">{{ $slot }}</div>
</div>
</x-velaro.layouts.base>
