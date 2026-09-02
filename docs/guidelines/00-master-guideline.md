# Master Guideline — CodaFácil Scaffold

Esta é a base genérica do **CodaFácil** (`codafacil.dev`), um scaffold Laravel reutilizável para novos sistemas.

## Princípios

- Regra transversal fica no core; regra exclusiva do produto vai em módulos isolados
- Auth, ACL, auditoria, mobile, jobs e scheduler **não devem ser removidos**
- Qualquer integração externa deve nascer desacoplada e testável via interface/contrato
- Commits e pushes **somente com autorização explícita** do usuário — ver `112-agente-deploy-gates.md` e `116-governanca-portavel-commit-push.md`
- Toda mensagem de commit deve usar o formato obrigatório `[TIPO] resumo curto`
- Reset destrutivo de banco local não faz parte do fluxo rotineiro de desenvolvimento nem dos gates de `commit/push`
- Sem `dd()`, `dump()`, `var_dump()`, `ray()`, `die()` em código commitado
- Dependências ficam no topo dos constraints; `composer audit` e `npm audit` devem terminar em zero advisories

## Stack

- **Backend:** PHP 8.3 (plataforma fixada em `8.3.30` no `composer.json`), Laravel 13, Fortify (auth + 2FA), Socialite (Google OAuth)
- **Frontend:** Livewire 4 + Volt, Flux UI (free), Tailwind CSS 4, Vite 7
- **Database:** MySQL (padrão do `.env.example`), PostgreSQL suportado; SQLite em memória nos testes
- **Testes e tooling:** PHPUnit 12, Larastan 3, Pint 1, Boost 2, Pail 1
- **Agente:** CodaFácil IA local com chat, uploads e processamento assíncrono

## Domínio base

| Model | Propósito |
|-------|-----------|
| `User` | Autenticação, 2FA, Google OAuth, ACL |
| `Customer` | Cliente/entidade alvo — customizar por produto |
| `Product` | Catálogo de itens — customizar por produto |
| `Order` / `OrderItem` | Pedido genérico — customizar por produto |
| `AuditLog` | Trilha admin imutável |
| `AclPermission` / `AclResponsibility` | ACL granular por módulo |
| `AgentConversation` / `AgentMessage` / `AgentUpload` | Agente IA assíncrono |

## Ambientes preservados

1. **Site público** — `/`, `/sobre`, `/servicos`, `/contato`
2. **Front autenticado** — `/dashboard`, `/customers/*`, `/products/*`, `/orders/*`
3. **Admin** — `/backend/*` (usuários, ACL, auditoria, changelog, clientes, produtos, pedidos)
4. **API mobile** — `/api/mobile/*` (auth, dashboard, clientes, produtos, pedidos)
5. **Agente** — `/agente/*` (chat, uploads, conversas)

## Ao criar um novo produto a partir deste scaffold

1. Use a branch `main` (versão `1.1`) como ponto de partida do novo produto
2. Renomeie `APP_NAME` no `.env`
3. Substitua o logo em `/public/logo.webp`
4. Implemente domínio específico em módulos isolados (sem alterar o core)
5. Execute `php artisan acl:sync-backend` após adicionar permissões
6. Siga os gates de deploy em `112-agente-deploy-gates.md`

## Convenções transversais

1. Toda busca textual implementada no sistema deve ser **case-insensitive**, com comportamento consistente entre frontend, backend e APIs.
2. Nenhuma rota ou response expõe `orders.id` interno — sempre `public_number`.
3. Toda escrita no backend gera registro em `AuditLog`.
4. Todo input de usuário passa por Form Request.
5. Toda tela entrega paridade dark/light — ver `111` e `DESIGN.md`.
6. Mobile-first: navegação precisa de menu mobile utilizável sem setup extra.

## Governança Git (obrigatória)

1. Nunca executar `git commit` automaticamente.
2. Nunca executar `git push` automaticamente.
3. `commit` e `push` só com autorização **explícita e atual** do usuário, no momento da ação.
4. Toda mensagem de commit em `pt-BR`, respeitando o prefixo obrigatório da guideline `112`.
5. Fix adicional após um commit **não** vira commit novo por iniciativa própria; fica local até nova autorização.
6. Antes de rodar os testes de fechamento, registrar a entrada no changelog da branch ativa.
7. Padrão do arquivo de changelog: `X.Y` → `docs/changelog_X_Y.md`.
8. `## Principais implementações` lista poucos temas estratégicos em linguagem de negócio.
9. Cada entrada de tarefa contém apenas `**Resumo:**` e `**O que foi feito:**`.
10. Esses campos são curtos, comerciais e não citam arquivos, classes, funções ou rotas.
11. Não usar por entrada `**Arquivos impactados:**` nem `**Status:**`.
12. Ajuste dentro de uma `FEAT` já registrada é consolidado no bloco existente.
13. O changelog mantém um único bloco final `## Fechamento Técnico` (`🧪 📊 🛡️ 📈`).
14. O fechamento técnico é atualizado a cada tarefa da branch com o resultado mais recente.
15. Marcadores: `🟢` OK · `⚪` N/A · `🔵` métrica numérica.
16. `**📈 Métricas do sistema**` registra arquivos/linhas rastreados, a release anterior de referência, os comparativos de crescimento e os novos commits da release.
17. `1.1` é a baseline da série e usa `⚪ N/A` nos campos comparativos; releases seguintes comparam com a imediatamente anterior.
18. A suíte obrigatória para fechamento técnico é `composer qa:gates`.
19. Contadores oficiais: `git ls-files | wc -l`, `git ls-files -z | xargs -0 wc -l | tail -n 1` e `git rev-list --count <release_anterior>..HEAD`.
20. Gatilho textual `commit/push` aciona a rotina da guideline `112` antes de qualquer fechamento.
21. Gatilho textual `criar landing page` aciona a rotina da guideline `113`.

> Versão portável desta política para outros projetos: `docs/guidelines/116-governanca-portavel-commit-push.md`.

## Agentes de apoio

1. `docs/agentes/business-agent.md` — desenho de regra de negócio e critérios de aceite.
2. `docs/agentes/review-agent.md` — revisão de qualidade e segurança (guideline `110`).
3. `docs/agentes/darkmode-agent.md` — diagnóstico e correção visual dark/light (guideline `111`).
4. `docs/agentes/deploy-agent.md` — readiness técnica e gates pré-deploy (guideline `112`).
5. `docs/agentes/developer-agent.md` — gatilhos operacionais do executor técnico.
6. `docs/agentes/gordon-agent.md` — wrapper do módulo CodaFácil IA (guideline `114`).
7. `docs/agentes/README.md` — índice consolidado.

## Skills

1. `.claude/skills/tailwind/SKILL.md` — Tailwind v4 CSS-first, tokens `@theme` e dark mode.
2. `.claude/skills/frontend-design/SKILL.md` — construção de interface com qualidade de design.
