<?php

namespace Tests\Feature;

use Tests\TestCase;

class VelaroMockupDesignSystemTest extends TestCase
{
    public function test_all_environment_mockups_share_the_velaro_design_system(): void
    {
        foreach ($this->environmentFiles() as $filename) {
            $contents = $this->mockupContents($filename);

            $this->assertStringContainsString('lang="pt-BR"', $contents);
            $this->assertStringContainsString('name="viewport"', $contents);
            $this->assertStringContainsString('href="velaro-tokens.css"', $contents);
            $this->assertStringContainsString('href="velaro-ui.css"', $contents);
            $this->assertStringContainsString('class="skip-link"', $contents);
            $this->assertStringContainsString('id="conteudo"', $contents);
        }
    }

    public function test_operational_environments_keep_navigation_available_on_mobile(): void
    {
        foreach (['01-site-publico.html', '02-portal-lojista.html', '04-painel-master.html'] as $filename) {
            $contents = $this->mockupContents($filename);

            $this->assertStringContainsString('mobile-navigation', $contents);
            $this->assertStringContainsString('aria-label="Abrir navegação"', $contents);
            $this->assertStringContainsString('aria-label="Navegação principal"', $contents);
        }
    }

    public function test_dense_tables_are_keyboard_accessible_and_horizontally_contained(): void
    {
        $sharedCss = $this->mockupContents('velaro-ui.css');

        $this->assertStringContainsString('.table-scroll { overflow-x: auto;', $sharedCss);

        foreach (['02-portal-lojista.html', '04-painel-master.html'] as $filename) {
            $contents = $this->mockupContents($filename);

            $this->assertMatchesRegularExpression('/class="table-scroll" tabindex="0" aria-label="[^"]+"/', $contents);
        }
    }

    public function test_white_label_store_does_not_consume_velaro_brand_tokens(): void
    {
        $contents = $this->mockupContents('03-vitrine-pdv.html');

        $this->assertStringNotContainsString('var(--color-brand-', $contents);
        $this->assertStringContainsString('--shop-primary:', $contents);
        $this->assertStringContainsString('@media (max-width: 640px)', $contents);
    }

    public function test_shared_components_cover_focus_and_reduced_motion_preferences(): void
    {
        $contents = $this->mockupContents('velaro-ui.css');

        $this->assertStringContainsString(':focus-visible', $contents);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $contents);
    }

    public function test_input_map_is_a_visual_design_system_reference(): void
    {
        $contents = $this->mockupContents('06-mapa-inputs.html');
        $notes = $this->mockupContents('mapa-de-inputs.md');

        $this->assertStringContainsString('lang="pt-BR"', $contents);
        $this->assertStringContainsString('href="velaro-tokens.css"', $contents);
        $this->assertStringContainsString('href="velaro-ui.css"', $contents);
        $this->assertStringContainsString('Mapa visual de inputs', $contents);
        $this->assertStringContainsString('id="estados"', $contents);
        $this->assertStringContainsString('id="composicao"', $contents);
        $this->assertStringContainsString('id="escuro"', $contents);
        $this->assertStringContainsString('Escopo desta etapa: somente aparência', $notes);
        $this->assertStringNotContainsString('Entidade lógica', $notes);
        $this->assertStringNotContainsString('Obrigatório', $notes);
    }

    /** @return list<string> */
    private function environmentFiles(): array
    {
        return [
            '01-site-publico.html',
            '02-portal-lojista.html',
            '03-vitrine-pdv.html',
            '04-painel-master.html',
        ];
    }

    private function mockupContents(string $filename): string
    {
        $contents = file_get_contents(base_path("docs/mockups/{$filename}"));

        if ($contents === false) {
            self::fail("Não foi possível ler o mockup {$filename}.");
        }

        return $contents;
    }
}
