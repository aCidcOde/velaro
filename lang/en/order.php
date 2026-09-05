<?php

/*
[Modulo: lang/en]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em ingles dos status operacional e de pagamento do pedido, do lote, da remessa e do escopo dos eventos.
*/

return [
    'operational_status' => [
        'registered' => 'Registered',
        'payment_confirmed' => 'Payment confirmed',
        'in_production' => 'In production',
        'production_completed' => 'Production completed',
        'in_transit' => 'In transit',
        'ready_for_pickup' => 'Ready for pickup',
        'picked_up' => 'Picked up',
    ],

    'payment_status' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'refunded' => 'Refunded',
        'canceled' => 'Canceled',
    ],

    'batch_status' => [
        'open' => 'Open',
        'paid' => 'Paid',
    ],

    'shipment_status' => [
        'awaiting_release' => 'Awaiting release',
    ],

    'event_scope' => [
        'operational' => 'Operational',
        'payment' => 'Payment',
    ],
];
