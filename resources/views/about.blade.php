@extends('layouts.public')

@section('title', 'Sobre — ' . config('app.name', 'CodaFácil'))
@section('header_class', '')

@section('content')
    <section id="wrapper">
        <header>
            <div class="inner">
                <h2>Sobre a base</h2>
                <p>Uma fundação única para vários produtos.</p>
            </div>
        </header>

        <div class="wrapper">
            <div class="inner">
                <div class="app-split app-split--stats">
                    <div class="app-page-copy">
                        <h3 class="major">Núcleo reutilizável</h3>
                        <p>
                            O objetivo deste repositório é servir como ponto de partida para novos sistemas. A estrutura preserva autenticação, ACL, jobs,
                            rotinas, documentação operacional, deploy e um domínio comercial mínimo para acelerar novos projetos.
                        </p>
                        <p>
                            Em vez de reiniciar o stack a cada produto, a base mantém um conjunto estável de módulos transversais. Assim, o domínio específico
                            entra depois, em camadas isoladas, sem desmontar os blocos de operação já consolidados.
                        </p>

                        <section class="features">
                            <article>
                                <a href="#" class="image"><img src="{{ asset('logo-dark.webp') }}" alt="Escalável" /></a>
                                <h3 class="major">Escalável</h3>
                                <p>Migrações limpas, seed inicial, ACL sincronizável e rotas consistentes.</p>
                            </article>
                            <article>
                                <a href="#" class="image"><img src="{{ asset('logo-light.webp') }}" alt="Extensível" /></a>
                                <h3 class="major">Extensível</h3>
                                <p>Cada domínio novo pode crescer a partir de customers, products e orders.</p>
                            </article>
                        </section>
                    </div>

                    <div class="app-stat-grid">
                        @foreach ($stats as $label => $value)
                            <article class="app-stat-card">
                                <span class="eyebrow">{{ str_replace('_', ' ', $label) }}</span>
                                <strong>{{ $value }}</strong>
                                <p>Contagem atual carregada do domínio base.</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
