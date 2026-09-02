<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Redefinir senha')" :description="__('Informe sua nova senha abaixo para continuar')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                :label="__('E-mail')"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Nova senha')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Crie uma nova senha segura')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirme a nova senha')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Repita a nova senha')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    {{ __('Redefinir senha') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts.auth>
