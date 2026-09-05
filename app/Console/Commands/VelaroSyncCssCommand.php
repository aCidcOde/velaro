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
            $css = $this->escoparNaVitrine((string) $css);
            $cabecalho = "/* Vitrine white label. Pintada SO por --shop-*, que vem de reseller_stores.\n"
                ."   Espelho do SHOP_CSS de docs/mockups/_build_novas_site.py, sem a moldura de\n"
                ."   tablet que so existe na apresentacao. Todo seletor nasce sob `.shop`:\n"
                ."   este arquivo entra depois de velaro-screens.css no bundle e, sem escopo,\n"
                ."   .prod/.prods/.radio da vitrine sobrescreveriam os do site e do painel —\n"
                ."   com --shop-* indefinido fora da loja, o cartao perde fundo e borda.\n"
                ."   Gerado por velaro:sync-css. */\n";
            File::put("{$destino}/vitrine.css", $cabecalho.$css);
            $this->line('  ✓ vitrine.css (extraído do SHOP_CSS, escopado em .shop)');
        } else {
            $this->warn('SHOP_CSS não encontrado em _build_novas_site.py — vitrine.css mantido.');
        }

        $this->info('Design system sincronizado. Rode `npm run build`.');

        return self::SUCCESS;
    }

    /**
     * Prefixa com `.shop ` todo seletor que ainda não comece por `.shop`.
     *
     * O SHOP_CSS foi escrito para páginas de mockup que só continham a loja, e
     * por isso nomeia classes genéricas (`.prod`, `.prods`, `.radio`) que o site
     * e o painel também usam. Como `vitrine.css` é o último `@import` do bundle,
     * sem escopo ele ganha a cascata em todas as telas — e, fora de `.shop`, as
     * variáveis `--shop-*` não existem, então `background`/`border` caem para o
     * valor inicial e o cartão fica sem fundo nem borda.
     */
    private function escoparNaVitrine(string $css): string
    {
        // Comentários saem de circulação: vírgula dentro deles não é separador de seletor.
        $comentarios = [];
        $css = (string) preg_replace_callback(
            '/\/\*.*?\*\//s',
            function (array $m) use (&$comentarios): string {
                $comentarios[] = $m[0];

                return "\0C".(count($comentarios) - 1)."\0";
            },
            $css,
        );

        $saida = '';
        $buffer = '';

        for ($i = 0, $n = strlen($css); $i < $n; $i++) {
            $caractere = $css[$i];

            if ($caractere === '{') {
                // Quebras de linha e comentários antes do seletor ficam de fora.
                preg_match('/^(?:\s|\0C\d+\0)*/', $buffer, $m);
                $antes = $m[0];
                $seletor = trim(substr($buffer, strlen($antes)));

                if ($seletor !== '' && ! str_starts_with($seletor, '@')) {
                    $seletor = implode(', ', array_map(
                        static fn (string $parte): string => ($parte = trim($parte)) === '' || str_starts_with($parte, '.shop')
                            ? $parte
                            : '.shop '.$parte,
                        explode(',', $seletor),
                    ));
                }

                $saida .= $antes.$seletor.'{';
                $buffer = '';

                continue;
            }

            if ($caractere === '}') {
                $saida .= $buffer.'}';
                $buffer = '';

                continue;
            }

            $buffer .= $caractere;
        }

        $saida .= $buffer;

        return (string) preg_replace_callback(
            '/\0C(\d+)\0/',
            static fn (array $m): string => $comentarios[(int) $m[1]],
            $saida,
        );
    }
}
