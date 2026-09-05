<?php

/*
[Modulo: app/Http/Requests/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o pedido interno criado pelo Painel Master em nome do revendedor (tela 3.6, mockup 61).
*/

namespace App\Http\Requests\Backend;

use App\Models\OrderBatch;
use App\Models\Payment;
use App\Services\Backend\PedidoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PedidoCriarRequest extends FormRequest
{
    /**
     * Criar pedido é escrita na esteira operacional — o cadastro já nasce com o
     * primeiro evento de `registered` em `order_status_events`. O catálogo de
     * ACL não tem `velaro.orders.create`, e inventar chave nova é justamente o
     * que a seção 2 do doc proíbe: as quatro permissões da tela são as que
     * existem, e a única que cobre escrita de status é esta.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasBackendPermission('velaro.orders.update_status') === true;
    }

    /**
     * O formulário desenha linhas de item em branco — é como se adiciona item
     * sem uma linha de JavaScript, e o design system não carrega Alpine. Linha
     * sem SKU escolhido não é item vazio a validar: é linha que o operador não
     * usou, e sai antes das regras.
     */
    protected function prepareForValidation(): void
    {
        $itens = $this->input('itens');

        if (! is_array($itens)) {
            return;
        }

        $preenchidos = array_values(array_filter(
            $itens,
            static fn (mixed $linha): bool => is_array($linha)
                && isset($linha['product_variant_id'])
                && trim((string) $linha['product_variant_id']) !== '',
        ));

        $this->merge(['itens' => $preenchidos]);
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reseller_id' => ['required', 'integer', 'exists:resellers,id'],
            'origin_channel' => ['required', 'string', 'in:'.implode(',', array_keys(PedidoService::CANAIS_DE_ORIGEM))],
            'reference' => ['nullable', 'string', 'max:60'],

            // Cliente final é opcional (mockup 61): ele não tem login e não paga
            // a Velaro — só existe como pessoa vinculada ao pedido.
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_document' => ['nullable', 'string', 'max:20'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],

            'itens' => ['required', 'array', 'min:1'],
            'itens.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'itens.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'itens.*.engraving_text' => ['nullable', 'string', 'max:60'],

            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', [
                Payment::METHOD_PIX,
                Payment::METHOD_BOLETO,
                Payment::METHOD_BANK_TRANSFER,
            ])],
            // Nulável de propósito: `orders.batch_id` é nulável no schema porque
            // o pedido pode nascer antes de a semana de faturamento abrir. O
            // protótipo marca o campo com asterisco supondo que sempre existe um
            // lote aberto — quando não existe, exigir um bloquearia o cadastro.
            'batch_id' => ['nullable', 'integer', 'exists:order_batches,id'],

            'production_days' => ['required', 'integer', 'min:1', 'max:180'],
            'due_date' => ['required', 'date'],
            'delivery_mode' => ['required', 'string', 'in:'.implode(',', array_keys(PedidoService::MODOS_DE_ENTREGA))],
            'expected_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * O lote é do revendedor, e de mais ninguém: faturar o pedido de um lojista
     * na remessa de outro misturaria duas cobranças que nunca se encontram.
     */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $lote = $this->input('batch_id');

                if ($lote === null || $lote === '') {
                    return;
                }

                $dono = OrderBatch::query()->whereKey($lote)->value('reseller_id');

                if ((int) $dono !== (int) $this->input('reseller_id')) {
                    $validador->errors()->add('batch_id', 'O lote escolhido pertence a outro revendedor.');
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
            'itens.required' => 'Um pedido precisa de pelo menos um item.',
            'itens.min' => 'Um pedido precisa de pelo menos um item.',
            'reseller_id.required' => 'Escolha o revendedor em nome de quem o pedido é registrado.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reseller_id' => 'revendedor',
            'origin_channel' => 'canal de origem',
            'payment_method' => 'forma de pagamento',
            'batch_id' => 'lote de faturamento',
            'production_days' => 'prazo de produção',
            'due_date' => 'vencimento',
            'delivery_mode' => 'modo de entrega',
            'expected_at' => 'previsão de envio',
        ];
    }
}
