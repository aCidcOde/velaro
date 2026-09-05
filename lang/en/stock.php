<?php

/*
[Modulo: lang/en]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em ingles dos tipos de movimentacao, situacao do saldo, reposicao e solicitacoes de producao.
*/

return [
    'movement_type' => [
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
        'adjustment' => 'Adjustment',
        'reservation' => 'Reservation',
        'production' => 'Production',
    ],

    'item_status' => [
        'in_stock' => 'In stock',
        'low_stock' => 'Low stock',
        'reserved' => 'Reserved',
        'out_of_stock' => 'Out of stock',
        'made_to_order' => 'Made to order',
    ],

    'restock' => [
        'suggested' => 'Suggested',
        'priority' => 'Priority',
    ],

    'production_request_status' => [
        'pending' => 'Pending',
        'in_production' => 'In production',
        'completed' => 'Completed',
        'canceled' => 'Canceled',
    ],

    'production_request_priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
];
