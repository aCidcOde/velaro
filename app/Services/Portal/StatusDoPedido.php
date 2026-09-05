<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Traduz os dois status independentes do pedido em rotulo e chip do design system, sem string crua na view.
*/

namespace App\Services\Portal;

use App\Models\Order;
use App\Services\Portal\Concerns\FormataDados;

/**
 * Regra crítica 1 da tela 2.5 (Anexo I §6): **status do pedido e status do
 * pagamento são campos independentes**. Um pedido pode estar `in_production` com
 * pagamento `pending`, e o inverso também acontece — por isso são duas colunas na
 * lista, dois chips no detalhe e dois métodos aqui. Nada nesta classe deriva um
 * do outro.
 *
 * As chaves são sempre as constantes de {@see Order}; o rótulo vem de
 * `lang/<idioma>/order.php` e a classe do chip é a do design system. Uma tela do portal
 * nunca escreve `'in_production'` nem `'Em produção'` à mão.
 */
final class StatusDoPedido
{
    use FormataDados;

    /**
     * A ordem canônica do ciclo operacional — é a espinha da linha do tempo da
     * tela 2.5 e da 2.11, e a mesma cadeia que o seed percorre ao registrar os
     * eventos. Não existe status operacional fora desta lista.
     *
     * @var list<string>
     */
    public const CADEIA_OPERACIONAL = [
        Order::OPERATIONAL_STATUS_REGISTERED,
        Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
        Order::OPERATIONAL_STATUS_IN_PRODUCTION,
        Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED,
        Order::OPERATIONAL_STATUS_IN_TRANSIT,
        Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
        Order::OPERATIONAL_STATUS_PICKED_UP,
    ];

    /**
     * Os status de pagamento na ordem em que o filtro da tela 2.5 os oferece.
     *
     * @var list<string>
     */
    public const STATUS_PAGAMENTO = [
        Order::PAYMENT_STATUS_PENDING,
        Order::PAYMENT_STATUS_AWAITING_CLEARANCE,
        Order::PAYMENT_STATUS_PAID,
        Order::PAYMENT_STATUS_OVERDUE,
        Order::PAYMENT_STATUS_REFUNDED,
        Order::PAYMENT_STATUS_CANCELED,
    ];

    /**
     * Pedido que ainda não terminou o ciclo: é o que o KPI "Pedidos em aberto" da
     * tela 2.3 conta. `picked_up` é o único terminal do lado operacional.
     *
     * @var list<string>
     */
    public const EM_ABERTO = [
        Order::OPERATIONAL_STATUS_REGISTERED,
        Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
        Order::OPERATIONAL_STATUS_IN_PRODUCTION,
        Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED,
        Order::OPERATIONAL_STATUS_IN_TRANSIT,
        Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
    ];

    /** @var array<string, string> */
    private const CHIP_OPERACIONAL = [
        Order::OPERATIONAL_STATUS_REGISTERED => 'chip--neutral',
        Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED => 'chip--info',
        Order::OPERATIONAL_STATUS_IN_PRODUCTION => 'chip--violet',
        Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED => 'chip--violet',
        Order::OPERATIONAL_STATUS_IN_TRANSIT => 'chip--info',
        Order::OPERATIONAL_STATUS_READY_FOR_PICKUP => 'chip--ok',
        Order::OPERATIONAL_STATUS_PICKED_UP => 'chip--ok',
    ];

    /** @var array<string, string> */
    private const CHIP_PAGAMENTO = [
        Order::PAYMENT_STATUS_PENDING => 'chip--warn',
        Order::PAYMENT_STATUS_AWAITING_CLEARANCE => 'chip--warn',
        Order::PAYMENT_STATUS_PAID => 'chip--ok',
        Order::PAYMENT_STATUS_OVERDUE => 'chip--danger',
        Order::PAYMENT_STATUS_REFUNDED => 'chip--neutral',
        Order::PAYMENT_STATUS_CANCELED => 'chip--danger',
    ];

    /**
     * @return array{chave: string, rotulo: string, chip: string}
     */
    public function operacional(?string $status): array
    {
        $chave = $status ?? '';

        return [
            'chave' => $chave,
            'rotulo' => $this->traduzir('order.operational_status.', $chave),
            'chip' => self::CHIP_OPERACIONAL[$chave] ?? 'chip--neutral',
        ];
    }

    /**
     * @return array{chave: string, rotulo: string, chip: string}
     */
    public function pagamento(?string $status): array
    {
        $chave = $status ?? '';

        return [
            'chave' => $chave,
            'rotulo' => $this->traduzir('order.payment_status.', $chave),
            'chip' => self::CHIP_PAGAMENTO[$chave] ?? 'chip--neutral',
        ];
    }

    /**
     * Opções dos dois selects de status da barra de filtros da tela 2.5.
     *
     * @return array{operacional: list<array{valor: string, rotulo: string}>, pagamento: list<array{valor: string, rotulo: string}>}
     */
    public function opcoesDeFiltro(): array
    {
        return [
            'operacional' => array_map(
                fn (string $status): array => ['valor' => $status, 'rotulo' => $this->operacional($status)['rotulo']],
                self::CADEIA_OPERACIONAL,
            ),
            'pagamento' => array_map(
                fn (string $status): array => ['valor' => $status, 'rotulo' => $this->pagamento($status)['rotulo']],
                self::STATUS_PAGAMENTO,
            ),
        ];
    }

    /**
     * Posição do status na cadeia operacional, ou `-1` para status desconhecido —
     * o que faz a linha do tempo desenhar todos os degraus como pendentes em vez
     * de marcar um degrau errado como concluído.
     */
    public function degrau(?string $status): int
    {
        $posicao = array_search($status, self::CADEIA_OPERACIONAL, true);

        return is_int($posicao) ? $posicao : -1;
    }

    /**
     * Rótulo traduzido. A chave vazia cai no travessão; a chave sem tradução cai
     * no próprio slug, que é melhor que uma célula em branco quando alguém
     * gravar um status fora do vocabulário.
     */
    private function traduzir(string $prefixo, string $chave): string
    {
        if ($chave === '') {
            return self::VAZIO;
        }

        return $this->rotulo($prefixo.$chave) ?? $chave;
    }
}
