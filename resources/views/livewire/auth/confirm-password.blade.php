<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirmar senha')"
            :description="__('Esta é uma área segura do sistema. Confirme sua senha para continuar.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('Senha')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Informe sua senha atual')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Confirmar') }}
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
