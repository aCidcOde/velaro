<?php

/*
[Modulo: app/Http/Requests/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Normaliza o que a vitrine publica aceita da URL: a aba de categoria, a pagina da grade e o token do visitante.
*/

namespace App\Http\Requests\Vitrine;

use App\Services\Vitrine\VitrineCatalogoService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VitrineFiltroRequest extends FormRequest
{
    /** Teto do slug de categoria — `categories.slug` é `varchar(255)`. */
    private const LIMITE_CATEGORIA = 255;

    /** Teto de página: `?page=999999` viraria um OFFSET gigante no MySQL. */
    private const PAGINA_MAXIMA = 10000;

    /** Tamanho de `favorites.visitor_token`. Token maior não é token: é lixo. */
    private const LIMITE_VISITANTE = 64;

    /**
     * A vitrine é o único ambiente público e sem login do sistema: o consumidor
     * final não tem conta em lugar nenhum. Não há permissão a checar aqui — o
     * que decide se a loja abre é ela estar publicada, e isso é regra de
     * negócio, não autorização de usuário.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Sem `exists`: slug desconhecido na aba é grade completa, não 422. Uma
        // página pública que devolve erro de validação para um link velho
        // manda o consumidor embora — e `prepareForValidation()` já podou tudo,
        // então estas regras são rede de segurança.
        return [
            'categoria' => ['nullable', 'string', 'max:'.self::LIMITE_CATEGORIA],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * Filtros já normalizados, prontos para o service.
     *
     * @return array{categoria: string|null, visitante: string|null}
     */
    public function filtros(): array
    {
        return [
            'categoria' => $this->categoria(),
            'visitante' => $this->visitante(),
        ];
    }

    /**
     * Token do navegador do visitante, de `favorites.visitor_token`.
     *
     * Vem do cookie porque o consumidor final não faz login — é a única
     * identidade que ele tem nesta loja. Formato apertado de propósito: só
     * hexadecimal e no comprimento da coluna. Qualquer outra coisa vira ausência
     * de token, e a vitrine responde como responde a um visitante novo, sem
     * favorito nenhum.
     */
    public function visitante(): ?string
    {
        $token = $this->cookie(VitrineCatalogoService::COOKIE_VISITANTE);

        if (! is_string($token)) {
            return null;
        }

        $token = trim($token);

        return preg_match('/^[A-Za-z0-9]{8,'.self::LIMITE_VISITANTE.'}$/', $token) === 1
            ? $token
            : null;
    }

    private function categoria(): ?string
    {
        $valor = $this->input('categoria');

        return is_string($valor) && $valor !== '' ? $valor : null;
    }

    /**
     * Espaço em branco vira ausência de filtro, e a página é presa na faixa útil
     * antes de o paginador ler `page` direto da request — validar sozinho não o
     * protegeria.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        if (array_key_exists('categoria', $query)) {
            $categoria = $query['categoria'];
            $limpo['categoria'] = is_string($categoria) && trim($categoria) !== ''
                ? mb_substr(trim($categoria), 0, self::LIMITE_CATEGORIA)
                : null;
        }

        if (array_key_exists('page', $query)) {
            $pagina = $query['page'];
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        if ($limpo !== []) {
            $this->merge($limpo);
        }
    }
}
