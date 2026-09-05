{{--
[Modulo: resources/views/emails]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Corpo do aviso de pre-cadastro: protocolo, o que falta na analise, o acesso ja liberado e o pedido de documentos.
--}}
@extends('emails.layouts.base')

@section('title', 'Recebemos seu cadastro')

@section('content')
    <p style="margin: 0 0 12px;">Olá, {{ $contactName }}!</p>

    <p style="margin: 0 0 16px;">
        Recebemos o cadastro da <strong>{{ $reseller->trade_name ?? $reseller->legal_name }}</strong> e a
        sua solicitação já está em análise pela equipe {{ config('app.name') }}.
    </p>

    {{-- O protocolo é o número que o lojista usa para falar com a equipe (Anexo I §3.5). --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 20px;">
        <tr>
            <td style="background: #f3f2ef; border-radius: 12px; padding: 14px 18px; font-family: Arial, sans-serif;">
                <span style="display: block; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: #726f6c;">Protocolo</span>
                <strong style="display: block; font-size: 20px; color: #171817;">{{ $protocol }}</strong>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px;"><strong>O que acontece agora</strong></p>
    <p style="margin: 0 0 16px;">
        Nossa equipe ainda vai conferir as informações e os documentos enviados: primeiro a validação
        automática de CNPJ e CNAE, depois a aprovação final {{ config('app.name') }} e, por fim, a
        liberação do acesso completo à plataforma. Assim que houver novidades, entraremos em contato —
        você será avisado por e-mail e pelo WhatsApp cadastrado a cada atualização.
    </p>

    <p style="margin: 0 0 8px;"><strong>Você já pode entrar</strong></p>
    <p style="margin: 0 0 16px;">
        Use o e-mail <strong>{{ $email }}</strong> e a senha que você acabou de escolher no cadastro.
        Com esse mesmo login você acompanha o andamento da solicitação pelo seu painel, a qualquer
        momento — não é preciso esperar a aprovação para entrar.
    </p>

    <p style="margin: 0 0 20px;">
        <a href="{{ $loginUrl }}" style="display: inline-block; background: #0e5b58; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 999px; font-weight: 600;">
            Entrar na plataforma
        </a>
    </p>

    <p style="margin: 0 0 16px;">
        Se preferir ir direto ao andamento do cadastro, o link é
        <a href="{{ $statusUrl }}" style="color: #0e5b58;">{{ $statusUrl }}</a>.
    </p>

    {{-- Regra 4 da tela 1.6: em `awaiting_info` a tela abre o reenvio de documentos.
         Avisar aqui evita que o pedido de documento chegue como surpresa. --}}
    <p style="margin: 0 0 8px;"><strong>Podemos pedir mais alguma coisa</strong></p>
    <p style="margin: 0 0 16px;">
        Se faltar algum documento ou alguma informação da empresa, a equipe registra o pedido e você
        recebe um aviso. O reenvio é feito na própria tela de acompanhamento, e a solicitação volta
        para análise assim que os arquivos chegarem.
    </p>

    <p style="margin: 0;">
        Guarde seu e-mail e sua senha: eles são o seu acesso para acompanhar o cadastro agora e para
        entrar na plataforma quando ele for aprovado.
    </p>
@endsection
