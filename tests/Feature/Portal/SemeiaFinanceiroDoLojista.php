<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta o financeiro completo de um lojista (lotes, pedidos, cobrancas e NF-e) para os testes da tela 2.4.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Payment;
use App\Models\Reseller;
use Illuminate\Support\Carbon;

/**
 * Os tres testes do financeiro precisam da mesma cena: **dois lojistas com a
 * mesma forma de dado**, mudando so o dono. Sem vizinho na base, "nao vejo o dado
 * do outro" e uma afirmacao que nenhum teste consegue derrubar — e o proprio seed
 * do projeto existe com dois revendedores por essa razao.
 *
 * Os numeros sao redondos e distintos entre os dois lojistas de proposito: um
 * valor que aparece na tela errada denuncia de qual base ele veio.
 */
trait SemeiaFinanceiroDoLojista
{
    /**
     * A cena inteira e datada em maio de 2026, a semana do protocolo. Sem relogio
     * fixo, os KPIs "Este mes" e o calculo de vencido/em dia mudariam de resposta
     * conforme o dia em que a suite roda.
     */
    protected function fixarORelogio(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-25 09:00:00'));
    }

    /**
     * Um lojista completo: dois lotes (um quitado com NF-e, um em cobranca), tres
     * pedidos, as duas cobrancas e o rateio da nota.
     *
     * @return array<string, mixed>
     */
    protected function semearFinanceiro(Reseller $revendedor, string $sufixo): array
    {
        $doVizinho = $sufixo !== 'VEL02412';

        $cliente = Customer::factory()->forReseller($revendedor)->create([
            'user_id' => null,
            'name' => $doVizinho ? 'Cliente Vizinho' : 'Maria Silva',
        ]);

        $lotePago = OrderBatch::factory()->paid()->create([
            'reseller_id' => $revendedor->getKey(),
            'code' => 'LOTE-2026-W23-'.$sufixo,
            'cut_date' => '2026-05-14',
            'due_date' => '2026-05-21',
            'paid_at' => '2026-05-20 10:42:00',
            'total_amount' => $doVizinho ? 5555.00 : 1500.00,
        ]);

        $loteAberto = OrderBatch::factory()->open()->create([
            'reseller_id' => $revendedor->getKey(),
            'code' => 'LOTE-2026-W24-'.$sufixo,
            'cut_date' => '2026-05-21',
            'due_date' => '2026-05-28',
            'total_amount' => $doVizinho ? 7777.00 : 2400.00,
        ]);

        $pedidoA = $this->semearPedido($revendedor, $cliente, $loteAberto, 'ORD-'.$sufixo.'-A', $doVizinho ? 4000.00 : 1400.00, Order::PAYMENT_STATUS_PENDING, '2026-05-22 10:32:00');
        $pedidoB = $this->semearPedido($revendedor, $cliente, $loteAberto, 'ORD-'.$sufixo.'-B', $doVizinho ? 3777.00 : 1000.00, Order::PAYMENT_STATUS_AWAITING_CLEARANCE, '2026-05-23 14:15:00');
        $pedidoC = $this->semearPedido($revendedor, $cliente, $lotePago, 'ORD-'.$sufixo.'-C', $doVizinho ? 5555.00 : 1500.00, Order::PAYMENT_STATUS_PAID, '2026-05-15 09:21:00');

        // Cobranca quitada do lote fechado — e ela que alimenta o KPI
        // "Pagamentos confirmados" do mes.
        Payment::factory()->paid()->create([
            'batch_id' => $lotePago->getKey(),
            'method' => Payment::METHOD_PIX,
            'amount' => $doVizinho ? 5555.00 : 1500.00,
            'due_date' => '2026-05-21',
            'paid_at' => '2026-05-20 10:42:00',
            'external_id' => 'E2E'.$sufixo,
            'receipt_path' => 'comprovantes/'.$sufixo.'.pdf',
            'reconciled_by' => null,
        ]);

        // Cobranca em aberto do lote atual: boleto com linha digitavel emitida.
        $cobrancaAberta = Payment::factory()->pending()->create([
            'batch_id' => $loteAberto->getKey(),
            'method' => Payment::METHOD_BOLETO,
            'amount' => $doVizinho ? 7777.00 : 2400.00,
            'due_date' => '2026-05-28',
            'external_id' => $doVizinho
                ? '99990000090123456789012345678'
                : '00190000090123456789012345678',
        ]);

        $nota = Invoice::factory()->create([
            'batch_id' => $lotePago->getKey(),
            'series' => '1',
            'number' => $doVizinho ? '000.099.001' : '000.024.156',
            'amount' => $doVizinho ? 5555.00 : 1500.00,
            'status' => Invoice::STATUS_AUTHORIZED,
            'issued_at' => '2026-05-20 14:05:00',
            'pdf_path' => 'notas/'.$sufixo.'.pdf',
            'xml_path' => 'notas/'.$sufixo.'.xml',
        ]);

        // O rateio e o que liga a nota do lote a cada pedido (decisao 1.3).
        InvoiceItem::factory()->create([
            'invoice_id' => $nota->getKey(),
            'order_id' => $pedidoC->getKey(),
            'amount' => $doVizinho ? 5555.00 : 1500.00,
        ]);

        return [
            'cliente' => $cliente,
            'lote_pago' => $lotePago,
            'lote_aberto' => $loteAberto,
            'pedido_a' => $pedidoA,
            'pedido_b' => $pedidoB,
            'pedido_c' => $pedidoC,
            'cobranca_aberta' => $cobrancaAberta,
            'nota' => $nota,
        ];
    }

    /**
     * Pedido com valores redondos: `total = subtotal` (sem gravacao, frete nem
     * desconto), para o "Subtotal (custos Velaro)" da tela de pagamento fechar
     * exatamente no total do lote.
     */
    protected function semearPedido(
        Reseller $revendedor,
        Customer $cliente,
        OrderBatch $lote,
        string $numero,
        float $valor,
        string $statusDePagamento,
        string $feitoEm,
    ): Order {
        $pedido = Order::factory()->forReseller($revendedor)->create([
            'public_number' => $numero,
            'reference' => 'PC-'.$numero,
            'user_id' => null,
            'customer_id' => $cliente->getKey(),
            'batch_id' => $lote->getKey(),
            'payment_status' => $statusDePagamento,
            'operational_status' => $statusDePagamento === Order::PAYMENT_STATUS_PAID
                ? Order::OPERATIONAL_STATUS_IN_PRODUCTION
                : Order::OPERATIONAL_STATUS_REGISTERED,
            'total_amount' => $valor,
            'subtotal_amount' => $valor,
            'engraving_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
        ]);

        // `created_at` e a coluna "Data do pedido" da tela; o factory a preencheria
        // com o instante do teste.
        $pedido->forceFill(['created_at' => Carbon::parse($feitoEm)])->saveQuietly();

        return $pedido->refresh();
    }
}
