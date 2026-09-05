{{-- Criacao de conta (linguagem da tela 21): cartao sobre o layout escuro de auth.
     No modelo Velaro o acesso ao portal nasce da aprovacao do cadastro de lojista —
     a tela aponta para /seja-revendedor antes de oferecer o formulario. Rota, csrf e
     os campos seguem os do Fortify; a feature de registro continua ligada. --}}
<x-velaro.layouts.auth :title="__('Criar conta')">
    <div class="stack" style="width:100%;max-width:460px;gap:var(--space-5)">

        <div class="card">
            <span class="eyebrow">Como se torna parceiro</span>
            <h2 class="title" style="margin-top:var(--space-2)">{{ __('O acesso ao portal nasce da aprovação') }}</h2>
            <p class="lede" style="margin:6px 0 var(--space-4)">
                Lojista não se auto-cadastra no portal. Você envia o cadastro de lojista, a Velaro analisa
                CNPJ e CNAE e, aprovado o cadastro, o acesso ao ambiente do Parceiro Premium é liberado.</p>
            <a class="btn btn--primary" style="width:100%" href="{{ route('site.cadastro') }}">
                <x-velaro.icon name="store" /> Cadastre-se como lojista</a>
            <p class="muted" style="text-align:center;margin:var(--space-4) 0 0;font-size:var(--text-sm)">
                Já tem acesso? <a class="link-gold" href="{{ route('login') }}">{{ __('Acessar o sistema') }}</a></p>
            <p class="notice notice--info"><x-velaro.icon name="info" />
                <span>O cliente final não possui login — ele existe como cliente na carteira do Parceiro Premium.</span></p>
        </div>

        <div class="card">
            <h3 class="fsec">{{ __('Criar conta') }}</h3>
            <p class="lede" style="margin:0 0 var(--space-5);font-size:var(--text-sm)">
                {{ __('Preencha seus dados para começar a usar o sistema') }}. Uma conta criada aqui não entra
                no portal do lojista: esse acesso vem da aprovação do cadastro acima.</p>

            @if (session('status'))
                <p class="notice notice--ok" role="status"><x-velaro.icon name="check" />
                    <span>{{ session('status') }}</span></p>
            @endif

            @if ($errors->any())
                <p class="notice notice--danger" role="alert"><x-velaro.icon name="info" />
                    <span>Confira os campos destacados abaixo: a conta ainda não foi criada.</span></p>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf

                <div class="fgrid fgrid--1">
                    <div class="field" @error('name') data-state="error" @enderror>
                        <label for="name">{{ __('Nome completo') }}<i class="req">*</i></label>
                        <input class="input" type="text" id="name" name="name" required autofocus
                               autocomplete="name" value="{{ old('name') }}"
                               placeholder="{{ __('Como devemos te chamar?') }}">
                        @error('name')<p class="field__message">{{ $message }}</p>@enderror
                    </div>

                    <div class="field" @error('email') data-state="error" @enderror>
                        <label for="email">{{ __('E-mail') }}<i class="req">*</i></label>
                        <input class="input" type="email" id="email" name="email" required
                               autocomplete="email" value="{{ old('email') }}" placeholder="seuemail@exemplo.com.br">
                        @error('email')<p class="field__message">{{ $message }}</p>@enderror
                    </div>

                    <div class="field" @error('phone') data-state="error" @enderror>
                        <label for="phone">{{ __('Telefone') }}<i class="req">*</i></label>
                        <input class="input" type="tel" id="phone" name="phone" required inputmode="tel"
                               autocomplete="tel" data-mask="(00) 00000-0000"
                               value="{{ old('phone') }}" placeholder="(11) 99999-0000">
                        @error('phone')<p class="field__message">{{ $message }}</p>@enderror
                    </div>

                    <div class="field" @error('document') data-state="error" @enderror>
                        <label for="document">{{ __('Documento') }}<i class="req">*</i></label>
                        <input class="input" type="text" id="document" name="document" required
                               inputmode="text" value="{{ old('document') }}" placeholder="DOC-123456">
                        @error('document')<p class="field__message">{{ $message }}</p>@enderror
                    </div>

                    <div class="field" @error('password') data-state="error" @enderror>
                        <label for="password">{{ __('Senha') }}<i class="req">*</i></label>
                        <div class="input-shell input-shell--suffix">
                            <input class="input" type="password" id="password" name="password" required
                                   autocomplete="new-password" placeholder="{{ __('Crie uma senha segura') }}"
                                   style="padding-left:var(--space-3)">
                            <button type="button" class="input-shell__suffix" data-reveal="password"
                                    aria-label="Mostrar senha"
                                    style="pointer-events:auto;background:none;border:0;cursor:pointer;color:var(--ink-muted)">
                                <x-velaro.icon name="eye" />
                            </button>
                        </div>
                        @error('password')<p class="field__message">{{ $message }}</p>@enderror
                    </div>

                    <div class="field" @error('password_confirmation') data-state="error" @enderror>
                        <label for="password_confirmation">{{ __('Confirme a senha') }}<i class="req">*</i></label>
                        <div class="input-shell input-shell--suffix">
                            <input class="input" type="password" id="password_confirmation" name="password_confirmation"
                                   required autocomplete="new-password" placeholder="{{ __('Repita a senha escolhida') }}"
                                   style="padding-left:var(--space-3)">
                            <button type="button" class="input-shell__suffix" data-reveal="password_confirmation"
                                    aria-label="Mostrar confirmação de senha"
                                    style="pointer-events:auto;background:none;border:0;cursor:pointer;color:var(--ink-muted)">
                                <x-velaro.icon name="eye" />
                            </button>
                        </div>
                        @error('password_confirmation')<p class="field__message">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="btn btn--secondary" style="width:100%;margin-top:var(--space-5)"
                        data-test="register-user-button">{{ __('Criar conta') }}</button>
            </form>
        </div>
    </div>

    <script>
        // Mascara e olho da senha, iguais aos do cadastro publico: o layout Velaro
        // nao carrega o app.js do scaffold, entao o comportamento vem daqui.
        (() => {
            document.querySelectorAll('[data-mask]').forEach((campo) => {
                const molde = campo.dataset.mask;
                const aplicar = () => {
                    const numeros = campo.value.replace(/\D/g, '');
                    let saida = '';
                    let indice = 0;
                    for (const caractere of molde) {
                        if (indice >= numeros.length) break;
                        saida += caractere === '0' ? numeros[indice++] : caractere;
                    }
                    campo.value = saida;
                };
                campo.addEventListener('input', aplicar);
                aplicar();
            });

            document.querySelectorAll('[data-reveal]').forEach((botao) => {
                botao.addEventListener('click', () => {
                    const campo = document.getElementById(botao.dataset.reveal);
                    if (!campo) return;
                    campo.type = campo.type === 'password' ? 'text' : 'password';
                });
            });
        })();
    </script>
</x-velaro.layouts.auth>
