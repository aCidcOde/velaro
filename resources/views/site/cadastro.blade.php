{{--
[Modulo: resources/views/site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.4 — formulario publico de cadastro de lojista, com uploads obrigatorios e aceites de LGPD.
--}}
@php
    // Input de arquivo fora da tela mas ainda focavel: o cartao .upload continua
    // sendo o alvo de clique, como no prototipo.
    $hiddenFile = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0';
    $documentos = [
        'articles_of_incorporation' => 'Contrato social',
        'partner_id_document' => 'Documento do sócio / responsável',
        'cnpj_card' => 'Cartão ou comprovante do CNPJ',
    ];
@endphp
<x-velaro.layouts.site title="Cadastro como lojista">
    <x-slot:hero>
        <section class="hero"><div class="hero__inner">
            <div>
                <h1>CADASTRO COMO LOJISTA</h1>
                <p class="hero-sub">Solicite seu acesso à plataforma B2B Velaro.</p>
                <p class="lede">Cadastro exclusivo para lojistas com CNPJ e atividade compatível com o segmento.
                    Após o cadastro, seu CNPJ e CNAE passam por validação automática e aprovação final da equipe Velaro.</p>
            </div>
            <div class="hero__art"><div style="width:270px"><x-velaro.ring variant="classica" style="width:100%" /></div></div>
        </div></section>
    </x-slot:hero>

    <section class="band-light"><div class="band__inner">
        <div class="split split--wide">
            <div class="card">
                <div class="card__head"><h2 class="title"><x-velaro.icon name="user-plus" /> Faça seu cadastro como lojista</h2></div>

                @if ($errors->any())
                    <p class="notice notice--danger" role="alert"><x-velaro.icon name="info" />
                        <span>Confira os campos destacados abaixo: o cadastro ainda não foi enviado.</span></p>
                @endif

                <form method="POST" action="{{ route('site.cadastro.store') }}" enctype="multipart/form-data">
                    @csrf

                    <h3 class="fsec">Dados da empresa</h3>
                    <div class="fgrid fgrid--3">
                        <div class="field" @error('legal_name') data-state="error" @enderror>
                            <label for="legal_name">Razão social<i class="req">*</i></label>
                            <input class="input" type="text" id="legal_name" name="legal_name" required
                                   maxlength="255" autocomplete="organization"
                                   value="{{ old('legal_name') }}" placeholder="Digite a razão social da empresa">
                            @error('legal_name')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('trade_name') data-state="error" @enderror>
                            <label for="trade_name">Nome fantasia<i class="req">*</i></label>
                            <input class="input" type="text" id="trade_name" name="trade_name" required
                                   maxlength="255" value="{{ old('trade_name') }}" placeholder="Digite o nome fantasia">
                            @error('trade_name')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('cnpj') data-state="error" @enderror>
                            <label for="cnpj">CNPJ<i class="req">*</i></label>
                            <input class="input" type="text" id="cnpj" name="cnpj" required inputmode="numeric"
                                   maxlength="18" data-mask="00.000.000/0000-00"
                                   value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00">
                            @error('cnpj')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('state_registration') data-state="error" @enderror>
                            <label for="state_registration">Inscrição estadual</label>
                            <input class="input" type="text" id="state_registration" name="state_registration"
                                   maxlength="30" value="{{ old('state_registration') }}" placeholder="Digite a inscrição estadual">
                            @error('state_registration')<p class="field__message">{{ $message }}</p>@else
                                <small class="fhint">Quando aplicável</small>
                            @enderror
                        </div>
                        <div class="field" @error('contact_name') data-state="error" @enderror>
                            <label for="contact_name">Nome do responsável<i class="req">*</i></label>
                            <input class="input" type="text" id="contact_name" name="contact_name" required
                                   maxlength="255" autocomplete="name"
                                   value="{{ old('contact_name') }}" placeholder="Digite o nome do responsável">
                            @error('contact_name')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('contact_cpf') data-state="error" @enderror>
                            <label for="contact_cpf">CPF do responsável / sócio<i class="req">*</i></label>
                            <input class="input" type="text" id="contact_cpf" name="contact_cpf" required inputmode="numeric"
                                   maxlength="14" data-mask="000.000.000-00"
                                   value="{{ old('contact_cpf') }}" placeholder="000.000.000-00">
                            @error('contact_cpf')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h3 class="fsec">Endereço</h3>
                    <div class="fgrid fgrid--3">
                        <div class="field" @error('postal_code') data-state="error" @enderror>
                            <label for="postal_code">CEP<i class="req">*</i></label>
                            <input class="input" type="text" id="postal_code" name="postal_code" required inputmode="numeric"
                                   maxlength="9" data-mask="00000-000" autocomplete="postal-code"
                                   value="{{ old('postal_code') }}" placeholder="00000-000">
                            @error('postal_code')<p class="field__message">{{ $message }}</p>@else
                                <small class="fhint">Usado para conferência do endereço</small>
                            @enderror
                        </div>
                        <div class="field" @error('street') data-state="error" @enderror>
                            <label for="street">Endereço<i class="req">*</i></label>
                            <input class="input" type="text" id="street" name="street" required maxlength="255"
                                   autocomplete="address-line1" value="{{ old('street') }}" placeholder="Rua, avenida…">
                            @error('street')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('street_number') data-state="error" @enderror>
                            <label for="street_number">Número<i class="req">*</i></label>
                            <input class="input" type="text" id="street_number" name="street_number" required
                                   maxlength="30" value="{{ old('street_number') }}" placeholder="1234">
                            @error('street_number')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('address_complement') data-state="error" @enderror>
                            <label for="address_complement">Complemento</label>
                            <input class="input" type="text" id="address_complement" name="address_complement"
                                   maxlength="255" value="{{ old('address_complement') }}" placeholder="Sala, bloco…">
                            @error('address_complement')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('district') data-state="error" @enderror>
                            <label for="district">Bairro<i class="req">*</i></label>
                            <input class="input" type="text" id="district" name="district" required maxlength="255"
                                   value="{{ old('district') }}" placeholder="Centro">
                            @error('district')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @if($errors->hasAny(['city', 'state'])) data-state="error" @endif>
                            <label for="city">Cidade / UF<i class="req">*</i></label>
                            <div class="row" style="gap:var(--space-2)">
                                <input class="input" type="text" id="city" name="city" required maxlength="255"
                                       autocomplete="address-level2" value="{{ old('city') }}" placeholder="Cidade">
                                <select class="select" id="state" name="state" required aria-label="UF" style="max-width:104px">
                                    <option value="">UF</option>
                                    @foreach ($states as $code => $name)
                                        <option value="{{ $code }}" @selected(old('state') === $code)>{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('city')<p class="field__message">{{ $message }}</p>@enderror
                            @error('state')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h3 class="fsec">Contato e acesso</h3>
                    <div class="fgrid fgrid--3">
                        <div class="field" @error('email') data-state="error" @enderror>
                            <label for="email">E-mail<i class="req">*</i></label>
                            <input class="input" type="email" id="email" name="email" required maxlength="255"
                                   autocomplete="email" value="{{ old('email') }}" placeholder="seuemail@exemplo.com.br">
                            @error('email')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('whatsapp') data-state="error" @enderror>
                            <label for="whatsapp">WhatsApp<i class="req">*</i></label>
                            <input class="input" type="text" id="whatsapp" name="whatsapp" required inputmode="tel"
                                   maxlength="16" data-mask="(00) 00000-0000" autocomplete="tel"
                                   value="{{ old('whatsapp') }}" placeholder="(00) 00000-0000">
                            @error('whatsapp')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('contact_source') data-state="error" @enderror>
                            <label for="contact_source">Origem do contato<i class="req">*</i></label>
                            <select class="select" id="contact_source" name="contact_source" required>
                                <option value="">Selecione a origem</option>
                                @foreach ($contactSources as $key => $label)
                                    <option value="{{ $key }}" @selected(old('contact_source') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('contact_source')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field" @error('password') data-state="error" @enderror>
                            <label for="password">Criar senha<i class="req">*</i></label>
                            <div class="input-shell input-shell--suffix">
                                <input class="input" type="password" id="password" name="password" required minlength="8"
                                       autocomplete="new-password" placeholder="Mínimo 8 caracteres" style="padding-left:var(--space-3)">
                                <button type="button" class="input-shell__suffix" data-reveal="password"
                                        aria-label="Mostrar senha" style="pointer-events:auto;background:none;border:0;cursor:pointer;color:var(--ink-muted)">
                                    <x-velaro.icon name="eye" />
                                </button>
                            </div>
                            @error('password')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirmar senha<i class="req">*</i></label>
                            <div class="input-shell input-shell--suffix">
                                <input class="input" type="password" id="password_confirmation" name="password_confirmation"
                                       required minlength="8" autocomplete="new-password" placeholder="Confirme sua senha"
                                       style="padding-left:var(--space-3)">
                                <button type="button" class="input-shell__suffix" data-reveal="password_confirmation"
                                        aria-label="Mostrar confirmação de senha" style="pointer-events:auto;background:none;border:0;cursor:pointer;color:var(--ink-muted)">
                                    <x-velaro.icon name="eye" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="fgrid fgrid--1" style="margin-top:var(--space-4)">
                        <div class="field" @error('notes') data-state="error" @enderror>
                            <div class="field__head">
                                <label for="notes">Observações</label>
                                <span class="field__counter" data-counter-for="notes">0/300</span>
                            </div>
                            <textarea class="textarea" id="notes" name="notes" maxlength="300"
                                      placeholder="Conte o que mais precisamos saber sobre a sua loja.">{{ old('notes') }}</textarea>
                            @error('notes')<p class="field__message">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h3 class="fsec">Documentos</h3>
                    <div class="fgrid fgrid--3">
                        @foreach ($documentos as $campo => $rotulo)
                            <label class="upload" @error($campo) style="border-color:var(--color-error-500)" @enderror>
                                <span class="upload__ic"><x-velaro.icon name="upload" /></span>
                                <strong>{{ $rotulo }}<i class="req">*</i></strong>
                                <small>PDF, PNG ou JPG · máx. {{ intdiv($maxDocumentKb, 1024) }}MB</small>
                                <input type="file" id="{{ $campo }}" name="{{ $campo }}" required
                                       accept=".pdf,.png,.jpg,.jpeg" style="{{ $hiddenFile }}" data-upload>
                                <small class="fhint" data-upload-name="{{ $campo }}"></small>
                                @error($campo)<small class="field__message">{{ $message }}</small>@enderror
                            </label>
                        @endforeach
                    </div>

                    <h3 class="fsec">Aceites</h3>
                    <div class="stacklist">
                        @foreach ([
                            ['accept_business', 'Declaro que sou lojista / empresa formalizada.'],
                            ['accept_verification', 'Autorizo a validação automática do meu CNPJ e CNAE.'],
                        ] as [$campo, $texto])
                            <label class="checkline" style="align-items:flex-start">
                                <input type="checkbox" name="{{ $campo }}" value="1" required @checked(old($campo))
                                       style="flex:none;margin-top:2px;width:16px;height:16px;accent-color:var(--action)">
                                <span>{{ $texto }}
                                    @error($campo)<br><span class="field__message">{{ $message }}</span>@enderror
                                </span>
                            </label>
                        @endforeach
                        <label class="checkline" style="align-items:flex-start">
                            <input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms'))
                                   style="flex:none;margin-top:2px;width:16px;height:16px;accent-color:var(--action)">
                            <span>Li e concordo com a <a href="{{ route('site.privacidade') }}" class="link-gold">Política de Privacidade</a>
                                e os <a href="{{ route('site.termos') }}" class="link-gold">Termos de Uso</a>.
                                @error('accept_terms')<br><span class="field__message">{{ $message }}</span>@enderror
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn--primary" style="width:100%;margin-top:var(--space-6)">
                        Enviar cadastro ›</button>
                    <p class="muted" style="text-align:center;margin:var(--space-3) 0 0;font-size:var(--text-xs)">
                        <x-velaro.icon name="mail" /> <x-velaro.icon name="whats" />
                        Você receberá atualizações por e-mail e WhatsApp.</p>
                </form>
            </div>

            <div class="stack">
                <div class="card panel-dark">
                    <h3 class="title" style="color:var(--color-gold-300)">Como funciona</h3>
                    <ol class="howto">
                        @foreach ([
                            ['Cadastro', 'Preencha seus dados e envie seu cadastro.'],
                            ['Validação automática CNPJ + CNAE', 'Nosso sistema valida as informações automaticamente.'],
                            ['Aprovação final Velaro', 'Nossa equipe analisa e confirma a compatibilidade.'],
                            ['Acesso liberado', 'Você recebe seus acessos e começa a comprar.'],
                        ] as $indice => [$titulo, $texto])
                            <li><span class="num">{{ $indice + 1 }}</span>
                                <div><strong>{{ $titulo }}</strong><p>{{ $texto }}</p></div></li>
                        @endforeach
                    </ol>
                </div>

                <div class="card panel-dark">
                    <h3 class="title" style="color:var(--color-gold-300)">Quem pode se cadastrar?</h3>
                    <ul class="cklist cklist--dark">
                        @foreach (['Joalherias', 'Lojas de alianças', 'Empresas com CNPJ', 'Atividade compatível com o segmento'] as $item)
                            <li><x-velaro.icon name="check" />{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="card panel-dark">
                    <div class="grid g2" style="gap:var(--space-4)">
                        @foreach ([
                            ['shield', 'Ambiente seguro', 'Seus dados protegidos com criptografia e privacidade.'],
                            ['support', 'Atendimento consultivo', 'Suporte dedicado para lojistas em todas as etapas.'],
                            ['diamond', 'Condições exclusivas', 'Preços e condições especiais para parceiros aprovados.'],
                            ['book', 'Catálogo completo', 'Acesso ao catálogo completo após aprovação.'],
                        ] as [$icone, $titulo, $texto])
                            <div class="row" style="gap:10px;align-items:flex-start">
                                <x-velaro.icon :name="$icone" style="color:var(--color-gold-400);flex:none" />
                                <div>
                                    <strong style="display:block;color:#fff;font-size:var(--text-sm)">{{ $titulo }}</strong>
                                    <small style="color:rgba(255,255,255,.6);font-size:var(--text-xs);line-height:17px">{{ $texto }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div></section>

    <script>
        // Comportamentos que o CSS nao faz: mascara, contador, olho da senha e o
        // nome do arquivo escolhido em cada cartao de upload.
        (() => {
            const digitos = (valor) => valor.replace(/\D/g, '');
            document.querySelectorAll('[data-mask]').forEach((campo) => {
                const molde = campo.dataset.mask;
                const aplicar = () => {
                    const numeros = digitos(campo.value);
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

            document.querySelectorAll('[data-counter-for]').forEach((contador) => {
                const campo = document.getElementById(contador.dataset.counterFor);
                if (!campo) return;
                const atualizar = () => { contador.textContent = `${campo.value.length}/${campo.maxLength}`; };
                campo.addEventListener('input', atualizar);
                atualizar();
            });

            document.querySelectorAll('[data-reveal]').forEach((botao) => {
                botao.addEventListener('click', () => {
                    const campo = document.getElementById(botao.dataset.reveal);
                    if (!campo) return;
                    campo.type = campo.type === 'password' ? 'text' : 'password';
                });
            });

            document.querySelectorAll('input[data-upload]').forEach((campo) => {
                campo.addEventListener('change', () => {
                    const alvo = document.querySelector(`[data-upload-name="${campo.name}"]`);
                    if (alvo) alvo.textContent = campo.files[0] ? campo.files[0].name : '';
                });
            });
        })();
    </script>
</x-velaro.layouts.site>
