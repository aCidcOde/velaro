<x-layouts.auth>
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            {{ __('Confirme seu endereço de e-mail clicando no link que acabamos de enviar para você.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
                {{ __('Um novo link de verificação foi enviado para o e-mail informado no cadastro.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center gap-3 w-full">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Reenviar e-mail de verificação') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full text-sm text-center cursor-pointer py-2 text-zinc-400 hover:text-zinc-200 transition-colors" data-test="logout-button">
                    {{ __('Sair') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
