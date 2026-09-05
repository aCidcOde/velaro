<?php

namespace App\Providers;

use App\Services\Site\SiteContentService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // `scoped` para o cache por requisição do serviço valer de fato: o casco
        // do site, a tela 1.2 e os documentos legais leem os mesmos grupos de
        // `settings` no mesmo request.
        $this->app->scoped(SiteContentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('agent', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(30)->by($key);
        });

        // Rodapé do site: telefone, e-mail, horário e razão social saem de
        // `settings` (escopo 1.1 §1). O casco é compartilhado pelas 13 telas do
        // site, então o dado entra por composer e não por cada controller.
        View::composer('components.velaro.layouts.site', function (ViewInstance $view): void {
            $conteudo = $this->app->make(SiteContentService::class);

            $view->with([
                'rodapeContato' => $conteudo->contact(),
                'rodapeEmpresa' => $conteudo->company(),
            ]);
        });
    }
}
