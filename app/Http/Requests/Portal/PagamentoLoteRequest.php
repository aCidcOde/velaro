<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o meio de pagamento exibido no lote (pix, boleto ou transferencia) e a pagina da lista de pedidos.
*/

namespace App\Http\Requests\Portal;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagamentoLoteRequest extends FormRequest
{
    /**
     * Os tres meios B2B habilitados (regra 2 da tela 2.4). Sao as constantes do
     * proprio `Payment`, entao o radio da tela e a coluna do banco falam a mesma
     * lingua — nao ha slug de interface paralelo ao do dominio.
     *
     * @var list<string>
     */
    public const MEIOS = [
        Payment::METHOD_PIX,
        Payment::METHOD_BOLETO,
        Payment::METHOD_BANK_TRANSFER,
    ];

    private const PAGINA_MAXIMA = 10000;

    /**
     * O dono do lote ja foi conferido antes daqui: `{batch}` e resolvido pelo
     * bind escopado de `ResellerScope`, que devolve 404 quando o lote e de outro
     * lojista. Ver a nota da classe sobre 404 e nao 403.
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
        return [
            'metodo' => ['nullable', 'string', Rule::in(self::MEIOS)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * O meio destacado na tela. Sem parametro, o padrao e o meio da cobranca que
     * ja existe para o lote; so quando nao ha cobranca a tela abre no Pix, que e
     * o que o prototipo mostra selecionado.
     */
    public function metodo(?string $meioDaCobranca = null): string
    {
        $metodo = $this->input('metodo');

        if (is_string($metodo) && in_array($metodo, self::MEIOS, true)) {
            return $metodo;
        }

        if ($meioDaCobranca !== null && in_array($meioDaCobranca, self::MEIOS, true)) {
            return $meioDaCobranca;
        }

        return Payment::METHOD_PIX;
    }

    /**
     * Ver a nota de {@see FinanceiroFiltroRequest::pagina()}: o paginador nao le
     * esta instancia, entao a pagina precisa ser entregue a ele ja podada.
     */
    public function pagina(): int
    {
        $pagina = $this->input('page');

        return is_numeric($pagina) ? max(1, min(self::PAGINA_MAXIMA, (int) $pagina)) : 1;
    }

    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        if (array_key_exists('metodo', $query)) {
            $metodo = $query['metodo'];
            $limpo['metodo'] = is_string($metodo) && in_array($metodo, self::MEIOS, true) ? $metodo : null;
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
