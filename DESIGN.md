---
name: Velaro
description: Sistema visual do Velaro — ajustar tokens e north star conforme a marca.
colors:
  primary: "#2563eb"
  primary-hover: "#1d4ed8"
  primary-dark-mode: "#60a5fa"
  ink: "#0f172a"
  body: "#334155"
  muted: "#64748b"
  surface: "#ffffff"
  surface-subtle: "#f8fafc"
  surface-dark: "#1e293b"
  border: "#e2e8f0"
  border-dark: "#334155"
  success: "#15803d"
  warning: "#b45309"
  danger: "#dc2626"
typography:
  headline:
    fontFamily: "Sora, Segoe UI, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.33
    letterSpacing: "-0.02em"
  title:
    fontFamily: "Sora, Segoe UI, sans-serif"
    fontSize: "1.2rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "-0.02em"
  body:
    fontFamily: "Manrope, Segoe UI, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  label:
    fontFamily: "Manrope, Segoe UI, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.25
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
spacing:
  xs: "4px"
  sm: "8px"
  compact: "12px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  section: "48px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    rounded: "{rounded.md}"
    padding: "10px 24px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.surface}"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sm}"
    padding: "10px 12px"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: "24px"
  navigation-active:
    backgroundColor: "{colors.surface-subtle}"
    textColor: "{colors.primary-hover}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
---

# Design System — Velaro

> **Este arquivo é um template.** Ao clonar o scaffold para um produto novo,
> troque o `name`, a paleta, as fontes e o *creative north star*. A **estrutura**
> (tokens no frontmatter + regras nomeadas + acessibilidade) deve ser preservada.

## Overview

**Creative North Star: "Central de Operações"**

O sistema visual funciona como uma central de operações: cada área mostra o contexto necessário, evidencia a próxima ação e mantém os estados do processo reconhecíveis. A composição é previsível e eficiente, com densidade adequada a tabelas e fluxos administrativos, sem transformar cada informação em decoração.

A experiência transmite confiança por consistência, clareza por hierarquia e eficiência por proximidade entre informação e ação.

**Key Characteristics:**

- Navegação persistente e orientada por tarefas.
- Densidade controlada para operação frequente.
- Cor de ação reservada a ação, seleção e estado relevante.
- Superfícies planas, separadas por contraste tonal ou bordas discretas.
- Comportamento estruturalmente responsivo no desktop e no mobile.

## Colors

A paleta combina neutros frios e legíveis com uma cor de ação usada com parcimônia.

### Primary

- **Cor de ação:** identifica ações primárias, seleção atual e ênfase de estado. Nunca funciona como preenchimento decorativo de grandes áreas.
- **Cor de ação profunda:** reservada a hover e links de ação sobre superfícies claras.
- **Cor de ação luminosa:** variante de contraste para seleção e ações no dark mode.

### Neutral

- **Tinta Profunda:** títulos, dados prioritários e texto de alto contraste.
- **Ardósia:** texto corrente e informação secundária.
- **Superfície Clara:** área principal de trabalho e componentes interativos.
- **Superfície Noturna:** área principal equivalente no dark mode.
- **Divisor Suave:** bordas, separadores e estrutura de tabelas.

### Named Rules

**The One Signal Rule.** A cor de ação aparece somente quando comunica ação, seleção ou estado. Sua raridade sustenta sua força.

**The Contrast Is State Rule.** Texto, ícone e superfície mudam juntos entre temas; nenhuma superfície clara pode manter texto de dark mode, e vice-versa.

## Typography

**Display Font:** Sora (fallback Segoe UI, sans-serif)
**Body Font:** Manrope (fallback Segoe UI, sans-serif)

**Character:** títulos firmes e compactos organizam o trabalho; o corpo permanece neutro, legível e discreto para longas sessões operacionais.

### Hierarchy

