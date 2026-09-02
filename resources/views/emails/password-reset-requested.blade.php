@extends('emails.layouts.base')

@section('title', 'Recuperação de senha')

@section('content')
    <p style="margin: 0 0 12px;">Olá, {{ $user->name ?? 'Tudo bem' }}!</p>
    <p style="margin: 0 0 16px;">
        Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para continuar.
    </p>
    <p style="margin: 0 0 24px;">
        <a href="{{ $resetUrl }}" style="display: inline-block; background: #a97c3c; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 999px; font-weight: 600;">
            Redefinir senha
        </a>
    </p>
    <p style="margin: 0 0 12px;">
        Este link expira em {{ $expires }} minutos.
    </p>
    <p style="margin: 0;">
        Se você não solicitou a redefinição, ignore este email.
    </p>
@endsection
