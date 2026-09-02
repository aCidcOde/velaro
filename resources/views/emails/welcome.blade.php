@extends('emails.layouts.base')

@section('title', 'Bem-vindo(a) ao ' . config('app.name', 'CodaFácil'))

@section('content')
    <p style="margin: 0 0 12px;">Olá, {{ $user->name }}!</p>
    <p style="margin: 0 0 16px;">
        Seu cadastro foi concluído com sucesso. A partir de agora, você pode acompanhar clientes, produtos e pedidos pela área autenticada.
    </p>
    <p style="margin: 0 0 24px;">
        <a href="{{ route('dashboard') }}" style="display: inline-block; background: #0ea5e9; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 999px; font-weight: 600;">
            Acessar minha conta
        </a>
    </p>
    <p style="margin: 0;">
        Use este fluxo como base para customizar a comunicação do próximo projeto.
    </p>
@endsection
