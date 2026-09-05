<x-layouts.auth :title="__('Login') . ' | ' . config('app.name')">
    <div class="space-y-8">
        <a href="{{ route('site.home') }}" class="panel-auth-back">
            <i class="ti ti-arrow-left text-base"></i>
            <span>{{ __('Voltar para o site') }}</span>
        </a>

        <div class="space-y-4">
            <span class="panel-auth-eyebrow">Acesso seguro</span>
            <div>
                <h1 class="panel-auth-title">{{ __('Acesse sua conta') }}</h1>
                <p class="panel-auth-copy">{{ __('Informe seu e-mail e senha para entrar.') }}</p>
            </div>
        </div>

        <x-auth-session-status class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-300" :status="session('status')" />

        <div class="panel-auth-social-grid">
            <a
                href="{{ route('auth.google.redirect') }}"
                class="panel-auth-social-btn"
            >
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
                    <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-.8 2.3-1.7 3.1l2.8 2.2c1.6-1.5 2.6-3.8 2.6-6.5 0-.6-.1-1.1-.2-1.7H12z"/>
                    <path fill="#34A853" d="M12 22c2.6 0 4.8-.9 6.4-2.5l-2.8-2.2c-.8.5-1.8.9-3.6.9-2.8 0-5.1-1.9-6-4.4l-2.9 2.2C4.8 19.6 8.1 22 12 22z"/>
                    <path fill="#4A90E2" d="M6 13.8c-.2-.5-.3-1.1-.3-1.8s.1-1.2.3-1.8L3.1 8C2.4 9.4 2 10.7 2 12s.4 2.6 1.1 4l2.9-2.2z"/>
                    <path fill="#FBBC05" d="M12 5.8c1.4 0 2.7.5 3.7 1.5l2.7-2.7C16.8 3 14.6 2 12 2 8.1 2 4.8 4.4 3.1 8L6 10.2c.9-2.5 3.2-4.4 6-4.4z"/>
                </svg>
                {{ __('Entrar com Google') }}
            </a>

            <span class="panel-auth-social-btn text-gray-400 dark:text-gray-500">
                {{ __('Acesso por e-mail') }}
            </span>
        </div>

        <div class="panel-auth-divider">
            <span>{{ __('ou') }}</span>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <div class="panel-auth-field">
                <label for="email">{{ __('E-mail') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    autofocus
                    placeholder="email@example.com"
                    class="panel-auth-input"
                />
                @error('email')
                    <span class="text-sm text-red-600 dark:text-red-300">{{ $message }}</span>
                @enderror
            </div>

            <div class="panel-auth-field">
                <div class="flex items-center justify-between gap-3">
                    <label for="password">{{ __('Senha') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="panel-auth-link" wire:navigate>{{ __('Esqueceu sua senha?') }}</a>
                    @endif
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    placeholder="{{ __('Sua senha') }}"
                    class="panel-auth-input"
                />
                @error('password')
                    <span class="text-sm text-red-600 dark:text-red-300">{{ $message }}</span>
                @enderror
            </div>

            <label class="inline-flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/25 dark:border-gray-700 dark:bg-gray-900">
                <span>{{ __('Lembrar de mim') }}</span>
            </label>

            <button
                type="submit"
                class="panel-btn panel-btn--primary w-full"
                data-test="login-button"
            >
                {{ __('Entrar') }}
            </button>
        </form>

        @if (Route::has('register'))
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Ainda não tem uma conta?') }}
                <a href="{{ route('register') }}" class="panel-auth-link" wire:navigate>{{ __('Criar conta') }}</a>
            </p>
        @endif
    </div>
</x-layouts.auth>
