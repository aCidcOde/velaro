{{-- Documento base de todo ambiente Velaro. O design system entra por um único
     bundle (resources/css/velaro.css) e as fontes já são auto-hospedadas. --}}
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Velaro') }}</title>
@include('partials.favicons')
@vite(['resources/css/velaro.css', 'resources/js/velaro.js'])
{{ $head ?? '' }}
</head>
<body @isset($bodyClass) class="{{ $bodyClass }}" @endisset>
{{ $slot }}
</body>
</html>
