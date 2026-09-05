<?php

/*
[Modulo: app/Http/Requests/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida a nova movimentacao de estoque do Painel Master (tela 3.4, mockup 52a) e gateia por tipo.
*/

namespace App\Http\Requests\Backend;

use App\Models\ProductionRequest;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EstoqueMovimentacaoRequest extends FormRequest
{
    /**
     * A permissão depende do tipo, como a tabela "O que cada tipo de
     * movimentação exige" do protótipo declara: abrir ordem de produção é
     * `velaro.stock.request_production`; entrada, saída, ajuste e reserva são
     * `velaro.stock.adjust`.
     *
     * Tipo desconhecido cai na exigência mais restritiva em vez de passar: quem
     * manda um `type` fora do vocabulário ainda precisa de permissão de escrita
     * para receber o 422 da validação.
     */
    public function authorize(): bool
    {
        $permissao = $this->input('type') === StockMovement::TYPE_PRODUCTION
            ? 'velaro.stock.request_production'
            : 'velaro.stock.adjust';

        return $this->user()?->hasBackendPermission($permissao) === true;
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        $producao = $this->input('type') === StockMovement::TYPE_PRODUCTION;

        return [
            'type' => ['required', 'string', 'in:'.implode(',', [
                StockMovement::TYPE_INBOUND,
                StockMovement::TYPE_OUTBOUND,
                StockMovement::TYPE_ADJUSTMENT,
                StockMovement::TYPE_PRODUCTION,
                StockMovement::TYPE_RESERVATION,
            ])],
            // "Produto / SKU" e "Tamanho (aro)" são os dois campos do protótipo,
            // ambos marcados como obrigatórios lá. O aro é que identifica a linha
            // de saldo — cada aro é um SKU próprio em `product_variants` —, e o
            // produto entra como conferência de que o par escolhido bate: exigir
            // os dois faz a conferência de {@see after()} valer sempre, e não só
            // quando o operador se lembra de escolher o produto.
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            // Produção não tem local obrigatório: `production_requests.stock_location_id`
            // é nulável porque a bancada pode decidir o cofre na entrega.
            'stock_location_id' => [$producao ? 'nullable' : 'required', 'integer', 'exists:stock_locations,id'],
            // No ajuste a quantidade é o novo saldo, e zerar o cofre é um
            // resultado legítimo de inventário; nos outros tipos é o quanto se
            // move, e mover zero peça não é movimentação.
            'quantity' => ['required', 'integer', $this->input('type') === StockMovement::TYPE_ADJUSTMENT ? 'min:0' : 'min:1', 'max:100000'],
            // Justificativa registrada: Anexo I §7. Vale para os cinco tipos, e
            // não só para o ajuste — todo movimento responde "por quê".
            'reason' => ['required', 'string', 'max:255'],
            'order_id' => [
                $this->input('type') === StockMovement::TYPE_RESERVATION ? 'required' : 'nullable',
                'integer',
                'exists:orders,id',
            ],
            'due_date' => [$producao ? 'required' : 'nullable', 'date'],
            'priority' => ['nullable', 'string', 'in:'.implode(',', [
                ProductionRequest::PRIORITY_LOW,
                ProductionRequest::PRIORITY_NORMAL,
                ProductionRequest::PRIORITY_HIGH,
                ProductionRequest::PRIORITY_URGENT,
            ])],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    /**
     * O aro escolhido tem de ser de fato um aro do produto escolhido. Sem esta
     * checagem, um `product_variant_id` de outra peça passaria pelo `exists` e
     * o saldo iria para o cofre errado.
     */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $produto = $this->input('product_id');
                $variante = $this->input('product_variant_id');

                if ($produto === null || $produto === '' || $variante === null || $variante === '') {
                    return;
                }

                $dono = ProductVariant::query()->whereKey($variante)->value('product_id');

                if ((int) $dono !== (int) $produto) {
                    $validador->errors()->add('product_variant_id', 'O aro escolhido não pertence ao produto selecionado.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Informe o motivo: movimentação de estoque é ação registrada, com responsável e justificativa.',
            'order_id.required' => 'Reserva exige o pedido que está segurando as peças.',
            'due_date.required' => 'Solicitação de produção exige o prazo previsto.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'tipo de movimentação',
            'product_id' => 'produto',
            'product_variant_id' => 'tamanho (aro)',
            'stock_location_id' => 'local de armazenamento',
            'quantity' => 'quantidade',
            'reason' => 'motivo',
            'order_id' => 'pedido vinculado',
            'due_date' => 'prazo previsto',
            'occurred_at' => 'data e hora',
        ];
    }
}
