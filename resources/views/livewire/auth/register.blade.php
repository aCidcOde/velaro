<x-layouts.auth :title="__('Criar conta') . ' | ' . config('app.name')">
    <div class="flex flex-col gap-6">

        <x-auth-header :title="__('Crie sua conta')" :description="__('Preencha seus dados para começar a usar o sistema')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Nome completo')"
                type="text"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Como devemos te chamar?')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('E-mail')"
                type="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                name="phone"
                :label="__('Telefone')"
                type="tel"
                value="{{ old('phone') }}"
                required
                autocomplete="tel"
                inputmode="tel"
                placeholder="(11) 99999-0000"
                data-mask="phone"
            />

            <flux:input
                name="document"
                :label="__('Documento')"
                type="text"
                value="{{ old('document') }}"
                required
                inputmode="text"
                placeholder="DOC-123456"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Senha')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Crie uma senha segura')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirme a senha')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Repita a senha escolhida')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Criar conta') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-white">
            <span>{{ __('Já tem uma conta?') }}</span>
            <flux:link :href="route('login')" wire:navigate class="font-semibold text-accent">{{ __('Acessar o sistema') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
