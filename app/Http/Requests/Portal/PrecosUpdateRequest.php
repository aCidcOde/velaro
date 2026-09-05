<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida as margens da tela 2.7: modelo, multiplicador, faixas, arredondamento e alcance da regra.
*/

namespace App\Http\Requests\Portal;

use App\Models\ResellerPriceSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PrecosUpdateRequest extends FormRequest
{
    /** Os três botões de escrita da tela 2.7. */
    public const ACTION_SAVE = 'salvar';

    public const ACTION_RECALCULATE = 'recalcular';

    public const ACTION_APPLY_ALL = 'aplicar-a-todos';

    /** @var list<string> */
    public const ACTIONS = [self::ACTION_SAVE, self::ACTION_RECALCULATE, self::ACTION_APPLY_ALL];

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
            'pricing_model' => ['required', Rule::in(ResellerPriceSetting::PRICING_MODELS)],
            'multiplier' => ['required', 'numeric', 'min:1', 'max:99.99'],

            // Margem é percentual sobre o preço de venda: 100% significaria custo
            // zero, então o teto é aberto em 99,99.
            'margin_global' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'margin_min' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'margin_ideal' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'margin_max' => ['required', 'numeric', 'min:0', 'max:99.99'],

            'rounding' => ['required', Rule::in(ResellerPriceSetting::ROUNDINGS)],
            'rule_scope' => ['required', Rule::in(ResellerPriceSetting::RULE_SCOPES)],

            'apply_to_all' => ['nullable', 'boolean'],
            'allow_manual_override' => ['nullable', 'boolean'],
            'allow_promotional_prices' => ['nullable', 'boolean'],

            'action' => ['nullable', Rule::in(self::ACTIONS)],
        ];
    }

    /**
     * As três faixas da "Configuração rápida" precisam ser crescentes.
     *
     * Mínima acima da ideal deixaria a coluna Status da tabela sem sentido: todo
     * produto cairia em "margem baixa" ao mesmo tempo em que bate a ideal.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            $minima = (float) $this->input('margin_min');
            $ideal = (float) $this->input('margin_ideal');
            $maxima = (float) $this->input('margin_max');

            if ($minima > $ideal) {
                $validador->errors()->add('margin_min', 'A margem mínima não pode ser maior que a ideal.');
            }

            if ($ideal > $maxima) {
                $validador->errors()->add('margin_max', 'A margem máxima não pode ser menor que a ideal.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'multiplier.min' => 'O fator de multiplicação não pode ser menor que 1.',
            'margin_global.max' => 'A margem sobre o preço de venda precisa ser menor que 100%.',
            'rounding.in' => 'Selecione uma das políticas de arredondamento da lista.',
            'rule_scope.in' => 'Selecione um dos alcances de regra da lista.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pricing_model' => 'modelo de precificação',
            'multiplier' => 'fator de multiplicação',
            'margin_global' => 'margem global padrão',
            'margin_min' => 'margem mínima desejada',
            'margin_ideal' => 'margem ideal',
            'margin_max' => 'margem máxima',
            'rounding' => 'arredondamento de preços',
            'rule_scope' => 'regra de preço',
        ];
    }

    /**
     * Campos de `reseller_price_settings`, prontos para o service.
     *
     * @return array<string, mixed>
     */
    public function dados(): array
    {
        return [
            'pricing_model' => $this->string('pricing_model')->toString(),
            'multiplier' => (float) $this->input('multiplier'),
            'margin_global' => (float) $this->input('margin_global'),
            'margin_min' => (float) $this->input('margin_min'),
            'margin_ideal' => (float) $this->input('margin_ideal'),
            'margin_max' => (float) $this->input('margin_max'),
            'rounding' => $this->string('rounding')->toString(),
            'rule_scope' => $this->string('rule_scope')->toString(),
            // "Aplicar para todos os produtos" é o próprio toggle `apply_to_all`
            // ligado — o botão da Configuração rápida e o toggle da tela 2.6 são a
            // mesma coluna.
            'apply_to_all' => $this->querAplicarATodos() || $this->boolean('apply_to_all'),
            'allow_manual_override' => $this->boolean('allow_manual_override'),
            'allow_promotional_prices' => $this->boolean('allow_promotional_prices'),
        ];
    }

    /**
     * Recalcular e aplicar a todos carimbam `recalculated_at`: é a data que o
     * KPI "Atualizado em" mostra.
     */
    public function querRecalcular(): bool
    {
        return in_array(
            $this->string('action')->toString(),
            [self::ACTION_RECALCULATE, self::ACTION_APPLY_ALL],
            true,
        );
    }

    public function querAplicarATodos(): bool
    {
        return $this->string('action')->toString() === self::ACTION_APPLY_ALL;
    }
}
