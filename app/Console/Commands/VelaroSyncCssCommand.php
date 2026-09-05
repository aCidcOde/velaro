<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Copia o design system de docs/mockups para resources/css/velaro.
 *
 * Os mockups são a fonte da verdade do design: mudou lá, roda isto e faz o
 * build. A vitrine é extraída do SHOP_CSS de _build_novas_site.py sem a moldura
 * de tablet, que só existe na apresentação.
 */
class VelaroSyncCssCommand extends Command
{
    protected $signature = 'velaro:sync-css';

    protected $description = 'Sincroniza o CSS do design system Velaro a partir de docs/mockups';

    public function handle(): int
    {
        $origem = base_path('docs/mockups');
        $destino = resource_path('css/velaro');

        foreach (['velaro-tokens.css', 'velaro-ui.css', 'velaro-screens.css'] as $arquivo) {
            File::copy("{$origem}/{$arquivo}", "{$destino}/{$arquivo}");
            $this->line("  ✓ {$arquivo}");
        }

        $builder = File::get("{$origem}/_build_novas_site.py");
        if (preg_match('/SHOP_CSS = """(.*?)"""/s', $builder, $m) === 1) {
            $css = $m[1];
            $css = preg_replace('/\n\s*\/\* Moldura de tablet[^\n]*/', "\n", $css);
            $css = preg_replace('/\n\s*\.tablet[^{]*\{[^}]*\}/', '', $css);
            $css = preg_replace('/\n\s*\.vitlegend[^{]*\{[^}]*\}/', '', $css);
            $css = preg_replace('/\n\s*body \{[^}]*\}/', '', $css);
            $cabecalho = "/* Vitrine white label. Pintada SO por --shop-*, que vem de reseller_stores.\n"
                ."   Espelho do SHOP_CSS de docs/mockups/_build_novas_site.py, sem a moldura de\n"
                ."   tablet que so existe na apresentacao. Gerado por velaro:sync-css. */\n";
            File::put("{$destino}/vitrine.css", $cabecalho.$css);
            $this->line('  ✓ vitrine.css (extraído do SHOP_CSS)');
        } else {
            $this->warn('SHOP_CSS não encontrado em _build_novas_site.py — vitrine.css mantido.');
        }

        $this->info('Design system sincronizado. Rode `npm run build`.');

        return self::SUCCESS;
    }
}
