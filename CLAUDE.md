# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**CodaFácil Scaffold** (v1.1) — base Laravel reutilizável para novos sistemas SaaS. Laravel 13 + Livewire 4 + Flux UI. Inclui autenticação completa, ACL granular, painel admin, API mobile, agente IA local assíncrono e domínio comercial mínimo (Customer → Product → Order).

Site: `codafacil.dev`

## Domínio Base

### Entidades Core
- **Customer**: Cliente/entidade alvo do sistema
- **Product**: Catálogo de itens do sistema
- **Order / OrderItem**: Pedido genérico com itens; `public_number` para referência externa
- **User**: Usuário autenticado com 2FA, Google OAuth, ACL
- **AuditLog**: Trilha imutável de ações admin
- **AgentConversation / AgentMessage / AgentUpload**: Agente IA assíncrono

### Estado do Pedido
```
draft ──→ awaiting_payment ──→ paid ──→ in_progress ──→ completed
  │              │                  │            │
  └──→ canceled ←┘                  └──→ error ←─┘

completed | canceled | error = terminais (sem saida)
```

### Regras Críticas
- `unit_price` copiado do catálogo no momento da seleção (imutável)
- Backend: `is_admin = true` + gate `can:access-backend`
- `orders.public_number` é o identificador externo (nunca expor `id` interno)

## Tech Stack

- **Backend**: PHP 8.3 (plataforma fixada em 8.3.30 no `composer.json`), Laravel 13, Fortify (auth + 2FA), Socialite (Google OAuth)
- **Frontend**: Livewire 4 + Volt, Flux UI (free), Tailwind CSS 4, Vite 7
- **Database**: MySQL (padrão do `.env.example`); SQLite em memória nos testes; PostgreSQL suportado
- **AI Agent**: CodaFácil IA **local** — chat, upload e jobs assíncronos, sem dependência externa. Ver `docs/guidelines/114-agente-gordon.md`
- **Testing**: PHPUnit 12, Larastan 3 (static analysis)
- **Dev Tools**: Pint, Pail, Sail, Laravel Boost (MCP)

## Development Commands

```bash
# Setup
composer setup                     # instala tudo de uma vez

# Dev
composer dev                       # server + queue + logs + vite

# Testes
php artisan test --compact
php artisan test --compact tests/Feature/ExampleTest.php
php artisan test --compact --filter=testName

# Qualidade
vendor/bin/pint                    # formatar
vendor/bin/phpstan analyse         # análise estática

# Database
php artisan migrate
# Use migrate:fresh/seed apenas quando houver autorizacao explicita
```

## Deploy Gates — Gatilho commit/push (Guideline 112)

> **Regra:** `commit` e `push` **somente com autorização explícita do usuário**.
> Fonte canônica: `docs/guidelines/112-agente-deploy-gates.md`

**Sequência oficial (10 gates em ordem):**
```bash
# Gate 1 — Validação do composer
composer validate --no-check-publish

# Gate 2 — Auditoria de segurança
composer audit --locked

# Gate 3 — Formatação (zero diff após pint)
vendor/bin/pint && git diff --exit-code

# Gate 4 — Análise estática
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G

# Gate 5 — Testes
php artisan test --compact

# Gate 6 — Scan de secrets
gitleaks detect --source . --no-banner --redact --config .gitleaks.toml

# Gate 7 — Prefixo de commit obrigatório
# Formato: [FEAT|FIX|CHORE|DOCS|REFACTOR|TEST|BUILD|CI|PERF|STYLE|HOTFIX] Mensagem

# Gate 8 — Anti-debug (bloqueia se encontrar em app/, routes/, config/, resources/)
composer qa:anti-debug   # usa rg em app/ routes/ config/ resources/

# Gate 9 — Trivy (somente se Dockerfile presente)
# trivy image --severity HIGH,CRITICAL --exit-code 1 <imagem>

# Gate 10 — Changelog da branch atualizado
# Formato em docs/guidelines/112-agente-deploy-gates.md
```

> `php artisan migrate:fresh`, `php artisan migrate:fresh --seed` e `php artisan db:seed` nao sao gates padrao de desenvolvimento. So use com autorizacao explicita ou em bootstrap local descartavel.

