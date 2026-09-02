# Agentes do Projeto (Índice Consolidado)

## 1. Objetivo

Centralizar as referências dos agentes manuais do scaffold e reduzir duplicação de regras entre `docs/agentes/*` e `docs/guidelines/*`.

## 2. Fonte de verdade

1. Regras base do agente de implementação: `AGENTS.md` / `CLAUDE.md`
2. Governança e fluxo oficial: `docs/guidelines/00-master-guideline.md`
3. Regras especializadas por agente: arquivos em `docs/agentes/*`
4. Guias de operação (manual): `docs/guidelines/110..116`
5. Agentes `review/darkmode/deploy` funcionam como **wrappers**; o checklist oficial fica nas guidelines `110/111/112`.

## 3. Mapa de agentes

1. **Business Agent**
   - Arquivo: `docs/agentes/business-agent.md`
   - Uso: mapear regra de negócio, validações, escopo e critérios de aceite.

2. **Review Agent**
   - Arquivo: `docs/agentes/review-agent.md`
   - Guia operacional: `docs/guidelines/110-agente-revisao-qualidade-seguranca.md`
   - Uso: revisão de qualidade e segurança após a feature estar funcional.

3. **Darkmode Agent**
   - Arquivo: `docs/agentes/darkmode-agent.md`
   - Guia operacional: `docs/guidelines/111-agente-darkmode.md`
   - Uso: diagnóstico/correção visual de dark mode, sem alterar regra de negócio.

4. **Deploy Agent**
   - Arquivo: `docs/agentes/deploy-agent.md`
   - Guia operacional: `docs/guidelines/112-agente-deploy-gates.md`
   - Uso: validar gates de release pré-deploy.

5. **Developer Agent**
   - Arquivo: `docs/agentes/developer-agent.md`
   - Uso: complemento do executor técnico, sempre subordinado ao `CLAUDE.md` / `AGENTS.md`.

6. **CodaFácil IA (agente do produto)**
   - Arquivo: `docs/agentes/gordon-agent.md`
   - Guia operacional: `docs/guidelines/114-agente-gordon.md`
   - Uso: contexto técnico do módulo local de chat, uploads e processamento assíncrono.

## 4. Gatilhos operacionais (Skill-like)

1. Gatilho textual: **`commit/push`**
   - Ação obrigatória: executar a rotina da guideline `112` (deploy gates), registrar o resultado e só seguir para commit/push **com autorização explícita do usuário**.
   - Padrão de changelog da rotina `112`: `## Principais implementações` com poucos temas estratégicos, sem `Arquivos impactados`/`Status`, texto comercial e `## Fechamento Técnico` visual (`🧪📊🛡️📈`), mantendo o comparativo de crescimento e os novos commits da release.

2. Gatilho textual: **`criar landing page`**
   - Ação obrigatória: executar a rotina da guideline `113`, sincronizando rotas/menu/lista/views/testes/changelog conforme checklist.

## 5. Regra anti-duplicação

1. Evitar repetir checklist completo em múltiplos arquivos.
2. Quando existir guia operacional (`110..116`), referenciar por link no agente.
3. Manter neste índice a navegação principal para reduzir divergência documental.
