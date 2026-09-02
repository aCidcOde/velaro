<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $theme = 'dark';

    public function mount(): void
    {
        $this->theme = Auth::user()->theme_preference ?? 'dark';
    }

    public function saveTheme(): void
    {
        $user = Auth::user();
        $user->forceFill([
            'theme_preference' => $this->theme,
        ])->save();

        session()->flash('theme-saved', true);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Aparência')" :subheading=" __('Escolha como você prefere visualizar a aplicação.')">
        <div class="flex flex-wrap gap-3">
            <button type="button" class="panel-btn panel-btn--secondary"
                onclick="toggleTheme('light'); @this.set('theme', 'light'); @this.saveTheme();">
                <i class="ti ti-sun"></i>
                <span>{{ __('Modo Claro') }}</span>
            </button>
            <button type="button" class="panel-btn panel-btn--secondary"
                onclick="toggleTheme('dark'); @this.set('theme', 'dark'); @this.saveTheme();">
                <i class="ti ti-moon"></i>
                <span>{{ __('Modo Escuro') }}</span>
            </button>
        </div>

        @if (session()->has('theme-saved'))
            <div class="panel-flash panel-flash--success mt-4">
                {{ __('Preferências de aparência atualizadas com sucesso!') }}
            </div>
        @endif
    </x-settings.layout>
</section>
