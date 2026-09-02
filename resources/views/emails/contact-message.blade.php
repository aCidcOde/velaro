@extends('emails.layouts.base')

@section('title', 'Novo contato do site')

@section('content')
    <p style="margin: 0 0 8px;"><strong>Nome:</strong> {{ $name }}</p>
    <p style="margin: 0 0 8px;"><strong>E-mail:</strong> {{ $email }}</p>
    <p style="margin: 0 0 16px;"><strong>Telefone:</strong> {{ $phone }}</p>
    <div style="margin: 0;">
        <p style="margin: 0 0 8px;"><strong>Mensagem:</strong></p>
        <p style="margin: 0; white-space: pre-line;">{{ $content }}</p>
    </div>
@endsection
