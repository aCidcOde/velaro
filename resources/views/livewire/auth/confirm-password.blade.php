{{-- Reconfirmacao de senha em area sensivel (linguagem da tela 21): cartao sobre o
     layout escuro de auth. Rota, csrf e o campo password seguem os do Fortify. --}}
<x-velaro.layouts.auth :title="__('Confirmar senha')">
    <div class="card" style="width:100%;max-width:420px">
        <span class="eyebrow">Área protegida</span>
        <h2 class="title" style="margin-top:var(--space-2)">{{ __('Confirmar senha') }}</h2>
        <p class="lede" style="margin:6px 0 var(--space-5)">
            {{ __('Esta é uma área segura do sistema. Confirme sua senha para continuar.') }}</p>

        @if (session('status'))
            <p class="notice notice--ok" role="status"><x-velaro.icon name="check" />
                <span>{{ session('status') }}</span></p>
        @endif

        <form method="POST" action="{{ route('password.confirm.store') }}">
            @csrf

            <div class="fgrid fgrid--1">
                <div class="field" @error('password') data-state="error" @enderror>
                    <label for="password">{{ __('Senha') }}<i class="req">*</i></label>
                    <div class="input-shell input-shell--suffix">
                        <input class="input" type="password" id="password" name="password" required autofocus
                               autocomplete="current-password" placeholder="{{ __('Informe sua senha atual') }}"
                               style="padding-left:var(--space-3)">
                        <button type="button" class="input-shell__suffix" data-reveal="password"
                                aria-label="Mostrar senha"
                                style="pointer-events:auto;background:none;border:0;cursor:pointer;color:var(--ink-muted)">
                            <x-velaro.icon name="eye" />
                        </button>
                    </div>
                    @error('password')<p class="field__message">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn--primary" style="width:100%;margin-top:var(--space-5)"
                    data-test="confirm-password-button">{{ __('Confirmar') }}</button>
        </form>

        <p class="notice notice--info"><x-velaro.icon name="lock" />
            <span>A confirmação vale por um período curto e é pedida de novo em cada ação sensível.</span></p>
    </div>

    <script>
        // O olho da senha, igual ao do cadastro publico: o CSS nao troca o type do campo.
        document.querySelectorAll('[data-reveal]').forEach((botao) => {
            botao.addEventListener('click', () => {
                const campo = document.getElementById(botao.dataset.reveal);
                if (!campo) return;
                campo.type = campo.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
</x-velaro.layouts.auth>
