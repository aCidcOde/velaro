<?php

/*
[Modulo: app/Support]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Desliga o escopo automatico do route model binding nas rotas da vitrine: quem decide o que a loja expoe e o service, nao uma relacao.
*/

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerStore;
use App\Services\Vitrine\VitrineCatalogoService;
use App\Services\Vitrine\VitrinePedidoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Route as RoutedRoute;
use Illuminate\Support\Facades\Route;

/**
 * As rotas da vitrine são aninhadas — `/loja/{store:slug}/produto/{product:slug}` —
 * e o Laravel, ao ver um filho com chave própria dentro de um pai que é model,
 * liga sozinho o **binding escopado**: ele resolve `{product}` por
 * `$store->products()`, a relação de mesmo nome.
 *
 * Na vitrine esse automatismo entrega a resposta errada. `ResellerStore::products()`
 * é a **curadoria** (`reseller_store_products`), e a regra da tela 2.9 diz que
 * curadoria vazia significa "não escolhi": a loja mostra o catálogo ativo
 * inteiro. Com o escopo automático ligado, a loja que ainda não curou nada
 * resolveria zero produtos e **toda** ficha responderia 404 — inclusive as peças
 * que a própria grade acabou de listar.
 *
 * Não dá para consertar isso do lado da relação: "tudo o que está ativo quando
 * não há curadoria" não é expressável como uma relação Eloquent. Então o filho
 * volta a ser resolvido da forma simples, e quem aplica a regra é
 * {@see VitrineCatalogoService}, que reabre a peça dentro do catálogo visível
 * daquela loja e devolve 404 quando ela não está lá — a mesma resposta, pelo
 * caminho certo.
 *
 * O binder é global por nome de parâmetro, e `{product}` também aparece no site
 * público e no Painel Master. Por isso ele só age nas rotas do grupo `vitrine.`;
 * nas demais devolve o valor cru e o binding implícito do Laravel segue como
 * sempre seguiu.
 *
 * ## O comprovante do pedido
 *
 * `/loja/{store:slug}/pedido/{order:public_number}` cai na mesma armadilha, e
 * pior: `ResellerStore` não tem relação `orders()`, então o escopo automático
 * nem 404 devolveria — estouraria com `RelationNotFoundException`. Por isso
 * `{order}` também está aqui, e quem confere se o pedido é daquela loja (e se
 * ele nasceu na vitrine, e não no B2B do lojista) é
 * {@see VitrinePedidoService}.
 *
 * ## Convivência com o escopo do portal
 *
 * `{order}` já tinha dono: {@see ResellerScope::bindRouteParameters()} o resolve
 * dentro da carteira do lojista autenticado. Dois `Route::bind()` para o mesmo
 * parâmetro não somam — o último apaga o primeiro, e o portal perderia o escopo
 * em silêncio. Então o binder registrado aqui **guarda o anterior** e devolve a
 * ele tudo o que não for rota da vitrine: a ordem em que os dois são registrados
 * no `AppServiceProvider` deixa de importar.
 */
final class VitrineRouteBinding
{
    /**
     * Parâmetros que a vitrine declara como filhos de `{store:slug}`.
     *
     * @var array<string, class-string<Model>>
     */
    private const CHILD_PARAMETERS = [
        'product' => Product::class,
        'order' => Order::class,
    ];

    /**
     * Prefixo dos nomes das rotas do ambiente, como `routes/velaro.php` declara.
     *
     * O grupo é identificado pelo nome, e não por middleware: a vitrine é
     * pública e não carrega middleware próprio — é justamente o ambiente que
     * qualquer um abre sem login.
     */
    private const ROUTE_PREFIX = 'vitrine.';

    /**
     * Liga a resolução simples dos parâmetros filhos da vitrine.
     *
     * Roda antes do binding implícito (o `SubstituteBindings` resolve primeiro
     * os binders explícitos), e o parâmetro que já chega como model faz o
     * escopo automático ser pulado.
     */
    public static function bindChildParameters(): void
    {
        foreach (self::CHILD_PARAMETERS as $parameter => $model) {
            // Quem já resolvia este parâmetro continua resolvendo fora da
            // vitrine. Sem esta corrente, registrar `{order}` aqui desligaria o
            // escopo do portal — e o pedido de um lojista abriria no painel do
            // outro.
            $anterior = Route::getBindingCallback($parameter);

            Route::bind(
                $parameter,
                static fn (string $value, RoutedRoute $route): mixed => self::resolve($model, $parameter, $value, $route, $anterior),
            );
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  (callable(string, RoutedRoute): mixed)|null  $anterior
     */
    private static function resolve(string $model, string $parameter, string $value, RoutedRoute $route, ?callable $anterior = null): mixed
    {
        // Fora da vitrine manda quem mandava: o binder que já existia, se
        // existia, e senão o valor cru — que faz o binding implícito assumir e
        // deixa site público, portal e Master resolvendo como antes.
        if (! self::isVitrineRoute($route)) {
            return $anterior === null ? $value : $anterior($value, $route);
        }

        $instance = new $model;
        $field = $route->bindingFieldFor($parameter) ?? $instance->getRouteKeyName();
        $record = $instance->resolveRouteBinding($value, $field);

        if (! $record instanceof Model) {
            // A mesma exceção do binding implícito — o handler traduz em 404, e
            // a loja não distingue "não existe" de "não está nesta vitrine".
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        return $record;
    }

    private static function isVitrineRoute(RoutedRoute $route): bool
    {
        return str_starts_with((string) $route->getName(), self::ROUTE_PREFIX);
    }
}
