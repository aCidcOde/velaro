@extends('layouts.public')

@section('title', config('app.name', 'CodaFácil') . ' — Scaffold Laravel')
@section('header_class', 'alt')

@section('content')
    <section id="banner">
        <div class="inner">
            <div class="logo">
                <span class="app-brand-badge">
                    <img src="{{ asset('logo.webp') }}" alt="{{ config('app.name', 'CodaFácil') }}">
                </span>
            </div>
            <h2>{{ config('app.name', 'CodaFácil') }}</h2>
            <p>Base pronta para criar novos sistemas com site, front autenticado, backend, API mobile e agente local.</p>
            <ul class="actions">
                <li><a href="{{ route('public.services') }}" class="button">Ver módulos</a></li>
                <li><a href="{{ route('login') }}" class="button">Acessar sistema</a></li>
            </ul>
        </div>
    </section>

    <section id="wrapper">
        <section class="wrapper spotlight style1">
            <div class="inner">
                <a href="{{ route('public.about') }}" class="image"><img src="{{ asset('orbit.png') }}" alt="Estrutura do framework" /></a>
                <div class="content">
                    <h2 class="major">Estrutura que nasce operacional</h2>
                    <p>
                        O framework já parte com autenticação, ACL, jobs, scheduler, API mobile e módulo de agente. A proposta é começar um produto novo
                        em cima de um núcleo limpo, sem reabrir decisões básicas a cada projeto.
                    </p>
                    <a href="{{ route('public.about') }}" class="special">Entender a base</a>
                </div>
            </div>
        </section>

        <section class="wrapper alt spotlight style2">
            <div class="inner">
                <a href="{{ route('public.services') }}" class="image"><img src="{{ asset('images/quem-somos-office.webp') }}" alt="Módulos do sistema" /></a>
                <div class="content">
                    <h2 class="major">Cinco blocos principais no mesmo repositório</h2>
                    <p>
                        Site público, operação do cliente, backend, API mobile e agente local coexistem no mesmo fluxo. Isso acelera produto, suporte,
                        manutenção e futuras extensões de domínio.
                    </p>
                    <a href="{{ route('public.services') }}" class="special">Ver os módulos</a>
                </div>
            </div>
        </section>

        <section class="wrapper spotlight style3">
            <div class="inner">
                <a href="{{ route('dashboard') }}" class="image"><img src="{{ asset('logo-dark.webp') }}" alt="Agente local e automação" /></a>
                <div class="content">
                    <h2 class="major">Infra transversal preservada</h2>
                    <p>
                        A base não trata site e painel como projetos soltos. ACL, auditoria, rotinas e ownership por conta permanecem coerentes entre as
                        interfaces, reduzindo drift técnico conforme o produto cresce.
                    </p>
                    <a href="{{ route('login') }}" class="special">Entrar no sistema</a>
                </div>
            </div>
        </section>

        <section class="wrapper alt style1">
            <div class="inner">
                <h2 class="major">Leitura rápida da base</h2>
                <p class="app-page-copy">
                    O projeto já entrega números simples para validar seed, domínio e operação mínima. Abaixo, a home usa o próprio estado da aplicação
                    para mostrar clientes, produtos, pedidos e uma amostra dos registros mais recentes.
                </p>

                <div class="app-split app-split--stats">
                    <div class="app-order-list">
                        @forelse ($recentOrders as $order)
                            <article class="app-order-item">
                                <div>
                                    <h3 class="major">{{ $order->public_number }}</h3>
                                    <p>{{ $order->customer?->name ?? 'Sem cliente' }} · {{ $order->user?->name ?? 'Sem conta' }}</p>
                                </div>
                                <div class="app-order-meta">
                                    <span class="app-chip">{{ ucfirst($order->status) }}</span>
                                </div>
                            </article>
                        @empty
                            <article class="app-order-item">
                                <div>
                                    <h3 class="major">Sem pedidos</h3>
                                    <p>Nenhum pedido recente foi encontrado na base.</p>
                                </div>
                            </article>
                        @endforelse
                    </div>

                    <div class="app-stat-grid">
                        @foreach ($stats as $label => $value)
                            <article class="app-stat-card">
                                <span class="eyebrow">{{ str_replace('_', ' ', $label) }}</span>
                                <strong>{{ $value }}</strong>
                                <p>Indicador consolidado do framework.</p>
                            </article>
                        @endforeach

                        <article class="app-mini-note">
                            <h3 class="major">Ponto de partida limpo</h3>
                            <p>
                                O framework já nasce com migrações, seed, auth, painéis e documentação operacional suficientes para iniciar a próxima vertical
                                sem remontar o esqueleto do produto.
                            </p>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection
