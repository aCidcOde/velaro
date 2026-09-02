# Design Patterns — Padrões do Scaffold

> Escopo: apenas o que existe no core do CodaFácil Scaffold.
> Padrão de produto específico não entra aqui — vai em módulo isolado.

## 1. Single-Action Controllers
Controllers de agente e webhooks usam `__invoke()`. Um controller = uma responsabilidade.

## 2. Form Request Validation
Todo input de usuário passa por `App\Http\Requests\*` antes de chegar ao controller. Controllers nunca chamam `$request->validate()` diretamente.

## 3. State Machine para Orders
`OrderWorkflowStatusService` enforça as transições válidas dos 7 statuses do domínio. Nenhum código faz `$order->update(['status' => ...])` sem passar pelo service — use `assertTransition()`.

## 4. Price Snapshot (Imutabilidade)
`unit_price` em `OrderItem` é copiado do catálogo no momento da seleção e nunca mais alterado. Protege contra mudanças de preço retroativas.

## 5. Service Layer
Lógica de negócio em `app/Services/`. Controllers são finos (recebem request, chamam service, retornam response). Services são gordos (toda a lógica de domínio).

## 6. Service Compartilhado entre Web e Mobile
`OrderService` centraliza `resolveOwnedCustomer()`, `resolveOwnedProducts()` e `syncItems()`, consumido tanto por `OrdersController` quanto por `Api/Mobile/OrderController`. Regra de pedido nova entra no service, não duplicada nos dois controllers.

## 7. Job-Based Async
Operação pesada ou com I/O externo é sempre offloaded para queue job. Nunca chamar API externa síncronamente em controller. No core: `ProcessAgentMessageJob` e `ProcessAgentUploadJob`.

## 8. Three-Layer ACL
`AclResponsibility` (papel) → `AclPermission` (ação granular) → `AclUserPermissionOverride` (exceção por usuário). Verificação via `BackendAclService`.

## 9. Soft Deletes para Auditoria
`Order` usa `SoftDeletes`. Pedidos nunca são hard-deleted, para preservar trilha de auditoria e histórico.

## 10. Audit Log em Toda Escrita do Backend
`Backend/{User,Customer,Product,Order}Controller` registram `update` e `destroy` via `AdminAuditLogger`. Controller novo no backend nasce com audit log.

## 11. Timing-Safe Webhook Validation
Ao adicionar qualquer webhook, comparar token com `hash_equals()` — nunca `==`. Previne timing attack. (O core hoje não expõe webhook de pagamento; a regra vale para quando o produto adicionar um.)

## 12. Volt Inline Components
Componentes CRUD simples colocalizados como Blade files com PHP inline (`resources/views/livewire/`). Reservar full components (`app/Livewire/`) para lógica complexa com estado multi-step.
