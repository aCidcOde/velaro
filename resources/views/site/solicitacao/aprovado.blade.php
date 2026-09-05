{{--
[Modulo: resources/views/site/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.7 — cadastro aprovado: confirmacao da liberacao e o caminho para a plataforma do parceiro.
--}}
<x-velaro.layouts.site title="Cadastro aprovado">
    <x-slot:hero>
        <section class="hero"><div class="hero__inner">
            <div>
                <h1>CADASTRO APROVADO!</h1>
                <p class="hero-sub">Parabéns! Seu cadastro foi aprovado com sucesso.</p>
                <p class="lede">Agora você já pode acessar a plataforma B2B Velaro e aproveitar todas as vantagens
                    exclusivas para lojistas.</p>
            </div>
            <div class="hero__art">
                <div class="notifcard">
                    <div class="notifcard__head"><x-velaro.icon name="whats" /><strong>WhatsApp</strong><span>agora</span></div>
                    <strong style="color:var(--ink)">Velaro Alianças</strong>
                    <p>Olá! Seu cadastro foi aprovado. Seu acesso à plataforma B2B já está liberado.</p>
                    <span class="notifcard__ok"><x-velaro.icon name="check" /></span>
                </div>
            </div>
        </div></section>
    </x-slot:hero>

    <section class="band-light"><div class="band__inner">
        <div class="split split--wide">
            <div class="card">
                <h2 class="title"><x-velaro.icon name="store" /> Seu cadastro foi aprovado</h2>
                <p class="notice notice--ok"><x-velaro.icon name="info" />
                    <span><strong>Seu acesso à plataforma B2B Velaro foi liberado com sucesso.</strong>
                        Você já pode explorar o catálogo, consultar preços e realizar pedidos.</span></p>

                <h3 class="fsec">Próximo passo</h3>
                <div class="row" style="gap:var(--space-4);align-items:flex-start">
                    <x-velaro.icon name="eye" style="color:var(--color-gold-600);flex:none" />
                    <p class="lede">Acesse sua plataforma e aproveite todas as vantagens exclusivas para lojistas
                        parceiros Velaro.</p>
                </div>

                <a class="btn btn--primary" style="width:100%;margin-top:var(--space-6);min-height:56px"
                   href="{{ route('portal.dashboard') }}">Acessar minha plataforma ›</a>
                <p class="muted" style="text-align:center;margin:var(--space-3) 0 0;font-size:var(--text-xs)">
                    <x-velaro.icon name="mail" /> <x-velaro.icon name="whats" />
                    Enviamos os dados de acesso para seu e-mail e WhatsApp.</p>
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
                    <h3 class="title" style="color:var(--color-gold-300)">O que você pode fazer agora?</h3>
                    <ul class="cklist cklist--dark">
                        @foreach ([
                            'Explorar o catálogo completo',
                            'Consultar preços exclusivos',
                            'Realizar pedidos com agilidade',
                            'Acompanhar seus pedidos e novidades',
                        ] as $item)
                            <li><x-velaro.icon name="check" />{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div></section>
</x-velaro.layouts.site>
