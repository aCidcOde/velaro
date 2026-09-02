<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

@include('partials.favicons')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Inter+Tight:wght@300;400;500;600;700&display=swap" rel="stylesheet">

@vite(['resources/css/panel.css', 'resources/js/app.js'])
@include('partials.theme-init')
@fluxAppearance
