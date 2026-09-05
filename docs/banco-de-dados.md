# Banco de dados Velaro B2B

Registro do schema construído a partir de [`docs/telas/`](telas/README.md) e do diagrama
[`docs/mockups/er-banco.html`](mockups/er-banco.html), com as decisões que fecharam as 18 lacunas
declaradas em [`_build_er.py`](mockups/_build_er.py).

**Estado:** 54 migrations aplicadas · 71 tabelas · 101 chaves estrangeiras.

---

## 1. Decisões estruturais

### 1.1 O core deixa de ser imutável

A regra "nenhuma tabela do núcleo é alterada" foi **abandonada**. Ela já era furada pela própria
documentação (`users.reseller_id` na tela 0, `products.slug` nas telas 1.3 e 3.7), e mantê-la exigiria
um JOIN em toda rota pública e um pivô N:N que nenhuma tela exercita.

Consequência direta: **as três tabelas de extensão 1:1 deixam de existir**. Seus campos foram
absorvidos pela tabela do core correspondente.

| Tabela declarada na doc | Onde os campos moram agora |
|---|---|
| `product_attributes` | `products` |
| `order_velaro_details` | `orders` |
| `customer_velaro_details` | `customers` |

> A documentação foi realinhada: `_mapa.py`, `_build_er.py` e os 31 documentos de tela já
> refletem o schema real. As três menções que sobram — aqui e no rodapé de
> [`docs/telas/README.md`](telas/README.md) — são registro histórico da decisão, não declaração
> de tabela existente.
>
> ⚠️ **`docs/telas/*.md` são arquivos gerados**, não escritos à mão: `_build_docs.py` os produz a
> partir de `_mapa.py` + `_notas.md`. Corrigir tabela ou campo de tela direto no `.md` é trabalho
> perdido — a próxima execução do gerador sobrescreve. A correção entra no `_mapa.py`.

### 1.2 Status do pedido — quem é canônico

`operational_status` e `payment_status` (em `orders`) são a verdade. São **independentes entre si**,
como manda a regra 2 da tela 3.6.

- `operational_status`: `registrado → pagamento_confirmado → producao_andamento → producao_finalizada → em_transporte → pronto_retirada → retirado`
- `payment_status`: ciclo financeiro do lote, independente do operacional

`orders.status` (o campo do scaffold) permanece na tabela como **espelho derivado**, mantido só para
compatibilidade com `OrderWorkflowStatusService`. Nada no módulo Velaro deve lê-lo como autoridade.

### 1.3 NF-e por lote, com rateio por pedido

O conflito entre a tela 3.5 (emite dentro do lote) e a 2.4 (coluna NF-E por pedido) foi resolvido
atendendo as duas: `invoices.batch_id` é a chave do documento fiscal, e `invoice_items` liga a nota
aos pedidos do lote com o valor rateado. A 2.4 baixa a NF do lote a que o pedido pertence.

### 1.4 Escopo de propriedade — `user_id` × `reseller_id`

`reseller_id` é o eixo de escopo do módulo Velaro. `user_id` (do scaffold) foi mantido, mas
**deixou de ser destrutivo**: em `products`, `customers` e `orders` ele passou de
`NOT NULL / ON DELETE CASCADE` para `nullable / ON DELETE SET NULL`.

Isso elimina o risco descrito na lacuna 15 — apagar um usuário não apaga mais a carteira de clientes
nem o histórico de pedidos de um lojista inteiro.

Política de exclusão adotada:

| Relação | Regra | Por quê |
|---|---|---|
| `orders.reseller_id` | `RESTRICT` | Pedido é registro fiscal; não some junto com o cadastro |
| `customers.reseller_id` | `CASCADE` | A carteira pertence ao lojista (`resellers` tem SoftDeletes, então o hard delete é ato deliberado de apagamento LGPD) |
| `*.user_id` | `SET NULL` | Usuário é login, não dono de dado de negócio |

---

## 2. As 18 lacunas — como cada uma foi fechada

