{{--
[Modulo: resources/views/portal/suporte/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Card de canais de atendimento — os mesmos valores publicos do rodape do site, lidos de settings.
--}}
<div class="card">
  <div class="card__head"><h2 class="title">Canais de atendimento</h2></div>

  <div class="datarow">
    <span class="datarow__k"><x-velaro.icon name="support" />
      <span><strong style="display:block;color:var(--ink)">Chat online</strong>
        <small>Disponível na plataforma</small></span></span>
    <span class="datarow__v"><span class="chip chip--ok chip--flat">Online</span></span>
  </div>

  <div class="datarow">
    <span class="datarow__k"><x-velaro.icon name="whats" />
      <span><strong style="display:block;color:var(--ink)">WhatsApp</strong>
        <small>{{ $canais['whatsapp'] }}</small></span></span>
    <span class="datarow__v"><span class="chip chip--ok chip--flat">Online</span></span>
  </div>

  <div class="datarow">
    <span class="datarow__k"><x-velaro.icon name="mail" />
      <span><strong style="display:block;color:var(--ink)">E-mail</strong>
        <small>{{ $canais['email'] }}</small></span></span>
    <span class="datarow__v"><span class="chip chip--neutral chip--flat">24h</span></span>
  </div>

  <div class="datarow">
    <span class="datarow__k"><x-velaro.icon name="phone" />
      <span><strong style="display:block;color:var(--ink)">Telefone</strong>
        <small>{{ $canais['telefone'] }}</small></span></span>
    <span class="datarow__v"><span class="chip chip--neutral chip--flat">08h às 18h</span></span>
  </div>

  <small class="fhint">{{ $canais['horario'] }} (horário de Brasília) · exceto feriados</small>
</div>
