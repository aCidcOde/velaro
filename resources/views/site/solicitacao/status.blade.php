{{--
[Modulo: resources/views/site/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.6 — acompanhamento do cadastro: stepper, triagem automatica, linha do tempo e dados da solicitacao.
--}}
@php
    $rotulos = [
        'received' => 'Cadastro recebido',
        'verification' => 'Validação automática',
        'approval' => 'Aprovação final Velaro',
        'access' => 'Acesso liberado',
    ];
    $painel = match ($reseller->status) {
        \App\Models\Reseller::STATUS_APPROVED => ['Aprovado', 'Seu cadastro foi aprovado. O acesso à plataforma do parceiro já está liberado.'],
        \App\Models\Reseller::STATUS_REJECTED => ['Cadastro reprovado', $reseller->rejection_reason ?: 'A equipe Velaro não aprovou este cadastro. Fale conosco para entender os próximos passos.'],
        \App\Models\Reseller::STATUS_INACTIVE => ['Cadastro inativo', 'Este cadastro está inativo. Fale com nossa equipe para reativá-lo.'],
        default => ['Em validação automática', 'Nossa IA já concluiu parte da análise. Assim que esta etapa for finalizada, seu cadastro seguirá para aprovação final da equipe Velaro.'],
    };
@endphp
<x-velaro.layouts.site title="Status da solicitação">
    <x-slot:hero>
        <section class="hero"><div class="hero__inner">
            <div>
                <span class="badge-b2b"><x-velaro.icon name="users" /> Área do lojista / Pré-cadastro</span>
                <h1>STATUS DA SUA SOLICITAÇÃO</h1>
                <p class="lede">Acompanhe em tempo real a validação automática do seu cadastro e as próximas etapas
                    até a liberação do acesso.</p>
                @guest
                    <p class="hero__note"><x-velaro.icon name="info" /> Faça login para acompanhar sua solicitação.</p>
                @endguest
            </div>
            <div class="hero__art"><div style="width:250px"><x-velaro.ring variant="classica" style="width:100%" /></div></div>
        </div></section>
    </x-slot:hero>

    <section class="band-light"><div class="band__inner">
        <div class="identbar">
            @foreach ([
                ['store', 'Parceiro', $reseller->legal_name],
                ['shield', 'Protocolo', $reseller->protocol],
                ['user', 'Responsável', $reseller->contact_name],
                ['mail', 'Login vinculado', $reseller->email],
                ['clock', 'Última atualização', $lastUpdated],
            ] as [$icone, $rotulo, $valor])
                <div class="identcell">
                    <x-velaro.icon :name="$icone" />
                    <span><small>{{ $rotulo }}</small><strong>{{ $valor ?: '—' }}</strong></span>
                </div>
            @endforeach
        </div>

        @if (session('status'))
            <p class="notice notice--ok" style="margin-top:var(--space-4)">
                <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
        @endif

        <div class="split split--wide" style="margin-top:var(--space-4)">
            <div class="stack">
                <div class="card">
                    <x-velaro.solicitacao.stepper :steps="$steps" :rotulos="$rotulos" />
                </div>

                <div class="card">
                    <x-velaro.solicitacao.verificacao :checks="$checks" />
                </div>

                <x-velaro.solicitacao.reenvio-documentos :reseller="$reseller" :documentos="$documentos" />

                <div class="split" style="--gcols:1fr 1fr">
                    <div class="card">
                        <div class="card__head"><h2 class="title">Linha do tempo da solicitação</h2></div>
                        <x-velaro.solicitacao.linha-do-tempo :timeline="$timeline" />
                    </div>

                    <div class="card">
                        <div class="card__head"><h2 class="title">Dados da solicitação</h2></div>
                        @foreach ([
                            ['doc', 'CNPJ', $reseller->cnpj],
                            ['pin', 'Cidade / UF', trim($reseller->city.' / '.$reseller->state, ' /')],
                            ['globe', 'Origem do contato', $contactSource],
                            ['whats', 'WhatsApp', $reseller->whatsapp],
                            ['mail', 'E-mail', $reseller->email],
                        ] as [$icone, $rotulo, $valor])
                            <div class="datarow">
                                <span class="datarow__k"><x-velaro.icon :name="$icone" /> {{ $rotulo }}</span>
                                <span class="datarow__v">{{ $valor ?: '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row row--wrap">
                    <a class="btn btn--gold" href="{{ route('site.solicitacao.status', ['reseller' => $reseller->protocol]) }}">
                        <x-velaro.icon name="refresh" /> Atualizar status</a>
                    <a class="btn btn--secondary" href="{{ route('site.contato') }}">
                        <x-velaro.icon name="support" /> Falar com nossa equipe</a>
                </div>
            </div>

            <div class="stack">
                <div class="card panel-dark">
                    <span class="eyebrow" style="color:var(--color-gold-300)">Status atual</span>
                    <h2 class="display-sm" style="color:#fff;margin-top:var(--space-2)">{{ $painel[0] }}</h2>
                    <p style="margin-top:var(--space-3);font-size:var(--text-sm);line-height:21px;color:rgba(255,255,255,.7)">
                        {{ $painel[1] }}</p>
                </div>

                <div class="card">
                    <div class="card__head"><h2 class="title">Como acompanhar</h2></div>
                    <div class="stack">
                        @foreach ([
                            ['eye', 'Veja o andamento sempre que quiser fazendo login na área do parceiro.'],
                            ['bell', 'Você receberá atualizações por e-mail e WhatsApp.'],
                            ['lock', 'Se aprovado, o mesmo login dará acesso à plataforma completa.'],
                        ] as [$icone, $texto])
                            <div class="row" style="gap:10px;align-items:flex-start">
                                <x-velaro.icon :name="$icone" style="color:var(--color-gold-600);flex:none" />
                                <small style="font-size:var(--text-sm);line-height:20px;color:var(--ink-body)">{{ $texto }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <div class="card__head"><h2 class="title">Próximas etapas</h2></div>
                    <ol class="howto">
                        @foreach ([
                            'Conclusão da validação automática com IA.',
                            'Envio de contrato para aprovação final da equipe Velaro.',
                            'Aprovação concluída: acesso liberado à plataforma do parceiro.',
                        ] as $indice => $texto)
                            <li><span class="num">{{ $indice + 1 }}</span><div><strong>{{ $texto }}</strong></div></li>
                        @endforeach
                    </ol>
                </div>

                <p class="notice notice--gold"><x-velaro.icon name="info" />
                    <span>A IA faz a <strong>triagem</strong>. A decisão final é sempre <strong>humana</strong>
                        e fica registrada com justificativa.</span></p>
            </div>
        </div>
    </div></section>

    <x-slot:pillars>
        @foreach ([
            ['shield', 'Ambiente seguro', 'Seus dados protegidos com criptografia e segurança.'],
            ['brain', 'Validação automática', 'Processos com IA para mais agilidade e precisão.'],
            ['support', 'Atendimento consultivo', 'Nossa equipe está sempre para te apoiar em cada etapa.'],
            ['lock', 'Acesso liberado após aprovação', 'Acesso total à plataforma após a aprovação final.'],
        ] as [$icone, $titulo, $texto])
            <div class="pillar">
                <x-velaro.icon :name="$icone" style="width:32px;height:32px;color:var(--color-gold-400)" />
                <div><h3>{{ $titulo }}</h3><p>{{ $texto }}</p></div>
            </div>
        @endforeach
    </x-slot:pillars>
</x-velaro.layouts.site>
