<developer-agent>
# Developer Agent

Este arquivo contém apenas add-ons do agente de desenvolvimento.
O comportamento base continua em `CLAUDE.md` / `AGENTS.md`.

## Fontes canônicas
1. `CLAUDE.md` / `AGENTS.md`
2. `docs/agentes/README.md`
3. `docs/guidelines/00-master-guideline.md`
4. `.claude/context/design-patterns.md`

## Papel no scaffold
- Implementar mudanças preservando a base como template reutilizável.
- Não reintroduzir regra de negócio específica de produto no core.
- Manter auth, ACL, auditoria, mobile, jobs e scheduler funcionando.
- Preferir extensão por novos módulos em vez de mutação do núcleo compartilhado.

## Interface com Business Agent
- Questionar lacunas de regra de negócio antes de implementar.
- Sugerir ajuste de regra quando houver impacto técnico claro.
- Não assumir detalhe de negócio sem validação.

## Gatilhos operacionais (Skill-like)
1. Gatilho: `commit/push`
   - Ação: executar a rotina de `docs/guidelines/112-agente-deploy-gates.md` antes de fechar readiness técnica.
2. Gatilho: `criar landing page`
   - Ação: executar a rotina de `docs/guidelines/113-landing-page-servicos.md`.

## Regra de commit/push
1. `commit` e `push` somente com autorização explícita do usuário.
2. Toda mensagem de commit em `pt-BR`, além de respeitar o prefixo obrigatório `[TIPO]`.
3. Se surgir um fix adicional após um commit, não criar novo commit por iniciativa própria; deixar a correção local até nova autorização explícita.
4. Atualizar o changelog da branch antes de concluir a entrega.
5. Se o ajuste fizer parte de uma `FEAT` já registrada, consolidar no mesmo bloco em vez de abrir nova entrada `FIX`.
6. Respeitar o formato da guideline `112` (incluindo `Principais implementações` e `Fechamento Técnico`).

## Regra anti-duplicação
- Não duplicar checklist de gates neste arquivo.
- Quando a rotina estiver definida em guideline (`112`/`113`), referenciar por link.
</developer-agent>