## Application Architecture

### Directory Structure

```
app/
├── Console/Commands/          # Artisan commands (ACL sync, agent, scheduler)
├── Http/
│   ├── Controllers/           # Web controllers principais
│   ├── Controllers/Backend/   # Admin controllers
│   ├── Controllers/Api/Mobile/ # Mobile API controllers (REST)
│   └── Requests/              # Form Requests
├── Jobs/                      # Background jobs
├── Livewire/                  # Full-page Livewire components
├── Models/                    # Eloquent models
├── Services/                  # Business logic
│   └── Acl/                   # ACL services
└── Support/
database/
├── migrations/
├── factories/
└── seeders/
resources/views/
├── livewire/                  # Volt components (auth, backend, orders, settings)
├── components/layouts/        # backend, app, auth layouts
└── (public views)
docs/
├── guidelines/                # Specs detalhadas
├── agentes/                   # Definições dos agentes IA
└── mobile/                    # Documentação API mobile
.claude/context/               # business-rules, common-hurdles, design-patterns
```

### Core Domain Models

| Model | Campos-chave | Notas |
|-------|-------------|-------|
| **User** | id, name, email, is_admin, is_agent, google_id, theme_preference | Fortify, 2FA, OAuth, ACL |
| **Customer** | id, user_id, name, email, document, phone | Entidade alvo — customizar por produto |
| **Product** | id, name, slug, price, description, is_active | Catálogo — customizar por produto |
| **Order** | id, public_number, user_id, customer_id, status, total_amount | SoftDeletes; public_number = ref externa |
| **OrderItem** | id, order_id, product_id, unit_price, quantity, status | Snapshot de preço imutável |
| **AuditLog** | id, actor_id, action, target_type, before, after | Trilha admin |
| **AclPermission** | id, key, module | Permissão granular |
| **AclResponsibility** / **AclUserPermissionOverride** | — | Papéis + exceções por usuário |
| **AgentConversation** | id, user_id, session_id, status (open/waiting/error) | Chat do agente |
| **AgentMessage** | id, conversation_id, role, content | Histórico do chat |
| **AgentUpload** | id, user_id, original_name, mime_type, size_bytes, status, local_path, stored_disk, stored_path, processed_at, last_checked_at, error_message, metadata | Fila de upload local, isolada por usuário |

### Controllers

- `app/Http/Controllers/` — web (home, dashboard, orders, customers, products, contact, Google OAuth, agente)
- `app/Http/Controllers/Backend/` — admin (orders, users, customers, products, ACL, audit, changelog)
- `app/Http/Controllers/Api/Mobile/` — REST (auth, dashboard, clientes, produtos, pedidos)
- Agente: single-action controllers prefixo `/agente` (message, conversation CRUD, uploads, polling)

### Services (`app/Services/`)

- `AdminAuditLogger` — auditoria
- `AgentUploadStatusService` — status de uploads
- `OrderService` — resolução de customer/produtos e sync de itens (web + mobile)
- `OrderWorkflowStatusService` — matriz de transição de status do pedido (`assertTransition()`)
- ACL: `BackendAclService`, `UserAclManager`

### Artisan Commands (`app/Console/Commands/`)

- `SyncBackendAcl` — sincroniza permissões ACL (`acl:sync-backend`)
- `SyncTemplateAgentUploadsCommand` — reconcilia status de uploads pendentes (`agent:sync-uploads`), agendado em `routes/console.php`
- `DispatchDailyDigestCommand` — digest diário por e-mail
- `BackfillOrderPublicNumbers` — preenche `public_number` em pedidos legados

### Background Jobs (`app/Jobs/`)

- `ProcessAgentMessageJob` — processa mensagem do agente
- `ProcessAgentUploadJob` — processa upload do agente

### Rotas (`routes/web.php`)

| Grupo | Middleware | Prefixo |
|-------|-----------|---------|
| Público | — | `/`, `/sobre`, `/servicos`, `/contato` |
| OAuth | — | `/auth/google/*` |
| Agent | auth + verified + agent + throttle:agent | `/agente/*` |
| Mobile API | auth:sanctum (mobile.auth) | `/api/mobile/*` |
| App | auth + verified | `/dashboard`, `/orders/*`, `/customers/*`, `/products/*` |
| Settings | auth | `/settings/*` |
| Backend | can:access-backend | `/backend/*` |

