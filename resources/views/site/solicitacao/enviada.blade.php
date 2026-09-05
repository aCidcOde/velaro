{{--
[Modulo: resources/views/site/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.5 — confirmacao de recebimento do cadastro, com protocolo, resumo e stepper das quatro etapas.
--}}
@php
    $rotulos = [
        'received' => 'Cadastro recebido',
        'verification' => 'Validação automática CNPJ e CNAE',
        'approval' => 'Aprovação final Velaro',
        'access' => 'Acesso liberado',
    ];
@endphp
<x-velaro.layouts.site title="Solicitação enviada">
    <x-slot:hero>
        <section class="hero"><div class="hero__inner">
            <div>
                <h1>SOLICITAÇÃO ENVIADA</h1>
                <p class="hero-sub">Recebemos seu cadastro com sucesso!</p>
                <p class="lede">Sua solicitação está em análise pela equipe Velaro. Enquanto isso, você pode
                    acompanhar cada etapa do processo diretamente aqui no site.</p>
            </div>
            <div class="hero__art"><div style="width:250px"><x-velaro.ring variant="classica" style="width:100%" /></div></div>
        </div></section>
    </x-slot:hero>

    <section class="band-light"><div class="band__inner">
        <div class="split split--wide">
            <div class="stack">
                <div class="card">
                    <div class="row" style="gap:var(--space-4);align-items:flex-start">
                        <span class="bigcheck"><x-velaro.icon name="check" /></span>
                        <div>
                            <h2 class="title">Cadastro recebido com sucesso!</h2>
                            <p class="lede" style="margin-top:6px">Sua solicitação foi recebida e está em análise.</p>
                            <p class="lede" style="margin-top:var(--space-3);font-size:var(--text-sm)">Nossa equipe está
                                verificando as informações enviadas. Você será notificado por e-mail e WhatsApp sobre cada
                                atualização, e poderá acompanhar o status aqui no site.</p>
                            <div class="row row--wrap" style="margin-top:var(--space-5)">
                                <a class="btn btn--primary" href="{{ route('site.solicitacao.status', ['reseller' => $reseller->protocol]) }}">
                                    <x-velaro.icon name="search" /> Acompanhar minha solicitação</a>
                                <a class="btn btn--secondary" href="{{ route('site.home') }}">← Voltar ao início</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head"><h2 class="title">Acompanhe o andamento do seu cadastro</h2></div>
                    <ol class="stepper">
                        @foreach ($steps as $step)
                            <li class="step step--{{ $step['state'] }}">
                                <span class="step__dot">{{ $step['dot'] }}</span>
                                <span class="step__lab">{{ $rotulos[$step['key']] }}</span>
                                <span class="step__note">{{ $step['note'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div class="stack">
                <div class="card panel-dark">
                    <span class="eyebrow" style="color:var(--color-gold-300)">Status atual</span>
                    <h2 class="display-sm" style="color:#fff;margin-top:var(--space-2)">
                        <x-velaro.icon name="doc" /> Em análise</h2>
                    <p style="margin-top:var(--space-3);font-size:var(--text-sm);line-height:21px;color:rgba(255,255,255,.7)">
                        Assim que houver novidades, entraremos em contato.</p>
                </div>

                <div class="card">
                    <div class="card__head"><h2 class="title">Resumo da solicitação</h2></div>
                    @foreach ([
                        ['store', 'Razão social', $reseller->legal_name],
                        ['doc', 'CNPJ', $reseller->cnpj],
                        ['user', 'Responsável', $reseller->contact_name],
                        ['mail', 'E-mail', $reseller->email],
                        ['pin', 'Cidade/UF', trim($reseller->city.' / '.$reseller->state, ' /')],
                        ['globe', 'Origem do contato', $contactSource],
                    ] as [$icone, $rotulo, $valor])
                        <div class="datarow">
                            <span class="datarow__k"><x-velaro.icon :name="$icone" /> {{ $rotulo }}</span>
                            <span class="datarow__v"><span>{{ $valor ?: '—' }}</span></span>
                        </div>
                    @endforeach
                    <div class="datarow">
                        <span class="datarow__k"><x-velaro.icon name="shield" /> Protocolo</span>
                        <span class="datarow__v"><strong>{{ $reseller->protocol }}</strong></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head"><h2 class="title">Como acompanhar sua solicitação</h2></div>
                    <div class="stack">
                        @foreach ([
                            ['mail', 'Notificações por e-mail', 'Enviaremos atualizações sempre que houver novidades.'],
                            ['whats', 'Avisos via WhatsApp', 'Você também será informado pelo WhatsApp cadastrado.'],
                            ['user', 'Acompanhe no site', 'Faça login com o e-mail e senha criados no cadastro para ver o status atualizado a qualquer momento.'],
                        ] as [$icone, $titulo, $texto])
                            <div class="row" style="gap:10px;align-items:flex-start">
                                <x-velaro.icon :name="$icone" style="color:var(--color-gold-600);flex:none" />
                                <div>
                                    <strong style="display:block;font-size:var(--text-sm);color:var(--ink)">{{ $titulo }}</strong>
                                    <small style="color:var(--ink-muted);font-size:var(--text-xs);line-height:18px">{{ $texto }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="notice notice--gold"><x-velaro.icon name="info" />
                    <span><strong>Importante.</strong> Guarde seu e-mail e senha. Eles serão seu acesso para acompanhar
                        e entrar na plataforma quando seu cadastro for aprovado.</span></p>
            </div>
        </div>
    </div></section>

    <x-slot:pillars>
        @foreach ([
            ['shield', 'Ambiente seguro', 'Seus dados protegidos com criptografia e confidencialidade.'],
            ['brain', 'Validação automática', 'Verificação rápida e precisa de CNPJ e CNAE.'],
            ['support', 'Atendimento consultivo', 'Nossa equipe está pronta para te orientar em cada etapa.'],
            ['book', 'Catálogo liberado', 'Acesso ao catálogo completo após a aprovação.'],
        ] as [$icone, $titulo, $texto])
            <div class="pillar">
                <x-velaro.icon :name="$icone" style="width:32px;height:32px;color:var(--color-gold-400)" />
                <div><h3>{{ $titulo }}</h3><p>{{ $texto }}</p></div>
            </div>
        @endforeach
    </x-slot:pillars>
</x-velaro.layouts.site>
