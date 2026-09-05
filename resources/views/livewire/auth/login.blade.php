{{-- Tela 0 · login único (mockup 20-login.html). O roteamento por perfil é
     explicado pela coluna escura do layout — aqui fica só o cartão do formulário.
     A casca é Velaro; a ação, os nomes de campo e o CSRF continuam os do Fortify. --}}
<x-velaro.layouts.auth :title="__('Entrar')">

<div class="card" style="width:100%;max-width:420px">

  <a class="link-gold" href="{{ route('site.home') }}"
    style="display:inline-flex;align-items:center;gap:6px;margin-bottom:var(--space-4)">
    <x-velaro.icon name="arrow-up" style="width:15px;height:15px;transform:rotate(-90deg)" />
    {{ __('Voltar para o site') }}</a>

  <h2 class="title">{{ __('Entrar') }}</h2>
  <p class="lede" style="margin:6px 0 var(--space-5)">Acesse com o e-mail e a senha do seu cadastro.</p>

  {{-- Mensagem de sessão: senha redefinida, link enviado, sessão encerrada. --}}
  @if (session('status'))
    <p class="notice notice--ok" style="margin-bottom:var(--space-5)" role="status">
      <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <form method="POST" action="{{ route('login.store') }}">
    @csrf

    <div class="fgrid fgrid--1">

      <div class="field" @error('email') data-state="error" @enderror>
        <label for="login-email">{{ __('E-mail') }}<i class="req">*</i></label>
        <input class="input" type="email" id="login-email" name="email" value="{{ old('email') }}"
          placeholder="seuemail@exemplo.com.br" maxlength="255" autocomplete="email" required autofocus>
        @error('email')<small class="field__message">{{ $message }}</small>@enderror
      </div>

      <div class="field" @error('password') data-state="error" @enderror>
        <label for="login-password">{{ __('Senha') }}<i class="req">*</i></label>
        <input class="input" type="password" id="login-password" name="password"
          placeholder="{{ __('Sua senha') }}" autocomplete="current-password" required>
        @error('password')<small class="field__message">{{ $message }}</small>@enderror
      </div>

    </div>

    <div class="spread" style="margin-top:var(--space-4)">
      <label class="checkline" for="login-remember">
        <input type="checkbox" id="login-remember" name="remember" value="1" @checked(old('remember'))
          style="width:16px;height:16px;flex:none;margin:0;accent-color:var(--action)">
        <span>{{ __('Manter conectado') }}</span>
      </label>
      @if (Route::has('password.request'))
        <a class="link-gold" href="{{ route('password.request') }}">{{ __('Esqueci minha senha') }}</a>
      @endif
    </div>

    <button class="btn btn--primary" type="submit" data-test="login-button"
      style="width:100%;margin-top:var(--space-5)">{{ __('Entrar') }}</button>
  </form>

  <div class="divider" style="margin:var(--space-5) 0"></div>

  <a class="btn btn--secondary" style="width:100%" href="{{ route('auth.google.redirect') }}">
    <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" style="flex:none">
      <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-.8 2.3-1.7 3.1l2.8 2.2c1.6-1.5 2.6-3.8 2.6-6.5 0-.6-.1-1.1-.2-1.7H12z"/>
      <path fill="#34A853" d="M12 22c2.6 0 4.8-.9 6.4-2.5l-2.8-2.2c-.8.5-1.8.9-3.6.9-2.8 0-5.1-1.9-6-4.4l-2.9 2.2C4.8 19.6 8.1 22 12 22z"/>
      <path fill="#4A90E2" d="M6 13.8c-.2-.5-.3-1.1-.3-1.8s.1-1.2.3-1.8L3.1 8C2.4 9.4 2 10.7 2 12s.4 2.6 1.1 4l2.9-2.2z"/>
      <path fill="#FBBC05" d="M12 5.8c1.4 0 2.7.5 3.7 1.5l2.7-2.7C16.8 3 14.6 2 12 2 8.1 2 4.8 4.4 3.1 8L6 10.2c.9-2.5 3.2-4.4 6-4.4z"/>
    </svg>
    {{ __('Entrar com Google') }}</a>

  <p class="muted" style="text-align:center;margin:var(--space-5) 0 0;font-size:var(--text-sm)">
    Ainda não é parceiro?
    <a class="link-gold" href="{{ route('site.cadastro') }}">Cadastre-se como lojista</a></p>

  <p class="notice notice--info" style="margin-top:var(--space-5)">
    <x-velaro.icon name="info" /><span>Autenticação em duas etapas disponível.
      Todo login entra em <code>audit_logs</code> (Anexo I §7).</span></p>

</div>

</x-velaro.layouts.auth>