- **Headline** (600, 1.5rem, 1.33): título principal da página e identificação do contexto.
- **Title** (600, 1.2rem, 1.4): títulos de áreas, tabelas e agrupamentos.
- **Body** (400, 0.875rem, 1.43): conteúdo corrente, descrições e dados operacionais.
- **Label** (600, 0.75rem, 1.25): campos, estados compactos e navegação secundária; caixa normal por padrão.

### Named Rules

**The Operational Voice Rule.** Textos de produto usam frases curtas, verbos diretos e caixa normal. Letras maiúsculas espaçadas ficam restritas a cabeçalhos de tabela quando melhorarem a varredura.

## Elevation

O sistema é plano por padrão. Profundidade vem primeiro de contraste tonal, bordas discretas e posição fixa da navegação. Sombras são estruturais e aparecem apenas em elementos temporários que precisam se sobrepor ao fluxo — menus, modais e tooltips.

### Shadow Vocabulary

- **Sobreposição baixa** (`0 5px 15px rgba(0, 0, 0, 0.05)`): menus e elementos flutuantes pequenos.
- **Sobreposição média** (`0 10px 30px rgba(0, 0, 0, 0.08)`): modais e painéis temporários.

### Named Rules

**The Flat By Default Rule.** Cartões e seções permanentes nunca combinam borda com sombra larga. Se o conteúdo está no fluxo da página, contraste e espaçamento resolvem a hierarquia.

## Components

### Buttons

- **Shape:** cantos controlados (12px), sem formato excessivamente arredondado.
- **Primary:** cor de ação, texto de alto contraste e área mínima de toque de 44px.
- **Hover / Focus:** mudança para a variante profunda e foco visível; transição entre 150–200ms.
- **Secondary:** superfície neutra com borda discreta; nunca compete com a ação primária.

### Chips

- **Style:** fundo tonal suave, texto semântico e formato pill apenas para estados e filtros compactos.
- **State:** sempre combina texto com cor; cor isolada não comunica situação.

### Cards / Containers

- **Corner Style:** 12–16px conforme o tamanho da superfície.
- **Background:** superfície clara ou noturna coerente com o tema.
- **Shadow Strategy:** plano por padrão, conforme a regra de elevação.
- **Border:** divisor suave de 1px quando necessário para estrutura.
- **Internal Padding:** 16px em componentes compactos e 24px em seções principais.

### Inputs / Fields

- **Style:** superfície do tema, borda de 1px, raio de 8px e altura mínima de 44px.
- **Focus:** borda na cor de ação acompanhada de anel visível com contraste AA.
- **Error / Disabled:** mensagem textual explícita; campos desabilitados preservam legibilidade.

### Navigation

A sidebar persistente organiza as tarefas no desktop. O item ativo usa contraste tonal, cor de ação e peso 600; subitens aparecem no contexto do grupo ativo. No mobile, a navegação se transforma em cabeçalho compacto com menu acionável por teclado e área de toque mínima de 44px.

### Tables

Tabelas são superfícies operacionais, não cartões decorativos. Cabeçalhos compactos, alinhamento numérico à direita, estados textuais e ações no fim da linha favorecem leitura rápida. Em telas estreitas, a estrutura deve permitir rolagem horizontal ou apresentação resumida sem cortar dados.

## Do's and Don'ts

### Do:

- **Do** usar a escala de espaçamento de 4, 8, 12, 16, 24, 32 e 48px.
- **Do** manter ações primárias identificáveis em até dois segundos.
- **Do** preservar contraste WCAG 2.1 AA em ambos os temas.
- **Do** usar estados com texto, ícone e cor quando apropriado.
- **Do** manter fluxos do administrador e do cliente consistentes sem igualar suas densidades.

### Don't:

- **Don't** criar uma interface excessivamente decorativa, burocrática ou antiquada.
- **Don't** hardcodar cor ou fonte no componente — usar os tokens deste arquivo.
- **Don't** entregar tela sem paridade dark/light (ver guideline `111`).
- **Don't** tratar mobile como adaptação: o projeto é mobile-first.
