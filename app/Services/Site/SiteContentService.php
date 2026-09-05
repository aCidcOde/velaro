<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Conteudo institucional do site publico: settings publicos por grupo e colecoes ativas da vitrine.
*/

namespace App\Services\Site;

use App\Models\ProductCollection;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SiteContentService
{
    /**
     * Grupos de `settings` que o site publico le. Nenhum deles carrega preco:
     * `products.price` e custo B2B e nao sai de dentro do Portal ou do Master.
     */
    public const GROUP_COMPANY = 'company';

    public const GROUP_CONTACT = 'contact';

    public const GROUP_ABOUT = 'about';

    /**
     * Cache por requisicao: a home le `company` e `contact`, o rodape do casco
     * repete os mesmos valores e a 1.2 ainda soma `about`.
     *
     * @var array<string, array<string, string>>
     */
    private array $grupos = [];

    /**
     * Parametros publicos de um grupo, com a chave ja sem o prefixo do grupo:
     * `contact.telefone` chega na view como `telefone`.
     *
     * @return array<string, string>
     */
    public function group(string $group): array
    {
        if (array_key_exists($group, $this->grupos)) {
            return $this->grupos[$group];
        }

        /** @var array<string, mixed> $linhas */
        $linhas = Setting::query()
            ->where('group', $group)
            ->where('is_public', true)
            ->pluck('value', 'key')
            ->all();

        $valores = [];
        $prefixo = $group.'.';

        foreach ($linhas as $chave => $valor) {
            $chave = str_starts_with($chave, $prefixo)
                ? substr($chave, strlen($prefixo))
                : $chave;

            $valores[$chave] = is_string($valor) ? $valor : '';
        }

        return $this->grupos[$group] = $valores;
    }

    /**
     * Razao social, CNPJ e sede — o texto publico da propria Velaro sobre quem
     * controla os dados (usado pelos documentos legais).
     *
     * @return array<string, string>
     */
    public function company(): array
    {
        return $this->group(self::GROUP_COMPANY);
    }

    /**
     * Telefone, e-mail e horario de atendimento.
     *
     * @return array<string, string>
     */
    public function contact(): array
    {
        return $this->group(self::GROUP_CONTACT);
    }

    /**
     * Texto institucional da tela 1.2, com as duas listas ja decodificadas.
     *
     * @return array{
     *     texto: array<string, string>,
     *     apresentacao: list<string>,
     *     historia: list<string>,
     *     diferenciais: list<array{titulo: string, texto: string}>,
     *     numeros: list<array{titulo: string, texto: string}>
     * }
     */
    public function about(): array
    {
        $sobre = $this->group(self::GROUP_ABOUT);

        return [
            'texto' => $sobre,
            // A seção 5 do escopo 1.2 pede dois parágrafos no hero; o texto vem
            // de um parâmetro só, então quem edita separa por linha em branco.
            'apresentacao' => $this->paragraphs($sobre['hero_texto'] ?? ''),
            'historia' => $this->paragraphs($sobre['historia'] ?? ''),
            'diferenciais' => $this->decodeList($sobre['diferenciais'] ?? ''),
            'numeros' => $this->decodeList($sobre['numeros'] ?? ''),
        ];
    }

    /**
     * As colecoes que a vitrine da home exibe, na ordem definida no cadastro.
     *
     * @return Collection<int, ProductCollection>
     */
    public function activeCollections(): Collection
    {
        return ProductCollection::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Texto longo gravado com linha em branco entre paragrafos vira uma lista
     * de paragrafos — a view nao precisa saber do separador.
     *
     * @return list<string>
     */
    public function paragraphs(string $texto): array
    {
        $partes = preg_split('/\R{2,}/', trim($texto)) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $parte): string => trim($parte), $partes),
            static fn (string $parte): bool => $parte !== '',
        ));
    }

    /**
     * Lista de blocos titulo/texto gravada como JSON em `settings`.
     * Item malformado e descartado: a tela institucional nao pode quebrar por
     * causa de um registro editado a mao no Painel Master.
     *
     * @return list<array{titulo: string, texto: string}>
     */
    private function decodeList(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decodificado = json_decode($json, true);

        if (! is_array($decodificado)) {
            return [];
        }

        $itens = [];

        foreach ($decodificado as $item) {
            if (! is_array($item)) {
                continue;
            }

            $titulo = $item['titulo'] ?? null;
            $texto = $item['texto'] ?? null;

            if (! is_string($titulo) || ! is_string($texto)) {
                continue;
            }

            $itens[] = ['titulo' => $titulo, 'texto' => $texto];
        }

        return $itens;
    }
}
