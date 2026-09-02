<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased text-[#f5f7fb]" style="background: radial-gradient(circle at 18% 18%, rgba(14,165,233,0.15), transparent 38%), radial-gradient(circle at 82% 8%, rgba(56,189,248,0.12), transparent 42%), linear-gradient(135deg, #0b0e15 0%, #0f141f 55%, #0a0f1c 100%);">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800" style="background: radial-gradient(circle at 25% 20%, rgba(14,165,233,0.15), transparent 40%), #0c1018;">
                <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.7));"></div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="flex items-center">
                        <img src="/logo.webp" alt="{{ config('app.name', 'CodaFácil') }}" class="h-10 w-auto" />
                    </span>
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <flux:heading size="lg">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading>{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>
                <div class="w-full lg:p-8">
                    <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                        <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                            <span class="flex items-center justify-center">
                                <img src="/logo.webp" alt="{{ config('app.name', 'CodaFácil') }}" class="h-12 w-auto" />
                            </span>

                            <span class="sr-only">{{ config('app.name', 'CodaFácil') }}</span>
                        </a>
                        {{ $slot }}
                    </div>
            </div>
        </div>
        @include('partials.whatsapp-float')
        @fluxScripts
    </body>
</html>
