<?php

/*
[Modulo: lang/pt_BR]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em portugues dos tipos de movimentacao, situacao do saldo, reposicao e solicitacoes de producao.
*/

return [
    'movement_type' => [
        'inbound' => 'Entrada',
        'outbound' => 'Saída',
        'adjustment' => 'Ajuste',
        'reservation' => 'Reserva',
        'production' => 'Produção',
    ],

    'item_status' => [
        'in_stock' => 'Em estoque',
        'low_stock' => 'Baixo estoque',
        'reserved' => 'Reservado',
        'out_of_stock' => 'Sem estoque',
        'made_to_order' => 'Sob encomenda',
    ],

    'restock' => [
        'suggested' => 'Sugerida',
        'priority' => 'Prioritária',
    ],

    'production_request_status' => [
        'pending' => 'Pendente',
        'in_production' => 'Em produção',
        'completed' => 'Concluída',
        'canceled' => 'Cancelada',
    ],

    'production_request_priority' => [
        'low' => 'Baixa',
        'normal' => 'Normal',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],
];
