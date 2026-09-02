@extends('layouts.public')

@section('title', 'Módulos — ' . config('app.name', 'CodaFácil'))
@section('header_class', '')

@section('content')
    <section id="wrapper">
        <header>
            <div class="inner">
                <h2>/servicos</h2>
                <p>Módulos prontos para reaproveitar</p>
            </div>
        </header>

        <div class="wrapper">
            <div class="inner">
                <h3 class="major">Mapa do framework</h3>
                <p class="app-page-copy">
                    A landing foi convertida para uma vitrine técnica do template. Cada bloco abaixo representa uma fatia já preparada para ser clonada em
                    novos sistemas, sem depender de documentação externa do tema.
                </p>

                <section class="features">
                    @foreach ($modules as $module)
                        <article>
                            <a href="#" class="image"><img src="{{ asset('logo-light.webp') }}" alt="{{ $module['title'] }}" /></a>
                            <h3 class="major">{{ $module['title'] }}</h3>
                            <p>{{ $module['description'] }}</p>
                        </article>
                    @endforeach
                </section>
            </div>
        </div>
    </section>
@endsection
