<?php

/*
[Modulo: lang/pt_BR]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em portugues dos status operacional e de pagamento do pedido, do lote, da remessa e do escopo dos eventos.
*/

return [
    'operational_status' => [
        'registered' => 'Registrado',
        'payment_confirmed' => 'Pagamento confirmado',
        'in_production' => 'Em produção',
        'production_completed' => 'Produção finalizada',
        'in_transit' => 'Em transporte',
        'ready_for_pickup' => 'Pronto para retirada',
        'picked_up' => 'Retirado',
    ],

    'payment_status' => [
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'overdue' => 'Vencido',
        'refunded' => 'Estornado',
        'canceled' => 'Cancelado',
    ],

    'batch_status' => [
        'open' => 'Em aberto',
        'paid' => 'Pago',
    ],

    'shipment_status' => [
        'awaiting_release' => 'Aguardando liberação',
    ],

    'event_scope' => [
        'operational' => 'Operacional',
        'payment' => 'Pagamento',
    ],
];
