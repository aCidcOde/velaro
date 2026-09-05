<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Normaliza os filtros da fila de chamados da tela 2.8: busca, status, categoria e periodo.
*/

namespace App\Http\Requests\Portal;

use App\Models\SupportTicket;
use App\Services\Portal\SupportDeskService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuporteFiltroRequest extends FormRequest
{
    private const PAGINA_MAXIMA = 10000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Como na tela 2.7: filtro inválido some, a lista continua abrindo.
     */
    protected function prepareForValidation(): void
    {
        $busca = $this->query('q');
        $busca = is_string($busca) ? trim($busca) : '';

        $status = $this->query('status');
        $categoria = $this->query('categoria');
        $periodo = $this->query('periodo');

        $limpo = [
            'q' => $busca === '' ? null : mb_substr($busca, 0, 120),
            'status' => is_string($status) && in_array($status, SupportTicket::STATUSES, true) ? $status : null,
            'categoria' => is_string($categoria) && in_array($categoria, SupportTicket::CATEGORIES, true) ? $categoria : null,
            'periodo' => is_string($periodo) && array_key_exists($periodo, SupportDeskService::PERIODS)
                ? $periodo
                : SupportDeskService::PERIOD_DEFAULT,
        ];

        $pagina = $this->query('page');

        if ($pagina !== null) {
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        $this->merge($limpo);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(SupportTicket::STATUSES)],
            'categoria' => ['nullable', Rule::in(SupportTicket::CATEGORIES)],
            'periodo' => ['required', Rule::in(array_keys(SupportDeskService::PERIODS))],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * @return array{q: string|null, status: string|null, categoria: string|null, periodo: string}
     */
    public function filtros(): array
    {
        return [
            'q' => $this->textoOuNulo('q'),
            'status' => $this->textoOuNulo('status'),
            'categoria' => $this->textoOuNulo('categoria'),
            'periodo' => $this->string('periodo')->toString(),
        ];
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
