<?php

/*
[Modulo: app/Models/Contracts]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Contrato do registro que pertence a um unico lojista e nunca pode ser lido por outro.
*/

namespace App\Models\Contracts;

use App\Models\Reseller;
use App\Support\ResellerScope;

/**
 * Marca o registro como propriedade de um revendedor.
 *
 * Implementar esta interface é o que coloca a tabela dentro do escopo do Portal
 * do Lojista: nenhuma consulta do ambiente `portal` alcança uma destas linhas
 * sem passar por {@see ResellerScope}. Os seis models cobertos são
 * `Order`, `Customer`, `OrderBatch`, `SupportTicket`, `ResellerPriceRule` e
 * `ResellerStore` — a lista canônica está em `ResellerScope::SCOPED_MODELS`.
 */
interface OwnedByReseller
{
    /**
     * Id do revendedor dono do registro.
     *
     * Nulo é possível e não significa "de todos": `orders.reseller_id` e
     * `customers.reseller_id` são nuláveis porque o scaffold tem pedido e cliente
     * sem lojista nenhum. Registro sem dono não pertence a revendedor algum e,
     * portanto, não aparece no portal de nenhum deles.
     */
    public function resellerOwnerId(): ?int;

    /**
     * O registro é deste revendedor? Registro órfão responde `false` para todos.
     */
    public function isOwnedBy(Reseller|int|null $reseller): bool;
}
