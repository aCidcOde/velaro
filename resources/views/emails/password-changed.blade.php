@extends('emails.layouts.base')

@section('title', 'Senha alterada com sucesso')

@section('content')
    <p style="margin: 0 0 12px;">Olá, {{ $user->name }}!</p>
    <p style="margin: 0 0 12px;">
        Sua senha foi alterada em {{ $changedAt->format('d/m/Y \\à\\s H:i') }}.
    </p>
    <p style="margin: 0 0 24px;">
        Se você não reconhece essa alteração, fale com nossa equipe imediatamente.
    </p>
    <p style="margin: 0;">
        <a href="{{ route('user-password.edit') }}" style="display: inline-block; background: #0e5b58; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 999px; font-weight: 600;">
            Revisar configurações
        </a>
    </p>
@endsection
