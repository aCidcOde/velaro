<?php

/*
[Modulo: lang/es]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em espanhol dos tipos de movimentacao, situacao do saldo, reposicao e solicitacoes de producao.
*/

return [
    'movement_type' => [
        'inbound' => 'Entrada',
        'outbound' => 'Salida',
        'adjustment' => 'Ajuste',
        'reservation' => 'Reserva',
        'production' => 'Producción',
    ],

    'item_status' => [
        'in_stock' => 'En stock',
        'low_stock' => 'Stock bajo',
        'reserved' => 'Reservado',
        'out_of_stock' => 'Sin stock',
        'made_to_order' => 'Bajo pedido',
    ],

    'restock' => [
        'suggested' => 'Sugerida',
        'priority' => 'Prioritaria',
    ],

    'production_request_status' => [
        'pending' => 'Pendiente',
        'in_production' => 'En producción',
        'completed' => 'Completada',
        'canceled' => 'Cancelada',
    ],

    'production_request_priority' => [
        'low' => 'Baja',
        'normal' => 'Normal',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],
];