### Database Seeding

- `DatabaseSeeder` — seed inicial com admin e dados de exemplo
- Admin padrão: definir via `ADMIN_SEED_EMAIL` e `ADMIN_SEED_PASSWORD` no `.env`

## Key Patterns & Conventions

- **Models**: `fillable` arrays, return types em relationships, casts em `casts()`
- **Controllers**: finos — validação via Form Request, lógica em Service, single-action com `__invoke()`
- **Livewire**: `mount()` para init, `$this->validate()` antes de persistir, `redirect()->route()` após mutação
- **Volt** (`resources/views/livewire/`): CRUD simples | **Full** (`app/Livewire/`): lógica complexa
- **Security**: `hash_equals()` em webhooks, throttle em rotas de agente, Form Request em todo input
- **Async**: APIs externas sempre via queue jobs (nunca síncronas em controller)
- **Order status**: sempre via service, nunca `$order->update(['status' => ...])`

### Cabeçalho de autoria (obrigatório em todo arquivo novo)

Todo arquivo `.php` e `.blade.php` criado no projeto nasce com o cabeçalho abaixo, logo após a
abertura do arquivo. A descrição é **uma linha em pt-BR sem acentos**, dizendo o que o arquivo faz.

```php
<?php

/*
[Modulo: app/Http/Controllers/Auth]
@Author: André Gomes ( @acidcode )
@since 2026-02-22
Controla o login social com Google (redirect para OAuth e callback de autenticacao).
*/

namespace App\Http\Controllers\Auth;
```

Em Blade o mesmo bloco vai dentro de `{{-- --}}`; em arquivos de configuração (`.neon`, `.sh`)
cada linha é prefixada com `#`. `[Modulo: ...]` recebe o **diretório** do arquivo, e `@since` a
data de criação. Arquivos `.md` não levam cabeçalho.

> Padrões completos: `.claude/context/design-patterns.md`

## Common Development Tasks

### Novo Livewire Component
```bash
php artisan make:livewire ComponentName   # full component
php artisan make:volt component-name      # volt component
```

### Nova Migration
```bash
php artisan make:migration create_table_name_table
```

### Novo Model + Scaffold
```bash
php artisan make:model ModelName -mfc    # model + migration + factory + controller
```

### Debug
```bash
php artisan pail           # logs em tempo real
php artisan queue:failed   # jobs com erro
```
> Nunca deixar `dd()`, `dump()`, `var_dump()`, `ray()`, `die()` no código commitado.

## Important Files

- `routes/web.php` — todas as rotas
- `app/Models/` — models do domínio
- `database/migrations/` — migrações
- `app/Services/` — lógica de negócio
- `app/Jobs/` — processamento assíncrono
- `.env` / `.env.example` — configuração
- `public/logo.webp` — logo do sistema (substituir no clone)
- `DESIGN.md` — design system (substituir a marca no clone)
- `pipeline.sh` — deploy (ajustar `DEPLOY_*_DIR` e `SUPERVISOR_*_PROGRAM` no clone)
- `.claude/launch.json` — configs de dev server (Laravel, Vite, queue, Pail)

## Environment Configuration

```bash
# Core
APP_NAME="CodaFácil"
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=codafacil
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

# Agente (CodaFácil IA — local, sem serviço externo)
AGENT_RESPONSE_DELAY_SECONDS=1
AGENT_WAIT_TIMEOUT=30
AGENT_UPLOAD_MAX_KB=20480
AGENT_POST_MAX_KB=30720
AGENT_UPLOAD_DISK=local
AGENT_UPLOAD_STALE_PROCESSING_SECONDS=900
```

## Git Workflow

Feature branches → merge em `main` antes do deploy. `main` é o tronco do scaffold (criada a partir da `1.1`); `master` permanece como histórico legado.

## Guidelines (`docs/guidelines/`)

