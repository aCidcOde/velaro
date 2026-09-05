{{-- Desafio de segundo fator (tela 0 / 21): cartao sobre o layout escuro de auth.
     Sem Alpine — o layout Velaro carrega so o velaro.js, entao a troca entre codigo
     do app e codigo de recuperacao e feita por <details> nativo, como no resto do
     design system. Rota, csrf e os campos code/recovery_code seguem os do Fortify. --}}
<x-velaro.layouts.auth :title="__('Verificação em duas etapas')">
    <div class="card" style="width:100%;max-width:420px">
        <span class="eyebrow">Acesso protegido</span>
        <h2 class="title" style="margin-top:var(--space-2)">{{ __('Código de autenticação') }}</h2>
        <p class="lede" style="margin:6px 0 var(--space-5)">{{ __('Informe o código gerado pelo seu aplicativo autenticador.') }}</p>

        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf

            <div class="fgrid fgrid--1">
                <div class="field" @error('code') data-state="error" @enderror>
                    <label for="code">{{ __('Código de autenticação') }}<i class="req">*</i></label>
                    <input class="input" type="text" id="code" name="code" autofocus
                           inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                           placeholder="000000" style="letter-spacing:.42em;text-align:center;font-size:var(--text-title)">
                    @error('code')<p class="field__message">{{ $message }}</p>@else
                        <small class="fhint">{{ __('Seis dígitos, renovados a cada 30 segundos.') }}</small>
                    @enderror
                </div>
            </div>

            <details @if ($errors->has('recovery_code')) open @endif style="margin-top:var(--space-4)">
                <summary class="link-gold">{{ __('Perdeu o aparelho? Entrar com um código de recuperação') }}</summary>
                <div class="field" @error('recovery_code') data-state="error" @enderror style="margin-top:var(--space-3)">
                    <label for="recovery_code">{{ __('Código de recuperação') }}</label>
                    <input class="input" type="text" id="recovery_code" name="recovery_code"
                           autocomplete="one-time-code" placeholder="xxxxxxxx-xxxxxxxx"
                           value="{{ old('recovery_code') }}">
                    @error('recovery_code')<p class="field__message">{{ $message }}</p>@else
                        <small class="fhint">{{ __('Confirme o acesso informando um dos seus códigos de recuperação. Cada código serve uma única vez.') }}</small>
                    @enderror
                </div>
            </details>

            <button type="submit" class="btn btn--primary" style="width:100%;margin-top:var(--space-5)">
                {{ __('Continuar') }}</button>
        </form>

        <p class="muted" style="text-align:center;margin:var(--space-4) 0 0;font-size:var(--text-sm)">
            <a class="link-gold" href="{{ route('login') }}">&larr; {{ __('Voltar para o login') }}</a></p>

        <p class="notice notice--info"><x-velaro.icon name="shield" />
            <span>Preencha só um dos dois campos. Toda tentativa de acesso entra em <code>audit_logs</code> (Anexo I §7).</span></p>
    </div>
</x-velaro.layouts.auth>
