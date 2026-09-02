# Plano de Revisão Técnica — CodaFácil Framework
**Data:** 2026-03-29
**Branch:** 1.1
**Revisão:** Auditoria completa — arquitetura, segurança, formatação, conformidade com guidelines

---

## Resumo Executivo

Quatro agentes independentes analisaram o codebase em paralelo:
- **Formatação / PSR-12** — Excelente (99,9% compliant, 1 issue menor)
- **Arquitetura Laravel** — Muito boa (nota A-), sem críticos, 3 warnings
- **Guidelines vs Implementação** — 92% conformidade, 2 gaps funcionais
- **Segurança** — Boa base, mas 3 críticos e 4 highs requerem ação imediata

---

## 🔴 CRÍTICO — Ação Imediata

### S1 · AgentUploadListController sem filtro por usuário
**Arquivo:** `app/Http/Controllers/AgentUploadListController.php:21-25`
**Impacto:** Qualquer usuário autenticado com acesso ao agente consegue listar uploads de **todos** os usuários do sistema — incluindo nomes de arquivos, status de processamento e mensagens de erro.
**Correção:**
```php
AgentUpload::query()
    ->where('user_id', auth()->id())  // adicionar esta linha
    ->with('user:id,name,email')
    ->orderByDesc('created_at')
    ->paginate($perPage);
```

---

### S2 · AgentUploadDeleteController sem verificação de ownership
**Arquivo:** `app/Http/Controllers/AgentUploadDeleteController.php`
**Impacto:** Usuário autenticado pode deletar uploads de outros usuários via enumeração de IDs.
**Correção:** Adicionar antes da deleção:
```php
abort_unless($upload->user_id === auth()->id(), 403);
```

---

### G1 · OrderWorkflowStatusService ausente — máquina de estado não validada
**Arquivos:** `app/Http/Controllers/OrdersController.php`, `app/Http/Controllers/Api/Mobile/OrderController.php`, `database/factories/OrderFactory.php`, `tests/Feature/CustomerProductOrderFlowTest.php`
**Impacto:** Controllers aceitam qualquer string como status sem validar transições. A guideline `design-patterns.md #3` e `common-hurdles.md #3` exigem `OrderWorkflowStatusService` para enforçar a máquina de estado.
**Problema adicional:** `OrderFactory` usa statuses incorretos (`pending`, `processing`) — spec define `awaiting_payment`, `paid`, `in_progress`.
**Correção:** Criar `app/Services/OrderWorkflowStatusService.php` com:
- Enum/constante para statuses válidos
- Matriz de transições permitidas
- Método `canTransition($from, $to): bool`
- Atualizar controllers, factory e testes

**Estado esperado:**
```
draft → awaiting_payment → paid → in_progress → completed
              ↓                        ↓
           canceled                  error
```

---

## 🟠 HIGH — Corrigir Antes de Produção

### S3 · Backend controllers sem verificação de ownership por recurso
**Arquivos:** `app/Http/Controllers/Backend/CustomerController.php`, `app/Http/Controllers/Backend/ProductController.php`
**Impacto:** Admin com permissão `access-backend` pode visualizar, editar e deletar customers/products de qualquer usuário do sistema. `BackendOrderController` já possui verificação — padronizar nos demais.
**Correção:** Adicionar verificação de ownership em `show`, `edit`, `update`, `destroy` dos dois controllers.

---

### S4 · IDs internos expostos na API Mobile
**Arquivos:** `app/Http/Controllers/Api/Mobile/CustomerController.php:76`, `app/Http/Controllers/Api/Mobile/ProductController.php:81`, `app/Http/Controllers/Api/Mobile/OrderController.php:195`
**Impacto:** IDs sequenciais expostos permitem enumeração. Orders já usam `public_number` (correto) — customers e products expõem `id` raw.
**Correção:** Implementar UUIDs ou campos `public_id` para Customer e Product, ou substituir IDs sequenciais nas respostas JSON.

---

### A1 · Audit logging incompleto — apenas UserController
**Arquivos:** `app/Services/AdminAuditLogger.php`, `app/Http/Controllers/Backend/`
**Impacto:** Alterações em customers, products e orders no backend não geram trilha de auditoria. Apenas `UserController::update()` e `UserController::updateAcl()` registram audit logs.
**Correção:** Estender `AdminAuditLogger` para cobrir operações CRUD dos demais backend controllers, ou implementar Model Observers.

---

### A2 · Lógica de negócio duplicada — sem OrderService
**Arquivos:** `app/Http/Controllers/OrdersController.php:153-225`, `app/Http/Controllers/Api/Mobile/OrderController.php`
**Impacto:** Métodos `resolveOwnedCustomer()`, `resolveOwnedProducts()` e `syncItems()` estão copiados entre os dois controllers.
**Correção:** Criar `app/Services/OrderService.php` com os métodos compartilhados e usar nos dois controllers.

---

## 🟡 MÉDIO — Corrigir em Breve

### S5 · Session encryption desabilitada por padrão
**Arquivo:** `config/session.php:50`
```php
'encrypt' => env('SESSION_ENCRYPT', false),  // deveria ser true
```
**Correção:** Alterar default para `true` ou garantir `SESSION_ENCRYPT=true` em todos os ambientes.

---

