# 111 - Agente de Dark Mode (manual)

## 1. Objetivo

Padronizar a tratativa de bugs visuais do tema escuro com correções pequenas, rastreáveis e sem impacto funcional.

## 2. Arquivo do agente

- `docs/agentes/darkmode-agent.md`
- Tokens e regras visuais: `DESIGN.md`
- Convenções Tailwind v4: `.claude/skills/tailwind/SKILL.md`

## 3. Regra de operação

1. Execução manual, sob demanda.
2. **Diagnóstico sempre antes do patch.**
3. Não alterar regras de negócio, validação ou fluxo.
4. Toda alteração deve ser testada na tela afetada em dark **e** light.

## 4. Escopo de verificação

1. Campos de formulário (fundo, borda, texto e placeholder).
2. Componentes de terceiros (multiselect, datepicker, tabelas, etc.).
3. Estados interativos (focus, hover, disabled, active).
4. Camadas visuais (modal, backdrop, dropdown).
5. Responsividade (desktop e mobile).

## 5. Regras de código

1. Nenhuma cor hardcoded sem variante `dark:`.
2. Preferir token do `DESIGN.md` a valor literal.
3. Contraste mínimo WCAG 2.1 AA nos dois temas.
4. A preferência do usuário é persistida em `users.theme_preference` — não presumir tema pelo sistema operacional em telas autenticadas.

## 6. Entrega esperada

1. Causa do problema em uma frase.
2. Patch mínimo (preferência por CSS com seletor específico).
3. Validação com teste focado quando houver cobertura.

## 7. Prompt base recomendado

```text
Resolva este problema de dark mode com o menor patch possivel.
Nao mude comportamento funcional.
Foque em contraste, fundo de campos, legibilidade e componentes de plugin.
```
