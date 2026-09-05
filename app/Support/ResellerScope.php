<?php

/*
[Modulo: app/Support]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Isola o Portal do Lojista por reseller_id: abre as queries do revendedor e barra o registro alheio com 404.
*/

namespace App\Support;

use App\Http\Middleware\EnsureUserIsReseller;
use App\Models\Concerns\BelongsToReseller;
use App\Models\Contracts\OwnedByReseller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use App\Models\ResellerStore;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Routing\Route as RoutedRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * A regra central do ambiente `portal`: **tudo é escopado por `reseller_id`**.
 * Um lojista nunca vê pedido, cliente, lote, nota, chamado ou regra de preço de
 * outro. Esta classe é o único ponto por onde isso é garantido, nas duas frentes
 * em que o vazamento pode acontecer:
 *
 * 1. **Listagem.** Toda query do portal nasce das relações do próprio revendedor
 *    ({@see orders()}, {@see customers()}, …). Não existe `Order::query()` solto
 *    num controller do portal — a consulta parte de `auth()->user()->reseller` e
 *    o `WHERE reseller_id` é estrutural, não um filtro que dá para esquecer.
 *    Quando a consulta já existe (join, `whereIn`), o filtro entra pelo scope
 *    `ownedBy()` do trait {@see BelongsToReseller}.
 *
 * 2. **Route model binding.** `{customer}`, `{batch}`, `{order:public_number}` e
 *    `{ticket:code}` são resolvidos por {@see bindRouteParameters()}, que confere
 *    o dono antes de entregar o registro ao controller.
 *
 * ## Por que 404 e não 403
 *
 * 403 responde "existe, mas não é seu" — e isso já é o vazamento. O identificador
 * do pedido (`ORD004112`), do chamado (`SUP-2026-0031`) e do lote
 * (`LOTE-2026-W21`) é curto e sequencial o bastante para ser percorrido: com 403
 * o lojista mede o tamanho da base do concorrente, descobre quantos pedidos ele
 * fez na semana e confirma se um número específico existe. Com 404 as duas
 * respostas — "não existe" e "é de outro lojista" — são literalmente a mesma
 * exceção, e não há sinal nenhum a colher. É a mesma razão pela qual o
 * `{customer}` de outro revendedor devolve 404: o CPF do cliente final de um
 * concorrente não pode ser confirmado nem por diferença de status HTTP.
 *
 * O 403 continua sendo a resposta certa um degrau antes, em
 * {@see EnsureUserIsReseller}: ali a negativa é sobre o
 * ambiente inteiro ("você não é um revendedor aprovado"), não sobre a existência
 * de um registro específico.
 */
final class ResellerScope
{
    /**
     * Os seis models cobertos pelo escopo. Todos implementam
     * {@see OwnedByReseller} e carregam `reseller_id`.
     *
     * @var list<class-string<OwnedByReseller>>
     */
    public const SCOPED_MODELS = [
        Order::class,
        Customer::class,
        OrderBatch::class,
        SupportTicket::class,
        ResellerPriceRule::class,
        ResellerStore::class,
    ];

    /**
     * Parâmetros de rota resolvidos sob o escopo. A chave é o nome do parâmetro
     * como `routes/velaro.php` o declara.
     *
     * `{store:slug}` fica de fora porque só existe na vitrine white label, que é
     * pública por definição — a loja do revendedor é justamente o que o
     * consumidor final precisa abrir sem login.
     *
     * @var array<string, class-string<Model&OwnedByReseller>>
     */
    private const BOUND_PARAMETERS = [
        'customer' => Customer::class,
        'batch' => OrderBatch::class,
        'order' => Order::class,
        'ticket' => SupportTicket::class,
    ];

    /**
     * O middleware que marca a rota como pertencente ao ambiente do lojista.
     *
     * O alias vem de `bootstrap/app.php` e é o mesmo que o grupo `portal.` declara
     * em `routes/velaro.php`. Renomear o alias sem trocar esta linha desligaria o
     * escopo em silêncio — o que segura isso é `ResellerScopeTest`, que reprova
     * assim que o registro de outro lojista deixar de responder 404.
     */
    private const PORTAL_MIDDLEWARE = 'reseller';

    public function __construct(public readonly Reseller $reseller) {}

    public static function for(Reseller $reseller): self
    {
        return new self($reseller);
    }

