{{-- Confirmacao de e-mail (linguagem da tela 21): cartao sobre o layout escuro de
     auth. As duas acoes continuam sendo POST do Fortify — reenviar o link e sair. --}}
@php
    // Mesma fonte que o Laravel usa para assinar a URL de verificacao, para a tela
    // nao prometer um prazo diferente do que o link realmente tem.
    $validadeLink = (int) config('auth.verification.expire', 60);
@endphp
<x-velaro.layouts.auth :title="__('Confirme seu e-mail')">
    <div class="card" style="width:100%;max-width:420px">
        <div class="row" style="gap:var(--space-4);align-items:flex-start">
            <span class="bigcheck"><x-velaro.icon name="mail" /></span>
            <div>
                <h2 class="title">{{ __('Confirme seu e-mail') }}</h2>
                <p class="lede" style="margin-top:6px">
                    {{ __('Confirme seu endereço de e-mail clicando no link que acabamos de enviar para você.') }}</p>
            </div>
        </div>

        @if (session('status') == 'verification-link-sent')
            <p class="notice notice--ok" role="status"><x-velaro.icon name="check" />
                <span>{{ __('Um novo link de verificação foi enviado para o e-mail informado no cadastro.') }}</span></p>
        @endif

        <div style="margin-top:var(--space-5)">
            <div class="datarow"><span class="datarow__k"><x-velaro.icon name="mail" /> Canal</span>
                <span class="datarow__v">{{ auth()->user()?->email ?? 'E-mail do cadastro' }}</span></div>
            <div class="datarow"><span class="datarow__k"><x-velaro.icon name="clock" /> Validade do link</span>
                <span class="datarow__v">{{ $validadeLink }} minutos</span></div>
            <div class="datarow"><span class="datarow__k"><x-velaro.icon name="shield" /> Uso</span>
                <span class="datarow__v">Uma única vez</span></div>
        </div>

        <h3 class="fsec">Não recebeu?</h3>
        <ul class="cklist">
            <li class="ck--ok"><x-velaro.icon name="check" /><span>Confira a caixa de spam e a aba de promoções.</span></li>
            <li class="ck--ok"><x-velaro.icon name="check" /><span>Confirme se o e-mail é o mesmo do seu cadastro.</span></li>
        </ul>

        <form method="POST" action="{{ route('verification.send') }}" style="margin-top:var(--space-5)">
            @csrf
            <button type="submit" class="btn btn--primary" style="width:100%">
                <x-velaro.icon name="refresh" /> {{ __('Reenviar e-mail de verificação') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:var(--space-3)">
            @csrf
            <button type="submit" class="btn btn--secondary" style="width:100%" data-test="logout-button">
                {{ __('Sair') }}</button>
        </form>

        <p class="notice notice--info"><x-velaro.icon name="info" />
            <span>Enquanto o e-mail não for confirmado, o acesso ao ambiente fica retido nesta etapa.</span></p>
    </div>
</x-velaro.layouts.auth>
