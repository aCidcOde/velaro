<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased text-[#f5f7fb]" style="background: radial-gradient(circle at 18% 18%, rgba(14,165,233,0.15), transparent 38%), radial-gradient(circle at 82% 8%, rgba(56,189,248,0.12), transparent 42%), linear-gradient(135deg, #0b0e15 0%, #0f141f 55%, #0a0f1c 100%);">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex items-center justify-center">
                        <img src="/logo.webp" alt="{{ config('app.name', 'CodaFácil') }}" class="h-12 w-auto" />
                    </span>

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-white/10 bg-[#0d1118] text-[#f5f7fb] shadow-xs shadow-black/30">
                        <div class="px-10 py-8">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('partials.whatsapp-float')
        @fluxScripts
    </body>
</html>