### S6 · Trust proxies configurado como wildcard `'*'`
**Arquivo:** `bootstrap/app.php:23`
```php
$middleware->trustProxies(at: '*');  // risco de spoofing de X-Forwarded-For
```
**Correção:** Especificar IPs do load balancer/proxy em produção.

---

### S7 · Rate limiting de auth mobile pode ser mais restritivo
**Arquivo:** `routes/api.php:11`
**Atual:** 5 req/min — permite brute force lento.
**Sugestão:** 3 req/5min com lockout progressivo.

---

### G2 · Documentação desatualizada — CLAUDE.md menciona Laravel 12, projeto usa Laravel 13
**Arquivo:** `CLAUDE.md`
**Também:** Campo `slug` especificado para Product mas implementação usa `sku`. Atualizar spec ou implementar campo.

---

## 🔵 INFO / Melhorias

### A3 · Diretório `app/Services/Agent/` vazio
Remover ou popular com `N8nAgentService`, `OpenAiAgentService` (conforme guideline 114, como módulo desacoplado).

---

### A4 · Sem Model Policies
Controllers usam `abort_unless()` manualmente. Considerar `App\Policies\CustomerPolicy`, `OrderPolicy`, `ProductPolicy` para melhor testabilidade.

---

### A5 · Sem interfaces/contracts nos Services
`BackendAclService`, `UserAclManager`, `AdminAuditLogger` não implementam contratos. Considerar para facilitar mocking em testes.

---

### A6 · Soft Deletes apenas em Order
`Customer` e `Product` usam hard delete. Avaliar impacto em histórico de pedidos ao deletar um customer ou product referenciado.

---

### F1 · Import não utilizado em config
**Arquivo:** `config/database.php:4`
```php
use Pdo\Mysql;  // nunca usado
```
Remover.

---

### S8 · Content Security Policy ausente
Middleware `SecurityHeaders` não define CSP header. Adicionar para mitigação de XSS como defesa em profundidade.

---

### G3 · Deploy Gates não automatizados
Gates documentados em `112-agente-deploy-gates.md` dependem de execução manual. Avaliar adicionar pre-commit hooks (Husky) ou GitHub Actions.

---

## Plano de Execução por Prioridade

| # | Item | Arquivo(s) | Severidade | Esforço |
|---|------|-----------|-----------|---------|
| 1 | Filtro de usuário em AgentUploadListController | `AgentUploadListController.php` | CRÍTICO | Baixo |
| 2 | Ownership check em AgentUploadDeleteController | `AgentUploadDeleteController.php` | CRÍTICO | Baixo |
| 3 | Criar OrderWorkflowStatusService + corrigir factory/testes | `Services/`, `OrderFactory.php`, controllers, tests | CRÍTICO | Alto |
| 4 | Ownership checks em BackendCustomerController e BackendProductController | `Backend/CustomerController.php`, `Backend/ProductController.php` | HIGH | Médio |
| 5 | Substituir IDs internos na API Mobile | `Api/Mobile/CustomerController.php`, `Api/Mobile/ProductController.php` | HIGH | Médio |
| 6 | Estender audit logging para Customer/Product/Order | `Backend/*Controller.php`, `AdminAuditLogger.php` | HIGH | Médio |
| 7 | Criar OrderService e eliminar duplicação | `Services/OrderService.php`, 2 controllers | HIGH | Médio |
| 8 | Session encryption default `true` | `config/session.php` | MÉDIO | Baixo |
| 9 | Trust proxies — especificar IPs | `bootstrap/app.php` | MÉDIO | Baixo |
| 10 | Atualizar CLAUDE.md — versões e campo slug/sku | `CLAUDE.md` | MÉDIO | Baixo |
| 11 | Remover `use Pdo\Mysql;` | `config/database.php` | INFO | Mínimo |
| 12 | Remover ou popular `Services/Agent/` | `app/Services/Agent/` | INFO | Baixo |
| 13 | Implementar CSP header | `SecurityHeaders.php` | INFO | Baixo |

---

## Pontos Fortes Confirmados

- **Formatação PSR-12** — excelente em 30+ arquivos verificados
- **Form Requests** — 27 classes, cobertura ampla
- **ACL three-layer** — corretamente implementado (Responsibility → Permission → Override)
- **public_number** — route binding correto em Orders
- **unit_price imutável** — snapshot correto no momento da seleção
- **Eager loading** — `with()` e `withCount()` usados consistentemente
- **DB::transaction()** — usado corretamente em operações complexas
- **Tokens mobile** — SHA-256 hash + expiração 30 dias
- **2FA + Google OAuth** — Fortify + Socialite corretamente integrados
- **XSS/CSRF** — nenhuma vulnerabilidade encontrada
- **SQL Injection** — queries todas parametrizadas via Eloquent
- **Security headers** — X-Frame-Options, X-Content-Type-Options, Referrer-Policy presentes

---

## Ferramentas de Verificação Pós-Correção

```bash
# Testes
php artisan test --compact

# Formatação
vendor/bin/pint && git diff --exit-code

# Análise estática
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G

# Anti-debug
grep -r "dd(\|dump(\|ray(\|var_dump(\|die(" app/ routes/ config/ resources/

# Auditoria de dependências
composer audit --locked
```
