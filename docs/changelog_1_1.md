# Changelog 1.1

## Principais implementações

- Identidade visual Velaro consolidada e aprovada para os quatro ambientes da plataforma B2B.
- Todas as telas do escopo contratado desenhadas, navegáveis e validadas em desktop, tablet e mobile.
- Documentação por tela com campos, permissões, regras de negócio e critérios de aceite.
- Sistema de design registrado como referência única e já aplicado à aplicação em funcionamento.

### 2026-09-02 · FEAT · Identidade visual aplicada à aplicação

**Resumo:** A identidade aprovada saiu da prancheta e passou a valer no produto em
funcionamento. Login, painéis, site e comunicações transacionais agora falam a mesma
língua visual da marca.

**O que foi feito:** A paleta e a tipografia aprovadas foram aplicadas ao sistema, de
forma que todos os componentes já existentes passassem a exibir a marca sem precisar
ser refeitos. As cores que estavam escritas diretamente nas telas, e por isso escapavam
do sistema, foram localizadas e substituídas — incluindo a lateral do acesso, a área dos
gráficos, o site institucional e os e-mails automáticos. O contraste foi medido par a par
nos dois temas e atende ao critério de acessibilidade adotado. O nome da marca deixou de
estar fixo nos títulos das telas de acesso e passou a vir da configuração do sistema.

### 2026-09-02 · FEAT · Design system e telas da plataforma B2B

**Resumo:** A plataforma B2B da Velaro ganhou uma referência visual e funcional completa,
cobrindo todo o escopo contratado, para validação antes da implementação. A marca e a
operação passaram a conviver na mesma experiência sem que uma prejudique a outra.

**O que foi feito:** A identidade visual foi consolidada e aprovada, com paleta,
tipografia e regras de uso definidas a partir da arte oficial da marca. Todas as telas
do escopo contratado foram desenhadas e conectadas entre si, permitindo percorrer os
quatro ambientes de ponta a ponta: a captação e aprovação de lojistas, o portal do
parceiro, a vitrine com a marca do revendedor e o painel interno da fábrica. Cada tela
recebeu documentação própria com campos, permissões, regras de negócio e critérios de
aceite, apoiada na transcrição do material aprovado pelas partes. Campos e etapas
exigidos em contrato que não apareciam no material original foram identificados e
incorporados. O comportamento foi validado em desktop, tablet e mobile.

## Auditoria Técnica — Segurança, Arquitetura e Workflow de Pedidos

A versão `1.1` aplica as correções identificadas em auditoria técnica completa do scaffold (4 agentes em paralelo: segurança, arquitetura, formatação e conformidade com guidelines).

---

## Correções de Segurança

### S1 — AgentUploadListController: isolamento por usuário
- Adicionado filtro `where('user_id', auth()->id())` na query de uploads
- **Antes:** qualquer usuário com acesso ao agente via via uploads de todos os usuários do sistema
- **Depois:** cada usuário vê apenas seus próprios uploads

### S2 — AgentUploadDeleteController: verificação de ownership
- Adicionado `abort_unless($upload->user_id === auth()->id(), 403)` antes da deleção
- **Antes:** usuário autenticado podia deletar uploads de outros via enumeração de ID
- **Depois:** tentativa de deletar upload alheio retorna 403

### S5 — Session encryption habilitada por padrão
- `config/session.php`: `SESSION_ENCRYPT` default alterado de `false` para `true`
- Sessões armazenadas em banco de dados passam a ser cifradas em repouso

### F1 — Import não utilizado removido
- `config/database.php`: removido `use Pdo\Mysql;` que não era referenciado

### S8 — Content-Security-Policy header
- `SecurityHeaders.php`: adicionado header CSP como defesa em profundidade contra XSS
- Política: `default-src 'self'` com permissões explícitas para script, style, img, font e connect

---

## Arquitetura — Novos Services

### OrderWorkflowStatusService
- **Arquivo:** `app/Services/OrderWorkflowStatusService.php`
- Define os 7 statuses válidos do domínio: `draft`, `awaiting_payment`, `paid`, `in_progress`, `completed`, `canceled`, `error`
- Implementa matriz de transições permitidas conforme spec
- Métodos: `isValid()`, `canTransition()`, `assertTransition()`, `transition()`
- `assertTransition()` enforçado no `update` de pedidos (web e mobile)

