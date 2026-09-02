<?php

/*
[Modulo: app/Http/Controllers/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-02-22
Exibe as versoes do changelog dentro do backend administrativo.
*/

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ChangelogController extends Controller
{
    public function index(): View
    {
        return view('backend.changelog.index', [
            'versions' => array_values($this->changelogVersions()),
        ]);
    }

    public function show(string $version): View
    {
        $versions = $this->changelogVersions();
        $selectedVersion = $versions[$version] ?? null;

        if ($selectedVersion === null) {
            abort(404);
        }

        return view('backend.changelog.show', [
            'versions' => array_values($versions),
            'currentVersion' => $version,
            'changelog' => $selectedVersion,
            'changelogHtml' => $this->renderChangelogHtml($selectedVersion['file']),
        ]);
    }

    /**
     * @return array<string, array{version: string, title: string, summary: string, file: string, highlights: array<int, string>, is_latest: bool}>
     */
    private function changelogVersions(): array
    {
        $versions = [
            '1.0' => [
                'version' => '1.0',
                'title' => 'CodaFácil Scaffold',
                'summary' => 'Primeira baseline reutilizável com autenticação, ACL, domínio mínimo e rotina assíncrona de exemplo.',
                'file' => 'changelog_1_0.md',
                'highlights' => [
                    'Schema enxuto com customers, products, orders e order_items.',
                    'Site público, front autenticado, admin e API mobile mínima.',
                    'Agente local com fila, uploads e rotina agendada reutilizável.',
                ],
            ],
        ];

        uksort($versions, static fn (string $left, string $right): int => version_compare($right, $left));

        $latestVersion = array_key_first($versions);

        foreach ($versions as $version => $metadata) {
            $versions[$version]['is_latest'] = $version === $latestVersion;
        }

        return $versions;
    }

    private function renderChangelogHtml(string $fileName): HtmlString
    {
        $markdown = $this->normalizeMarkdownForDisplay($this->loadChangelogMarkdown($fileName));
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return new HtmlString($html);
    }

    private function normalizeMarkdownForDisplay(string $markdown): string
    {
        $markdownWithoutStatus = preg_replace('/^\*\*Status:\*\*.*$\n?/mi', '', $markdown);
        $normalizedBase = trim((string) ($markdownWithoutStatus ?? $markdown));

        if (! $this->isIncrementalChangelog($normalizedBase)) {
            return $normalizedBase;
        }

        [$contentBeforeClosing, $closingSection] = array_pad(
            explode("\n## Fechamento Tecnico", $normalizedBase, 2),
            2,
            null,
        );
        [$headerSection, $entriesSection] = array_pad(
            explode("\n## Entradas", $contentBeforeClosing, 2),
            2,
            null,
        );

        if ($entriesSection === null) {
            return $normalizedBase;
        }

        $entries = preg_split('/\n### /', trim($entriesSection));

        if ($entries === false) {
            return $normalizedBase;
        }

        $entries = array_values(array_filter($entries, static fn (string $entry): bool => trim($entry) !== ''));

        if ($entries === []) {
            return $normalizedBase;
        }

        $normalizedMarkdown = trim((string) $headerSection)
            ."\n\n## Entradas\n\n### "
            .implode("\n\n---\n\n### ", $entries);

        if ($closingSection !== null) {
            $normalizedMarkdown .= "\n\n## Fechamento Tecnico".$closingSection;
        }

        return $normalizedMarkdown;
    }

    private function isIncrementalChangelog(string $markdown): bool
    {
        return preg_match('/^## Entradas$/m', $markdown) === 1
            && preg_match('/^### \d{4}-\d{2}-\d{2} /m', $markdown) === 1;
    }

    private function loadChangelogMarkdown(string $fileName): string
    {
        $path = base_path('docs/'.$fileName);

        if (! File::exists($path)) {
            abort(404);
        }

        $content = File::get($path);
        $cleanContent = preg_replace('/\A\s*\/\*[\s\S]*?\*\/\s*/', '', $content);

        return trim((string) ($cleanContent ?? $content));
    }
}
