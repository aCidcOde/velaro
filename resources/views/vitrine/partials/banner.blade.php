{{--
[Modulo: resources/views/vitrine/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Banner do topo da loja, pintado so pelas cores gravadas em reseller_stores.
--}}
{{-- `.shop__banner` é do protótipo e ainda não entrou no SHOP_CSS do design
     system, então a pintura vem inline — e sai toda das variáveis --shop-*, que
     são as cores gravadas em reseller_stores. --}}
<div class="shop__banner"
     style="margin:var(--space-5) var(--space-6) 0;border-radius:var(--radius-md);overflow:hidden;
            padding:var(--space-8) var(--space-6);color:#fff;
            @if($banner['imagem'])
              background:linear-gradient(105deg, color-mix(in srgb, var(--shop-primary) 88%, transparent) 0%, color-mix(in srgb, var(--shop-primary) 45%, transparent) 100%), url('{{ $banner['imagem'] }}') center/cover no-repeat;
            @else
              background:linear-gradient(105deg, color-mix(in srgb, var(--shop-primary) 45%, #000) 0%, var(--shop-primary) 78%);
            @endif">
  <h2 style="font-family:var(--font-display);font-size:30px;line-height:36px;margin:0;font-weight:500">
    {{ $banner['titulo'] }}
  </h2>
  <p style="margin:8px 0 var(--space-4);color:rgba(255,255,255,.82);font-size:var(--text-sm)">
    {{ $banner['slogan'] }}
  </p>
  <a class="btn btn--sm" href="#produtos"
     style="background:var(--shop-secondary);color:#fff">Conheça nossa coleção</a>
</div>