| Arquivo | Quando usar |
|---------|-------------|
| `00-master-guideline.md` | Visão geral do scaffold — **ler primeiro** |
| `110-agente-revisao-qualidade-seguranca.md` | Revisão técnica e segurança |
| `111-agente-darkmode.md` | Dark mode |
| `112-agente-deploy-gates.md` | **Gates de deploy (fonte canônica)** |
| `113-landing-page-servicos.md` | Landing pages |
| `114-agente-gordon.md` | **Agente IA (spec canônica)** |
| `116-governanca-portavel-commit-push.md` | **Governança commit/push (versão portável)** |

## Agentes (`docs/agentes/`)

| Agente | Guia operacional | Uso |
|--------|------------------|-----|
| `business-agent.md` | — | Regra de negócio, validações e critérios de aceite |
| `developer-agent.md` | — | Add-ons do executor técnico + gatilhos operacionais |
| `review-agent.md` | `110` | Revisão de qualidade e segurança pós-feature |
| `darkmode-agent.md` | `111` | Diagnóstico e correção visual dark/light |
| `deploy-agent.md` | `112` | Readiness técnica e gates pré-deploy |
| `gordon-agent.md` | `114` | Wrapper do módulo CodaFácil IA |
| `README.md` | — | Índice consolidado e regra anti-duplicação |

**Gatilhos operacionais (skill-like):**
- `commit/push` → executa a rotina da guideline `112`
- `criar landing page` → executa a rotina da guideline `113`

## Skills (`.claude/skills/`)

- `tailwind/` — Tailwind v4 CSS-first, tokens `@theme`, dark mode
- `frontend-design/` — construção de interface com qualidade de design (mobile-first)

## Design System

- `DESIGN.md` — tokens de cor/tipografia/espaçamento e regras nomeadas. **Template: trocar a marca ao clonar.**

## Contexto Adicional (`.claude/context/`)

- `business-rules.md` — regras de negócio do scaffold (matriz de status, ownership, ACL)
- `common-hurdles.md` — problemas frequentes com solução
- `design-patterns.md` — padrões arquiteturais do projeto

## MCP Tools Disponíveis

- **Laravel Boost**: schema DB, Artisan, Tinker, logs, docs Laravel

---

## Pipeline de Manutenção

### Diário
```bash
php artisan queue:failed   # jobs com erro
php artisan pail           # logs em tempo real
```

### Semanal
```bash
composer audit             # auditoria de segurança
# Revisar AuditLog no /backend
# Verificar AgentUploads em 'processing' há mais de 1h
```

### Pré-Release
Executar 10 gates de `docs/guidelines/112-agente-deploy-gates.md`.

### Pós-Deploy
- Verificar queue worker rodando
- Checar `/backend` — zero failed jobs novos
- Testar fluxo: login → criar pedido → agente responder

---

## Checklist Pós-Implementação

**Código**
- [ ] Testes passando (`php artisan test --compact`)
- [ ] Pint sem diff (`vendor/bin/pint && git diff --exit-code`)
- [ ] PHPStan sem erros
- [ ] Sem `dd()`, `dump()`, `var_dump()`, `ray()`, `die()`

**Schema & Dados**
- [ ] Migration adicionada se schema mudou
- [ ] Seeder atualizado se novos dados de catálogo
- [ ] Factory criada se novo model

**Integração**
- [ ] Rota em `routes/web.php` com middleware correto
- [ ] Form Request para todo input de usuário
- [ ] Novas env vars em `.env.example`
- [ ] Job criado se operação assíncrona

**Documentação**
- [ ] `CLAUDE.md` atualizado se novos models/services/rotas
- [ ] Changelog em `docs/changelog_X_Y.md` (formato guideline 112: `**Resumo:**` + `**O que foi feito:**`, sem citar arquivos/classes/rotas)
- [ ] Guideline em `docs/guidelines/` se feature complexa

**Commit/Push** *(somente com autorização explícita)*
- [ ] 10 gates da guideline 112 aprovados
- [ ] Prefixo: `[FEAT|FIX|CHORE|DOCS|REFACTOR|TEST|BUILD|CI|PERF|STYLE|HOTFIX]`
- [ ] Descrição do commit em `pt-BR` (gate 8)
- [ ] Changelog com bloco único `## Fechamento Técnico` (`🧪 📊 🛡️ 📈` / `🟢⚪🔵`)
- [ ] Status final declarado: `APROVADO` ou `BLOQUEADO`
