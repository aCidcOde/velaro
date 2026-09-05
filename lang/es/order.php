<?php

/*
[Modulo: lang/es]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em espanhol dos status operacional e de pagamento do pedido, do lote, da remessa e do escopo dos eventos.
*/

return [
    'operational_status' => [
        'registered' => 'Registrado',
        'payment_confirmed' => 'Pago confirmado',
        'in_production' => 'En producción',
        'production_completed' => 'Producción finalizada',
        'in_transit' => 'En tránsito',
        'ready_for_pickup' => 'Listo para retiro',
        'picked_up' => 'Retirado',
    ],

    'payment_status' => [
        'pending' => 'Pendiente',
        'awaiting_clearance' => 'Esperando compensación',
        'paid' => 'Pagado',
        'overdue' => 'Vencido',
        'refunded' => 'Reembolsado',
        'canceled' => 'Cancelado',
    ],

    'batch_status' => [
        'open' => 'Abierto',
        'paid' => 'Pagado',
    ],

    'shipment_status' => [
        'awaiting_release' => 'Esperando liberación',
    ],

    'event_scope' => [
        'operational' => 'Operativo',
        'payment' => 'Pago',
    ],
];
