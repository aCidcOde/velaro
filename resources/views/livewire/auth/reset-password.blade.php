{{-- Tela 21 (segunda etapa) · redefinição de senha pelo link recebido. Mesma
     linguagem visual do pedido de recuperação. O token, os nomes de campo e a
     ação continuam os do Fortify — muda só a casca. --}}
<x-velaro.layouts.auth :title="__('Redefinir senha')">

<div class="card" style="width:100%;max-width:420px">

  <h2 class="title">{{ __('Redefinir senha') }}</h2>
  <p class="lede" style="margin:6px 0 var(--space-5)">Informe sua nova senha abaixo para continuar.
    O link só pode ser usado uma vez.</p>

  @if (session('status'))
    <p class="notice notice--ok" style="margin-bottom:var(--space-5)" role="status">
      <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ request()->route('token') }}">

    <div class="fgrid fgrid--1">

      <div class="field" @error('email') data-state="error" @enderror>
        <label for="reset-email">{{ __('E-mail') }}<i class="req">*</i></label>
        <input class="input" type="email" id="reset-email" name="email"
          value="{{ old('email', request('email')) }}" placeholder="seuemail@exemplo.com.br"
          maxlength="255" autocomplete="email" required>
        @error('email')<small class="field__message">{{ $message }}</small>@enderror
      </div>

      <div class="field" @error('password') data-state="error" @enderror>
        <label for="reset-password">{{ __('Nova senha') }}<i class="req">*</i></label>
        <input class="input" type="password" id="reset-password" name="password"
          placeholder="{{ __('Crie uma nova senha segura') }}" autocomplete="new-password" required autofocus>
        @error('password')<small class="field__message">{{ $message }}</small>@enderror
      </div>

      <div class="field" @error('password_confirmation') data-state="error" @enderror>
        <label for="reset-password-confirmation">{{ __('Confirme a nova senha') }}<i class="req">*</i></label>
        <input class="input" type="password" id="reset-password-confirmation" name="password_confirmation"
          placeholder="{{ __('Repita a nova senha') }}" autocomplete="new-password" required>
        @error('password_confirmation')<small class="field__message">{{ $message }}</small>@enderror
      </div>

    </div>

    <button class="btn btn--primary" type="submit" data-test="reset-password-button"
      style="width:100%;margin-top:var(--space-5)">
      <x-velaro.icon name="lock" /> {{ __('Redefinir senha') }}</button>
  </form>

  <p class="muted" style="text-align:center;margin:var(--space-4) 0 0;font-size:var(--text-sm)">
    <a class="link-gold" href="{{ route('login') }}">← {{ __('Voltar para o login') }}</a></p>

  <p class="notice notice--info" style="margin-top:var(--space-5)">
    <x-velaro.icon name="shield" /><span>Perfil, permissões e vínculo com o revendedor continuam os mesmos.
      A troca de senha entra em <code>audit_logs</code> (Anexo I §7).</span></p>

</div>

</x-velaro.layouts.auth>
