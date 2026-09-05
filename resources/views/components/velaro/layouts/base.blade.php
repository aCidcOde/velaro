{{-- Documento base de todo ambiente Velaro. O design system entra por um único
     bundle (resources/css/velaro.css) e as fontes já são auto-hospedadas.

     `marca` e `favicons` existem por causa da vitrine white label: ela é o único
     ambiente em que a marca do documento NÃO é a Velaro. Quem não passar nada
     continua vendo exatamente o que via — o nome da aplicação no <title> e o
     ícone da Velaro na aba. --}}
{{-- `title` e `bodyClass` já eram usados antes de existir `@props` aqui, e
     precisam continuar declarados: um atributo que fica de fora da lista é
     removido das variáveis pelo próprio `@props`, e o <title> voltaria a ser só
     o nome da marca em todas as telas. --}}
@props(['title' => null, 'bodyClass' => null, 'marca' => null, 'favicons' => true])
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · ' : '' }}{{ $marca ?? config('app.name', 'Velaro') }}</title>
@if($favicons)
@include('partials.favicons')
@endif
@vite(['resources/css/velaro.css', 'resources/js/velaro.js'])
{{ $head ?? '' }}
</head>
<body @if($bodyClass) class="{{ $bodyClass }}" @endif>
{{ $slot }}
</body>
</html>
