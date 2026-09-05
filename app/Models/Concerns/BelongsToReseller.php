<?php

/*
[Modulo: app/Models/Concerns]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Da ao model do portal o dono em reseller_id e o filtro por lojista que toda query precisa usar.
*/

namespace App\Models\Concerns;

use App\Models\Contracts\OwnedByReseller;
use App\Models\Reseller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Implementação de {@see OwnedByReseller}.
 *
 * Não é um global scope de propósito. Um global scope amarrado ao usuário logado
 * filtraria também o Painel Master — que precisa enxergar a base inteira — e a
 * vitrine pública, que lê a loja de um revendedor sem ninguém autenticado. Aqui o
 * filtro é explícito: quem consulta declara de quem é o dado.
 *
 * @phpstan-require-extends Model
 */
trait BelongsToReseller
{
    /**
     * Coluna do dono. As seis tabelas do portal carregam a FK direta — não existe
     * dono alcançado por travessia de relação, e é isso que mantém o filtro barato
     * e impossível de esquecer no meio de um join.
     */
    private const OWNER_COLUMN = 'reseller_id';

    public function resellerOwnerId(): ?int
    {
        $value = $this->getAttribute(self::OWNER_COLUMN);

        return $value === null ? null : (int) $value;
    }

    public function isOwnedBy(Reseller|int|null $reseller): bool
    {
        $ownerId = self::resellerKey($reseller);

        return $ownerId !== null && $this->resellerOwnerId() === $ownerId;
    }

    /**
     * Filtro obrigatório de todo o ambiente `portal`.
     *
     * Revendedor nulo devolve conjunto vazio, nunca a tabela inteira: se a origem
     * do escopo se perdeu no caminho, o resultado seguro é não mostrar nada.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOwnedBy(Builder $query, Reseller|int|null $reseller): void
    {
        $ownerId = self::resellerKey($reseller);

        if ($ownerId === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where($query->qualifyColumn(self::OWNER_COLUMN), $ownerId);
    }

    private static function resellerKey(Reseller|int|null $reseller): ?int
    {
        if ($reseller instanceof Reseller) {
            $key = $reseller->getKey();

            return is_numeric($key) ? (int) $key : null;
        }

        return $reseller;
    }
}