    /**
     * Escopo do revendedor autenticado.
     *
     * Chegar aqui sem revendedor é erro de programação, não de usuário: o
     * middleware `reseller` já barrou com 403 quem não é lojista aprovado antes
     * de qualquer controller do portal rodar.
     */
    public static function current(): self
    {
        $reseller = self::currentReseller();

        if ($reseller === null) {
            throw new RuntimeException(
                'ResellerScope::current() fora do Portal do Lojista: '
                .'o usuário autenticado não está vinculado a um revendedor.'
            );
        }

        return new self($reseller);
    }

    /** @return HasMany<Order, Reseller> */
    public function orders(): HasMany
    {
        return $this->reseller->orders();
    }

    /** @return HasMany<Customer, Reseller> */
    public function customers(): HasMany
    {
        return $this->reseller->customers();
    }

    /** @return HasMany<OrderBatch, Reseller> */
    public function batches(): HasMany
    {
        return $this->reseller->batches();
    }

    /** @return HasMany<SupportTicket, Reseller> */
    public function tickets(): HasMany
    {
        return $this->reseller->tickets();
    }

    /** @return HasMany<ResellerPriceRule, Reseller> */
    public function priceRules(): HasMany
    {
        return $this->reseller->priceRules();
    }

    /**
     * A vitrine do próprio lojista — `reseller_stores.reseller_id` é UNIQUE, então
     * é sempre uma loja ou nenhuma.
     */
    public function store(): ?ResellerStore
    {
        return $this->reseller->store()->first();
    }

    public function owns(OwnedByReseller $record): bool
    {
        return $record->isOwnedBy($this->reseller);
    }

    /**
     * Guarda para o registro que não veio por route model binding — um id que
     * chegou pelo corpo de um formulário, por exemplo.
     *
     * Devolve o próprio registro, para poder ser usado no meio de uma expressão,
     * e some com 404 (nunca 403) quando o dono é outro: ver a nota da classe.
     *
     * @template TRecord of Model&OwnedByReseller
     *
     * @param  TRecord  $record
     * @return TRecord
     */
    public function assertOwns(OwnedByReseller $record): OwnedByReseller
    {
        if (! $this->owns($record)) {
            throw self::notFound($record::class, (string) $record->getKey());
        }

        return $record;
    }

    /**
     * Liga a resolução escopada dos parâmetros de rota do portal.
     *
     * Um `Route::bind()` é global por nome de parâmetro, e `{customer}`,
     * `{order:public_number}` e `{ticket:code}` também aparecem no Painel Master e
     * na vitrine. Por isso o escopo só entra nas rotas que carregam o middleware
     * `reseller`; nas demais a resolução é a padrão do Laravel, porque o Master
     * enxerga a base inteira e a vitrine é pública.
     */
    public static function bindRouteParameters(): void
    {
        foreach (self::BOUND_PARAMETERS as $parameter => $model) {
            Route::bind(
                $parameter,
                static fn (string $value, RoutedRoute $route): Model|string => self::resolveBinding($model, $parameter, $value, $route),
            );
        }
    }

    /**
     * @param  class-string<Model&OwnedByReseller>  $model
     */
    private static function resolveBinding(string $model, string $parameter, string $value, RoutedRoute $route): Model|string
    {
        // Fora do portal o parâmetro volta cru, exatamente como chegou. Devolver a
        // string faz o `SubstituteBindings` seguir para o binding implícito do
        // Laravel, e as rotas do Master, da vitrine, do app e da API mobile — que
        // também têm `{order}` e `{customer}` — continuam se comportando como
        // antes deste bind existir, inclusive a resolução de `Order` por id.
        if (! self::isPortalRoute($route)) {
            return $value;
        }

        $instance = new $model;
        $field = $route->bindingFieldFor($parameter) ?? $instance->getRouteKeyName();

        /** @var (Model&OwnedByReseller)|null $record */
        $record = $instance->resolveRouteBinding($value, $field);
        $reseller = self::currentReseller();

        // "Não existe" e "é de outro lojista" saem pela mesma porta, de propósito.
        if ($record === null || $reseller === null || ! $record->isOwnedBy($reseller)) {
            throw self::notFound($model, $value);
        }

        return $record;
    }

    private static function isPortalRoute(RoutedRoute $route): bool
    {
        return in_array(self::PORTAL_MIDDLEWARE, $route->middleware(), true);
    }

    private static function currentReseller(): ?Reseller
    {
        $user = Auth::user();

        return $user instanceof User ? $user->reseller : null;
    }

    /**
     * A mesma exceção que o binding implícito do Laravel lança — o handler a
     * traduz em 404 e nada no corpo da resposta distingue os dois casos.
     *
     * @param  class-string<Model>  $model
     * @return ModelNotFoundException<Model>
     */
    private static function notFound(string $model, string $value): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$value]);
    }
}