| # | Lacuna | Resolução |
|---|--------|-----------|
| 1 | `audit_logs` não declarado por nenhuma tela | Nenhuma ação de schema — a tabela já existe no core. Falta declarar **o que grava** em cada tela |
| 2 | `products.slug` não existe no core | Coluna `slug` única em `products` (decisão 1.1) |
| 3 | Não há tabela de remessa | **`shipments`** + `orders.shipment_id`: transportadora, rastreio, liberação logística com ator, expedição, previsão e entrega |
| 4 | Pedido sem frete nem desconto | Colunas `subtotal_amount`, `engraving_amount`, `shipping_amount`, `discount_amount` em `orders` + **`order_promotions`** ligando campanha a pedido |
| 5 | Granularidade da NF em conflito | `invoices.batch_id` + **`invoice_items`** (decisão 1.3) |
| 6 | `notification_logs` sem alvo | FKs nullable para `orders`, `resellers` e `customers` |
| 7 | `retirado_por` sem alvo possível | Três colunas: `retirado_por` (nome livre), `retirado_por_documento` e `retirado_por_customer_id` (FK opcional). Cobre tanto o cliente da carteira quanto terceiro identificado. `order_batches` ganhou os mesmos campos para a retirada por lote inteiro |
| 8 | Aceites do lojista sem tabela | **`reseller_consents`**: tipo, versão do texto, IP, user-agent, concessão e revogação — requisito LGPD da regra 2 da tela 1.4 |
| 9 | Configurações da vitrine não cabem | Toggles em `reseller_stores` (`show_prices`, `pickup_only`, `payment_in_store`, `own_brand_only`, `hide_supplier_brand`) + **`reseller_store_categories`** (categorias visíveis) e **`reseller_store_products`** (destaques) |
| 10 | Margens do lojista sem lugar | **`reseller_price_settings`** 1:1: modelo, multiplicador, margem global/mín/ideal/máx, arredondamento e os três toggles |
| 11 | Estoque não tem local | **`stock_locations`** + `stock_items.stock_location_id`. A relação com `product_variants` passou a `UNIQUE(product_variant_id, stock_location_id)` — deixa de ser 1:1, como a tela 3.4 exige |
| 12 | Solicitação de produção não é entidade | **`production_requests`**: quantidade pedida × entregue, status, prioridade, prazo e solicitante |
| 13 | `support_tickets` não cobre a tela 3.12 | **`support_tags`** + **`support_ticket_tag`**, colunas `environment`/`browser`/`os`/`ip_address`, e **`support_status_events`** para a timeline de transições |
| 14 | Nada cobre ajuda nem lead | **`help_categories`**, **`help_articles`** (FAQ, guias e vídeos) e **`contact_leads`** |
| 15 | Duas escalas de escopo | Resolvido na decisão 1.4 |
| 16 | Três status de pedido | Resolvido na decisão 1.2 |
| 17 | `users.reseller_id` é mutação do core | Aceito como coluna (decisão 1.1) |
| 18 | Detalhes literais sem coluna | `resellers.registration_type`, `customers.person_type`, `products.prazo_entrega_dias` e `products.is_made_to_order`, e **`favorites`** para o ♡ da vitrine e do catálogo público |

---

## 3. Inventário por domínio

### Catálogo e estoque
`collections` · `categories` · `materials` · `finishes` · `products`* · `product_variants` ·
`product_images` · `product_revisions` · `stock_locations` · `stock_items` · `stock_movements` ·
`production_requests`

### Promoções
`promotions` · `promotion_rules` · `promotion_products` · `promotion_audiences`

### Revendedores e clientes
`resellers` · `reseller_documents` · `reseller_cnaes` · `reseller_verifications` ·
`reseller_status_events` · `reseller_consents` · `customers`* · `customer_consents`

### Pedidos
`orders`* · `order_items`* · `order_item_engravings` · `order_status_events` · `order_promotions`

### Financeiro B2B
`order_batches` · `payments` · `invoices` · `invoice_items` · `shipments`

### Vitrine white-label
`reseller_stores` · `reseller_store_categories` · `reseller_store_products` ·
`reseller_price_settings` · `reseller_price_rules` · `favorites`

### Suporte
`support_tickets` · `support_messages` · `support_attachments` · `support_tags` ·
`support_ticket_tag` · `support_status_events`

### Configuração e conteúdo
`settings` · `notification_logs` · `report_schedules` · `report_exports` ·
`help_categories` · `help_articles` · `contact_leads`

### Acesso
`users`* · `acl_permissions` · `acl_responsibilities` · `acl_responsibility_permission` ·
`acl_user_responsibility` · `acl_user_permission_overrides` · `audit_logs`

\* tabela do core que recebeu colunas Velaro.

---

## 4. O que ainda não existe

O schema está de pé, mas a camada de aplicação não:

- **Models** — nenhum Eloquent para as 49 tabelas novas; `Product`, `Order` e `Customer` não conhecem os campos novos
- **Factories e seeders** — sem massa de teste e sem catálogo inicial (coleções, categorias, materiais, acabamentos, locais de estoque)
- **Permissões ACL** — as ~36 chaves `velaro.*` levantadas das telas não estão em `acl_permissions`; falta estendê-las em `SyncBackendAcl`
- **Settings iniciais** — `company.*`, `contact.*`, `about.*`, `gravacao.max_chars` e `gravacao.preco` sem seed
- **Enums** — os valores de `status`, `operational_status`, `payment_status`, `type` etc. estão como `string` no banco; a validação vive na aplicação
- **Policy `ResellerScope`** — o escopo por `reseller_id` é FK, ainda não é regra de leitura
