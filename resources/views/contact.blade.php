@extends('layouts.public')

@section('title', 'Contato')
@section('header_class', '')

@section('content')
    <section id="wrapper">
        <header>
            <div class="inner">
                <h2>Contato</h2>
                <p>Fale com a operação do template</p>
            </div>
        </header>

        <div class="wrapper">
            <div class="inner">
                <div class="app-split app-split--stats">
                    <div>
                        <h3 class="major">Canal padrão de contato</h3>
                        <p class="app-page-copy">
                            Use este formulário como base para novos canais comerciais ou operacionais. Ele já está integrado ao fluxo padrão de e-mail do sistema.
                        </p>

                        @if (session('status'))
                            <div class="app-form-status">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('public.contact.store') }}">
                            @csrf
                            <div class="fields">
                                <div class="field">
                                    <label for="name">Nome</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required />
                                    @error('name')<div class="app-form-errors">{{ $message }}</div>@enderror
                                </div>
                                <div class="field">
                                    <label for="email">E-mail</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required />
                                    @error('email')<div class="app-form-errors">{{ $message }}</div>@enderror
                                </div>
                                <div class="field">
                                    <label for="phone">Telefone</label>
                                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required />
                                    @error('phone')<div class="app-form-errors">{{ $message }}</div>@enderror
                                </div>
                                <div class="field">
                                    <label for="message">Mensagem</label>
                                    <textarea name="message" id="message" rows="5" required>{{ old('message') }}</textarea>
                                    @error('message')<div class="app-form-errors">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <ul class="actions">
                                <li><input type="submit" value="Enviar mensagem" /></li>
                            </ul>
                        </form>
                    </div>

                    <div class="app-stat-grid">
                        <article class="app-stat-card">
                            <span class="eyebrow">Fluxo</span>
                            <strong>Mail</strong>
                            <p>O contato segue o pipeline padrão de e-mail do framework.</p>
                        </article>
                        <article class="app-stat-card">
                            <span class="eyebrow">Uso</span>
                            <strong>Base</strong>
                            <p>Este formulário pode ser reutilizado como canal comercial, suporte ou operação.</p>
                        </article>
                        <article class="app-mini-note">
                            <h3 class="major">Pronto para adaptar</h3>
                            <p>O endpoint, a validação e o envio já existem. O próximo produto só precisa refinar o conteúdo e o destino do fluxo.</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