### OrderService
- **Arquivo:** `app/Services/OrderService.php`
- Elimina duplicação entre `OrdersController` e `Api/Mobile/OrderController`
- Centraliza: `resolveOwnedCustomer()`, `resolveOwnedProducts()`, `syncItems()`

---

## Arquitetura — Audit Logging Expandido

- `Backend/CustomerController`: audit log em `update` e `destroy`
- `Backend/ProductController`: audit log em `update` e `destroy`
- `Backend/OrderController`: audit log em `update` e `destroy`
- **Antes:** apenas `UserController` registrava eventos no `AuditLog`
- **Depois:** todas as operações de escrita do backend geram trilha de auditoria

---

## Conformidade com Spec

### Statuses de pedido corrigidos
- `OrderStoreRequest`: status restrito a `['draft']` (novos pedidos sempre iniciam em draft)
- `OrderUpdateRequest`: statuses alinhados com a spec (`awaiting_payment`, `paid`, `in_progress`, etc.)
- `OrderFactory`: substituídos `pending`/`processing` pelos valores canônicos da spec
- `CustomerProductOrderFlowTest`: corrigido de `submitted` → `draft` e progressão válida `draft → awaiting_payment`

---

## Governança Operacional de Commit e Banco Local

- `commit/push` passa a exigir o formato obrigatório `[TIPO] resumo curto`
- Tipos aceitos: `FEAT`, `FIX`, `CHORE`, `DOCS`, `REFACTOR`, `TEST`, `BUILD`, `CI`, `PERF`, `STYLE`, `HOTFIX`
- `php artisan migrate:fresh`, `php artisan migrate:fresh --seed` e `php artisan db:seed` deixam de ser rotina padrão de desenvolvimento
- O bootstrap seguro da base passa a priorizar `composer setup` ou `php artisan migrate --force --no-interaction`
- O reset com seed fica restrito a banco local descartável e execução explícita

---

## Gates Executados (Guideline 112)

| Gate | Comando | Resultado |
|------|---------|-----------|
| 1 | `vendor/bin/pint --dirty` | ✅ pass |
| 2 | `php artisan test --compact` | ✅ 66 passed, 281 assertions |
| 3 | `php artisan route:list --except-vendor` | ✅ 85 rotas sem erros |
| 4 | `php artisan migrate:fresh --seed --no-interaction` | ✅ seed completo |

---

