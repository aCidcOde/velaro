<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name'))</title>
    </head>
    @php
        $emailLogoPath = public_path('logo.webp');
        $emailLogoSrc = file_exists($emailLogoPath)
            ? $message->embed($emailLogoPath)
            : asset('logo.webp');
    @endphp
    <body style="margin: 0; padding: 0; background-color: #f4f6f8;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 24px 0;">
            <tr>
                <td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 600px; background: #ffffff; border-radius: 16px; overflow: hidden;">
                        <tr>
                            <td style="background: #0b0f17; padding: 24px;">
                                <img src="{{ $emailLogoSrc }}" alt="{{ config('app.name') }}" width="320" style="display: block; width: 100%; max-width: 320px; height: auto; border: 0;">
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 28px; font-family: Arial, sans-serif; color: #111827;">
                                @hasSection('title')
                                    <h1 style="margin: 0 0 12px; font-size: 22px; line-height: 1.3;">@yield('title')</h1>
                                @endif
                                @yield('content')
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 18px 28px 28px; font-family: Arial, sans-serif; color: #6b7280; font-size: 12px;">
                                <p style="margin: 0 0 8px;">{{ config('app.name') }} • Template-base para novos projetos.</p>
                                <p style="margin: 0;">{{ config('app.name') }} &copy; {{ date('Y') }}. Todos os direitos reservados.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
