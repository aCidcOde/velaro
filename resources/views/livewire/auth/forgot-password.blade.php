{{-- Tela 21 · pedido de recuperação de senha (mockup 21-login-senha.html).
     Os dois estados do mockup são excludentes aqui: enquanto o status de envio
     estiver na sessão a tela mostra a confirmação; nas demais visitas, o pedido.
     A ação, o campo e o CSRF continuam os do Fortify — muda só a casca. --}}
@php
    $validadeLink = (int) config('auth.passwords.users.expire', 60);
    $intervaloReenvio = (int) config('auth.passwords.users.throttle', 60);
@endphp
<x-velaro.layouts.auth :title="__('Recuperar senha')">

<div class="stack" style="width:100%;max-width:420px;gap:var(--space-5)">

  @if (session('status'))
    {{-- Estado 2 · link enviado. A resposta é a mesma exista ou não a conta. --}}
    <div class="card">
      <div class="row" style="gap:var(--space-4);align-items:flex-start">
        <span class="bigcheck"><x-velaro.icon name="mail" /></span>
        <div>
          <h2 class="title">{{ __('Link enviado') }}</h2>
          {{-- O status do Fortify é o gatilho deste estado; o texto exibido é o
               nosso, em pt-BR, porque a aplicação ainda roda com APP_LOCALE=en. --}}
          <p class="lede" style="margin-top:6px" role="status">O link de recuperação já está a caminho
            do e-mail informado.</p>
        </div>
      </div>

      <div style="margin-top:var(--space-5)">
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="clock" /> Validade do link</span>
          <span class="datarow__v">{{ $validadeLink }} minutos</span></div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="lock" /> Uso</span>
          <span class="datarow__v">Uma única vez</span></div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="mail" /> Canal</span>
          <span class="datarow__v">E-mail do responsável cadastrado</span></div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="refresh" /> Novo envio</span>
          <span class="datarow__v">Liberado em {{ $intervaloReenvio }} segundos</span></div>
      </div>

      <h3 class="fsec">Não recebeu?</h3>
      <ul class="cklist">
        <li class="ck--ok"><x-velaro.icon name="check" /><span>Confira a caixa de spam e a aba de promoções.</span></li>
        <li class="ck--ok"><x-velaro.icon name="check" /><span>Confirme se o e-mail é o mesmo do cadastro de lojista.</span></li>
        <li class="ck--wait"><x-velaro.icon name="clock" /><span>Cadastro reprovado ou inativo não recebe link — ele não autentica.</span></li>
      </ul>

      <div class="row row--wrap" style="margin-top:var(--space-5)">
        <a class="btn btn--secondary" href="{{ route('password.request') }}">
          <x-velaro.icon name="refresh" /> {{ __('Pedir outro link') }}</a>
        <a class="btn btn--primary" href="{{ route('login') }}">
          <x-velaro.icon name="user" /> {{ __('Voltar para o login') }}</a>
      </div>
    </div>
  @else

  {{-- Estado 1 · pedido de recuperação. --}}
  <div class="card">

    <a class="link-gold" href="{{ route('site.home') }}"
      style="display:inline-flex;align-items:center;gap:6px;margin-bottom:var(--space-4)">
      <x-velaro.icon name="arrow-up" style="width:15px;height:15px;transform:rotate(-90deg)" />
      {{ __('Voltar para o site') }}</a>

    <h2 class="title">{{ __('Recuperar senha') }}</h2>
    <p class="lede" style="margin:6px 0 var(--space-5)">Informe o e-mail cadastrado e enviaremos um link
      para você criar uma senha nova.</p>

    <form method="POST" action="{{ route('password.email') }}">
      @csrf

      <div class="fgrid fgrid--1">
        <div class="field" @error('email') data-state="error" @enderror>
          <label for="forgot-email">{{ __('E-mail') }}<i class="req">*</i></label>
          <input class="input" type="email" id="forgot-email" name="email" value="{{ old('email') }}"
            placeholder="seuemail@exemplo.com.br" maxlength="255" autocomplete="email" required autofocus>
          @error('email')<small class="field__message">{{ $message }}</small>@enderror
        </div>
      </div>

      <button class="btn btn--primary" type="submit" data-test="email-password-reset-link-button"
        style="width:100%;margin-top:var(--space-5)">
        <x-velaro.icon name="mail" /> {{ __('Enviar link de redefinição') }}</button>
    </form>

    <p class="muted" style="text-align:center;margin:var(--space-4) 0 0;font-size:var(--text-sm)">
      Lembrou a senha?
      <a class="link-gold" href="{{ route('login') }}">← {{ __('Voltar para o login') }}</a></p>

    <p class="notice notice--info" style="margin-top:var(--space-5)">
      <x-velaro.icon name="shield" /><span>O link vale por {{ $validadeLink }} minutos, só pode ser usado uma vez
        e sai para o e-mail do responsável cadastrado. O pedido e a troca de senha entram
        em <code>audit_logs</code> (Anexo I §7).</span></p>

  </div>
  @endif

</div>

</x-velaro.layouts.auth>