## Referência operacional

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan acl:sync-backend --no-interaction
composer dev
```

---

## Manutenção — 2026-09-02 — Atualização de dependências e sincronização de documentação

Retomada do projeto após ~5 meses sem manutenção (último commit em 2026-03-30).

### Branch

- Criada a branch `main` a partir da `1.1` (que já continha toda a `master`)
- `main` passa a ser o tronco do scaffold; `master` fica como histórico legado

### Dependências — backend

`composer update` dentro dos constraints do `composer.json` (nenhum bump de major):

| Pacote | De | Para |
|--------|----|------|
| laravel/framework | 13.2.0 | 13.30.1 |
| livewire/flux | 2.13.0 | 2.18.0 |
| livewire/volt | 1.10.4 | 1.11.2 |
| laravel/fortify | 1.36.1 | 1.39.0 |
| laravel/socialite | 5.25.0 | 5.31.0 |
| laravel/boost | 2.4.1 | 2.7.0 |
| laravel/sail | 1.54.0 | 1.67.0 |
| laravel/pint | 1.29.0 | 1.30.5 |
| laravel/pail | 1.2.6 | 1.2.7 |
| laravel/tinker | 3.0.0 | 3.0.2 |
| larastan/larastan | 3.9.3 | 3.11.0 |
| phpunit/phpunit | 12.5.14 | 12.5.34 |
| mockery/mockery | 1.6.12 | 1.6.15 |
| nunomaduro/collision | 8.9.1 | 8.9.5 |
| mercadopago/dx-php | 3.8.0 | 3.16.0 |

- **Antes:** 40 advisories de segurança abertos (`composer audit --locked`), incluindo severidade *high* em `laravel/framework`, `guzzlehttp/guzzle`, `league/commonmark`, `symfony/mime` e `symfony/http-kernel`
- **Depois:** zero advisories

### Dependências — frontend

- `node_modules` estava ausente; reinstalado
- `npm update` dentro dos ranges do `package.json`
- **Antes:** 11 vulnerabilidades (2 critical, 8 high, 1 moderate) — `shell-quote`, `axios`, `nanoid`, `postcss`, `rollup`, `vite`, `form-data`, `picomatch`, `browserslist`, `follow-redirects`
- **Depois:** zero vulnerabilidades
- `npm run build` validado

### Documentação sincronizada com o código

`CLAUDE.md` documentava um stack que não existe mais:

| Item | Documentado | Real |
|------|-------------|------|
| Laravel | 12 | 13 |
| Livewire | 3 | 4 |
| PHP | 8.4+ | 8.3 (plataforma fixada em 8.3.30) |
| PHPUnit | 11 | 12 |
| Banco padrão | SQLite | MySQL (SQLite só nos testes) |
| Agente | n8n + OpenAI + Google Drive | CodaFácil IA local, sem serviço externo |

- Removidos da doc os services inexistentes `N8nAgentService`, `GoogleDriveAgentService`, `OpenAiAgentService`, `AgentToolService`
- Removidos os jobs inexistentes `SendN8nAgentMessageJob`, `UploadAgentFileToGoogleDriveJob`
- Documentados os services reais `OrderService` e `OrderWorkflowStatusService`
- Documentados os commands `BackfillOrderPublicNumbers` e `SyncAgentDriveUploads`
- Bloco de env: removidas as vars mortas `N8N_*`, `OPENAI_*`, `GOOGLE_DRIVE_AGENT_*`; documentadas as vars reais `AGENT_*`
- Diagrama de status do pedido corrigido para refletir a matriz real (`draft → canceled` e `paid|in_progress → error` faltavam)
- Gate 8 alinhado ao que o `composer.json` executa de fato (`rg`, via `composer qa:anti-debug`)

### Guidelines

- **112** reescrita como fonte canônica de fato: a guideline se declarava dona dos "10 gates" mas continha uma lista de 4 itens divergente do `composer.json`. Agora os 10 gates estão mapeados 1:1 nos scripts `qa:*`, com seção nova de manutenção de dependências
- **00-master-guideline**: banco padrão corrigido, ponto de partida do clone passa a ser a branch `main` (v1.1), regra nova de zero advisories
- **114**: removida a amarração à versão `1.0`; reforçado que o agente é local e sem dependência externa

### Contexto (`.claude/context/`)

Os arquivos descreviam um **produto anterior** (carteira com ledger, emissão de certidões InfoSimples, agente "Gordon", webhook MercadoPago) — nada disso existe no scaffold:

- `design-patterns.md`: removidos os padrões 5 (Ledger Wallet), 8 (`InfoSimplesJobStatus`), 9 (Dynamic Required Fields) e 12 (session fallback do Gordon); adicionados "Service compartilhado web/mobile" e "Audit log em toda escrita do backend"
- `common-hurdles.md`: removidos os problemas de n8n, Google Drive, MercadoPago e InfoSimples; adicionados os de reconciliação de upload e de dependências desatualizadas
- `business-rules.md`: **criado** — o `CLAUDE.md` referenciava o arquivo, mas ele não existia. Documenta a matriz de transição real, ownership, ACL, auditoria e segurança transversal

### Gates executados

| Gate | Comando | Resultado |
|------|---------|-----------|
| 1 | `composer validate --no-check-publish` | ✅ valid |
| 2 | `composer audit --locked` | ✅ zero advisories (era 40) |
| 3 | `vendor/bin/pint --test` | ✅ passed |
| 4 | `vendor/bin/phpstan analyse` | ✅ no errors |
| 5 | `php artisan test --compact` | ✅ 66 passed, 281 assertions |
| 8 | `composer qa:anti-debug` | ✅ sem debug calls |
| — | `npm audit` | ✅ zero vulnerabilidades (era 11) |
| — | `npm run build` | ✅ build ok |

### Pendências identificadas

> Os itens 1 a 4 foram resolvidos na manutenção de remoção de código morto, mais abaixo neste changelog. O item 5 segue em aberto.

1. ✅ **`mercadopago/dx-php` é dependência não utilizada** — zero referências em `app/`, `routes/`, `config/`, `resources/`. Está só no `composer.json`/`lock`. Remover ou implementar.
2. ✅ **Factory órfã** — `database/factories/OrderItemRequiredDataFactory.php` referencia `App\Models\OrderItemRequiredData` e `App\Models\RequiredDataField`, que não existem. Quebra se for usada. (Não é pega pelo PHPStan: a análise cobre só `app/`.)
3. ✅ **Views órfãs** — `resources/views/backend/required-data-fields/{index,create,edit}.blade.php` apontam para um componente Livewire inexistente e não têm rota.
4. ✅ **`.env` local com vars mortas** — `N8N_*`, `OPENAI_*`, `GOOGLE_DRIVE_AGENT_*` continuam preenchidas, incluindo segredos, sem nenhum código que as leia.
5. **Majors disponíveis** (fora dos constraints, exigem autorização): `vite` 7→8, `laravel-vite-plugin` 2→3, `jquery` 3→4, `concurrently` 9→10, `@tabler/icons-webfont` 2→3.

---

## Manutenção — 2026-09-02 — Portagem de práticas do produto evoluído

Origem: `~/data/planetacertidoes-saas`, produto que evoluiu a partir deste scaffold. Foram lidas as 43 guidelines, os 6 agentes, as 2 skills, o `DESIGN.md` e o `pipeline.sh` de lá, separando o que é genérico do que é domínio de certidões.

> **Nota de versão:** o produto evoluído está em Laravel 12 / PHPUnit 11 — **atrás** deste scaffold (Laravel 13 / PHPUnit 12). Nada foi regredido; só práticas, estrutura documental e tooling foram portados. Os scripts `qa:*` já eram idênticos nos dois repositórios.

### Agentes (`docs/agentes/`) — de stubs a definições completas

De ~1,3 KB somados para ~9,8 KB. Cada agente ganhou papel, fontes canônicas, regra de uso e saída esperada:

- `README.md` — índice consolidado, mapa de agentes, **gatilhos operacionais** (`commit/push`, `criar landing page`) e regra anti-duplicação
- `business-agent.md` — processo de 6 passos + regra de separação core vs. produto
- `developer-agent.md` — add-ons do executor, gatilhos e regra de commit/push
- `review-agent.md`, `darkmode-agent.md`, `deploy-agent.md` — wrappers apontando para as guidelines `110`/`111`/`112`
- `gordon-agent.md` — wrapper do CodaFácil IA, reforçando que o módulo é local

### Guidelines

- **110** (243 B → 2,9 KB): escopo obrigatório de revisão em qualidade e segurança, achados com severidade/risco/correção, e uma seção nova **específica do scaffold** (ownership, status via service, ACL sincronizada, auditoria, não expor `id` interno, nada de I/O externo síncrono)
- **111** (243 B → 1,6 KB): escopo de verificação dark/light, regras de código (sem cor hardcoded sem `dark:`, WCAG AA, token antes de literal) e entrega esperada
- **112**: ganhou o padrão obrigatório de changelog, o gate 8 de idioma (`pt-BR`), a política de bloqueio e o formato de saída `APROVADO`/`BLOQUEADO`
- **116** (540 B → 7,5 KB): governança portável completa — política inegociável, fluxo operacional, 10 gates portáveis, padrão de changelog com métricas, checklist de adaptação para outro projeto e bloco pronto para copiar
- **00-master-guideline**: seções novas de convenções transversais (busca case-insensitive, `public_number`, auditoria, Form Request, paridade dark/light, mobile-first), **Governança Git** com 21 regras, mapa de agentes e skills

### Skills (`.claude/skills/`) — novo

- `tailwind/` — Tailwind v4 CSS-first, tokens `@theme`, dark mode por data-attribute
- `frontend-design/` — construção de interface com qualidade de design, mobile-first. **O bloco "Mobile App Visual System (Flutter)" foi removido**: era específico do domínio de certidões (saldo de carteira, etapa de seleção de cliente, paleta jurídica). Em seu lugar entrou uma seção de restrições do scaffold

### Design system (`DESIGN.md`) — novo

Portado como **template neutro**: mantém a arquitetura (tokens no frontmatter, "Named Rules", vocabulário de sombra, do's & don'ts) com paleta e marca genéricas para o clone substituir. Preserva as práticas de acessibilidade: WCAG 2.1 AA nos dois temas, alvo de toque de 44px e escala de espaçamento 4/8/12/16/24/32/48.

### Deploy (`pipeline.sh`) — reescrito

O script anterior tinha um **bug**: default para a branch `v_1_3`, que não existe neste repositório, e fazia `git checkout` cego. Além disso, faltavam etapas essenciais.

| | Antes | Depois |
|---|---|---|
| Branch | `git checkout v_1_3` (inexistente) | detecta a branch atual, `pull --ff-only` |
| Dependências PHP | não instalava | `composer install --optimize-autoloader --no-dev` |
| Dependências JS | não instalava | `npm ci` |
| ACL | não sincronizava | `php artisan acl:sync-backend` |
| Caches | não tratava | `optimize:clear` + config/route/view/event cache |
| Opcache | não zerava | restart do PHP-FPM (reload não limpa a SHM) |
| Ambientes | um só | `homologacao` / `producao` por variável de ambiente |

### Outros

- `.claude/launch.json` — **criado** (estava ausente): configs de Laravel serve, Vite, queue worker e Pail
- `.gitleaks.toml` — estava configurado para o **outro projeto**: título `planetacertidoes-saas`, allowlist com `INFOSIMPLES_TOKEN`/`MERCADOPAGO_*` e dois hashes de commit que não existem aqui. Reescrito para o scaffold, com allowlist genérica de placeholders e de fixtures de teste
- `CLAUDE.md` — passou a mapear agentes, skills, design system e gatilhos operacionais; checklist de commit atualizado com o gate de idioma e o status `APROVADO`/`BLOQUEADO`

---

---

## Manutenção — 2026-09-02 — Remoção de código morto e regeneração do baseline

Fecha as pendências 1 a 4 registradas acima. Antes de apagar qualquer coisa, cada item passou por verificação adversarial independente (9 agentes: um par por item tentando **provar que o item estava em uso**, mais uma varredura de completude). Resultado: os 4 confirmados mortos, confiança alta, zero bloqueio de código — e vários achados novos.

### 1. Dependência não utilizada — removida

`mercadopago/dx-php` saiu do `composer.json`. Verificações que sustentaram a remoção:

- zero ocorrências do namespace `MercadoPago\` ou de classes do SDK (`PreferenceClient`, `PaymentClient`, `MercadoPagoConfig`) em todo o código
- `composer why` confirma que o único dependente era o pacote raiz — nenhum pacote transitivo órfão
- o pacote não declara `extra.laravel`: sem auto-discovery, sem service provider, sem alias
- `README.md` listava "Mercado Pago SDK" na stack — linha removida junto, senão a doc passaria a mentir

### 2. Factory órfã — removida

`database/factories/OrderItemRequiredDataFactory.php` referenciava `App\Models\OrderItemRequiredData` e `App\Models\RequiredDataField`, ambos inexistentes. O PHPStan não pegava porque a análise cobre apenas `app/`.

### 3. Views órfãs — removidas

As três views de `resources/views/backend/required-data-fields/` invocavam componentes Livewire que não existem, sem nenhuma rota apontando para elas.

### 4. Variáveis de ambiente mortas — removidas

O escopo inicial era de 12 variáveis; a verificação encontrou **36**. Nenhuma delas é lida por qualquer arquivo de `config/`, `app/`, `routes/`, `database/` ou `tests/`:

| Bloco | Vars | Observação |
|-------|------|------------|
| `OPENAI_*` | 3 | credencial real preenchida |
| `N8N_*` | 3 | token real, URL de outro sistema |
| `GOOGLE_DRIVE_AGENT_*` | 6 | client secret e refresh token reais |
| `MERCADOPAGO_*` | 10 | vazias ou URLs de outro domínio |
| `INFOSIMPLES_*` | 14 | **credenciais de terceiros preenchidas** |

Preservadas por serem vivas: `GOOGLE_CLIENT_*` (Socialite, coberta por teste), `AWS_*`, `REDIS_*`, `MEMCACHED_HOST`, `VITE_APP_NAME`.

### 5. Baseline do PHPStan — regenerado

O baseline descrevia majoritariamente o produto anterior: 23 dos 35 caminhos apontavam para arquivos inexistentes (`MercadoPagoController`, `InfoSimplesService`, `WalletController`, `CertificateType`, `NewOrderWizard`, `OpenAIAgentService`…).

| | Antes | Depois |
|---|---|---|
| Tamanho | 35.930 bytes | 1.533 bytes |
| Entradas | 135 | 7 |
| Caminhos inexistentes | 23 de 35 | 0 |

Os 7 erros remanescentes são reais e ficam honestamente suprimidos. O gate 4 continua `[OK] No errors`.

### Lacuna inversa — `.env.example` completado

A verificação encontrou o problema oposto: variáveis **vivas** que nenhum arquivo de exemplo documentava, o que quebra o clone do scaffold. Adicionadas: `APP_VERSION`, `DB_QUEUE_RETRY_AFTER`, `MAIL_SCHEME`, `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`, as 7 `AGENT_*` e `ADMIN_SEED_EMAIL/PASSWORD`.

Não foram documentadas de propósito as chaves `mail.new_order_internal_to`, `mail.order_item_error_to` e `mail.order_completed_download_link_expire_minutes`: existem em `config/mail.php` mas não têm nenhum leitor.

### Correções de documentação

- `CLAUDE.md` descrevia `AgentUpload` com a coluna `drive_file_id`, que **não existe** na migration nem no `$fillable` — substituída pelas 12 colunas reais
- `CLAUDE.md`, `.claude/context/*` e `docs/agentes/gordon-agent.md` apontavam `agent:sync-drive-uploads` como rotina canônica. O que está no scheduler (`routes/console.php`) é `agent:sync-uploads`; `SyncAgentDriveUploads` é duplicata exata do outro comando e não é agendada. Referências corrigidas e a duplicata sinalizada

---

---

## Manutenção — 2026-09-02 — Erradicação do resíduo do produto anterior

Segunda rodada de limpeza, também precedida de verificação adversarial (13 agentes: um investigador por item, segunda opinião independente nos destrutivos e um agente de integração para achar conflito entre as mudanças). A verificação **corrigiu um erro do diagnóstico anterior** e achou resíduo mais grave do que o listado.

### Correção do diagnóstico

O relatório anterior dizia que `DatabaseSeeder` e `MobileApiTest` usavam status inválido. Errado: `OrderItem` tem **domínio de status próprio** (`pending`, `processing`, `completed`, validado por `Api/Mobile/OrderStoreRequest`), diferente do `Order`. Das três ocorrências apontadas, só uma era bug de fato — a linha do `Order` no seeder. Alterar o teste teria causado `422` e quebrado o gate 5.

### Marca do produto anterior em página pública

- `about.blade.php` exibia o wordmark do produto anterior como ilustração de card — trocado pelo logo do próprio scaffold, seguindo o padrão do card irmão
- `welcome.blade.php` exibia uma imagem de 1,6 MB com o codinome do agente do produto anterior — trocada por ilustração abstrata genérica já usada na mesma página
- `public/robots.txt` apontava crawlers para o sitemap de um domínio de terceiro — linha removida

### Vazamento entre projetos

- `public/storage` era um **symlink absoluto para o storage de outro repositório** — tudo servido em `/storage/*` vinha do produto anterior. Refeito com `storage:link` para o storage deste projeto
- `storage/users.json` estava **versionado**, com nome do produto anterior, e-mail real e hash bcrypt de senha, sem nenhum leitor no código — removido
- `.claude/settings.local.json` concedia leitura do outro repositório e continha buscas, um `curl` que gravava lá dentro e um domínio de API do produto anterior — 16 das 49 entradas removidas, preservando integralmente as permissões dos gates

### Código morto

- comando `agent:sync-drive-uploads` era duplicata exata de `agent:sync-uploads` e não estava no scheduler — removido, com as 4 referências em documentação corrigidas
- duas views órfãs: uma usava um model que nunca existiu neste repositório (falha em runtime se montada), a outra tinha a classe de apoio ausente
- três chaves de `config/mail.php` sem nenhum leitor, mais as variáveis correspondentes
- `MAIL_ENCRYPTION` no `.env.example`: morta desde o Laravel 11, substituída por `MAIL_SCHEME`
- 13 assets de marca de terceiros e a biblioteca `jquery-multi`, todos sem referência — `public/` caiu de 21 MB para 13 MB

### Correções de bug encontradas de passagem

- seeder criava pedido com status fora da máquina de estados — estado inalcançável em dado de demonstração
- `manifest.json` e `browserconfig.xml` apontavam para ícones na raiz web, mas os arquivos estão em `/images/icons/` — já estava quebrado, corrigido

### Preparação para o fork

- `composer.json` ainda se identificava como `laravel/livewire-starter-kit`, com a descrição do starter kit oficial — corrigido para a identidade do scaffold
- `.env.example` trazia e-mails pessoais como default, que se propagariam para todo fork — trocados por placeholders
- `docs/clone-checklist.md` reescrito: cobria 3 seções e citava a versão errada; agora cobre identidade, configuração, deploy, governança, conteúdo, validação e **higiene herdada** — a seção que impede o próximo fork de repetir estes mesmos problemas

---

## Fechamento Técnico

**🧪 Testes executados**

| Gate | Comando | Resultado |
|------|---------|-----------|
| 1 | `composer qa:validate` | 🟢 composer.json valid |
| 2 | `composer qa:security` | 🟢 zero advisories |
| 3 | `composer qa:style` | 🟢 passed |
| 4 | `composer qa:static` | 🟢 no errors (109 arquivos) |
| 5 | `composer qa:test` | 🟢 72 passed, 332 assertions |
| 6 | `composer qa:secrets` | 🟢 no leaks found |
| 7 | prefixo de commit | 🟢 `[FEAT]` com descrição em pt-BR |
| 8 | `composer qa:anti-debug` | 🟢 sem debug calls |
| 9 | Trivy | ⚪ N/A (sem Dockerfile) |
| 10 | changelog atualizado | 🟢 este bloco |
| — | `php artisan route:list --except-vendor` | 🟢 85 rotas |
| — | `php artisan migrate --pretend` | 🟢 nothing to migrate |
| — | `npm run build` | 🟢 build concluído |

**📊 Total de testes**

🔵 72 testes · 332 assertions

**🛡️ Validação das demais gates**

- 🟢 Contraste medido par a par: 12 combinações em uso aprovadas nos dois temas
- 🟢 Identidade verificada na aplicação em execução, em tema claro e escuro
- 🟢 Cores fixas fora do sistema de design eliminadas das telas, do site e dos e-mails
- 🟢 Todas as telas do escopo navegáveis entre si — 942 destinos verificados
- 🟢 Nenhuma migration alterada, criada ou órfã nesta entrega
- 🟢 Núcleo compartilhado preservado: os componentes existentes herdaram a marca sem reescrita

**📈 Métricas do sistema**

- 🔵 Arquivos rastreados: 788
- 🔵 Linhas rastreadas: 156.811
- ⚪ Release anterior de referência: N/A (`1.1` é a baseline da série)
- ⚪ Arquivos da release anterior: N/A
- ⚪ Linhas da release anterior: N/A
- ⚪ Aumento de arquivos vs release anterior: N/A
- ⚪ Aumento de linhas vs release anterior: N/A
- 🔵 Novos commits da release: 4 (incluindo esta entrega)

**Status final: 🟢 APROVADO**
