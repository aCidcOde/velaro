# Business Rules — Regras de Negócio do Scaffold

> Regras transversais do core. Regra exclusiva de produto vai em módulo isolado.
> Fonte de verdade no código: `app/Services/OrderWorkflowStatusService.php`.

## Pedido (Order)

### Statuses válidos
`draft`, `awaiting_payment`, `paid`, `in_progress`, `completed`, `canceled`, `error`

### Matriz de transição

| De | Para (permitido) |
|----|------------------|
| `draft` | `awaiting_payment`, `canceled` |
| `awaiting_payment` | `paid`, `canceled` |
| `paid` | `in_progress`, `error` |
| `in_progress` | `completed`, `error` |
| `completed` | — (terminal) |
| `canceled` | — (terminal) |
| `error` | — (terminal) |

- Todo pedido **nasce em `draft`** (`OrderStoreRequest` restringe o status de criação).
- Transição só acontece via `OrderWorkflowStatusService::assertTransition()` / `transition()`.
- Nenhum código faz `$order->update(['status' => ...])` direto.
- Transição inválida lança `ValidationException` no campo `status`.

### Identificador externo
- `orders.public_number` é a referência externa; o `id` interno **nunca** é exposto.
- Route binding do `Order` resolve por `public_number`.
- Pedido legado sem número: `php artisan orders:backfill-public-numbers`.

### Itens
- `unit_price` em `OrderItem` é **snapshot imutável**, copiado do catálogo no momento da seleção.
- Mudança de preço no `Product` não afeta pedido já criado — só os novos.

### Ownership
- `OrderService::resolveOwnedCustomer()` e `resolveOwnedProducts()` garantem que o usuário
  só monte pedido com customer/produto que lhe pertencem. Vale para web **e** mobile.

### Retenção
- `Order` usa `SoftDeletes`. Pedido nunca é hard-deleted (trilha de auditoria).

## Acesso e ACL

- Backend exige **ambos**: `is_admin = true` **e** o gate `can:access-backend`.
- ACL é aditiva, não substitui `is_admin`.
- Camadas: `AclResponsibility` (papel) → `AclPermission` (ação) → `AclUserPermissionOverride` (exceção por usuário).
- Após adicionar permissão: `php artisan acl:sync-backend`.

## Auditoria

- Toda escrita (`update`, `destroy`) nos controllers de `/backend` gera registro em `AuditLog` via `AdminAuditLogger`.
- `AuditLog` é trilha **imutável** — não há update/delete de registro de auditoria.

## Agente (CodaFácil IA)

- Módulo **local**: chat persistido, upload de PDF e processamento assíncrono. Sem serviço externo.
- Rotas `/agente/*` exigem `auth` + `verified` + `agent` + `throttle:agent`.
- Upload isolado por usuário: listagem filtra por `user_id`; deleção valida ownership (403).
- Limites em `config/services.php` → `agent_uploads` (`AGENT_UPLOAD_MAX_KB`, `AGENT_POST_MAX_KB`).
- Upload preso em `processing`: reconciliar com `php artisan agent:sync-uploads`.

## Segurança transversal

- Todo input de usuário passa por Form Request.
- Sessão cifrada em repouso (`SESSION_ENCRYPT` default `true` em `config/session.php`).
- Header CSP aplicado por `SecurityHeaders`.
- Webhook novo compara token com `hash_equals()` — nunca `==`.
