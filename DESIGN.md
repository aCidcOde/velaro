---
name: Velaro
description: Sistema visual do Velaro Alianças — plataforma B2B de fábrica para lojista.
colors:
  primary: "#0e5b58"
  primary-hover: "#0b4a49"
  primary-dark-mode: "#a97c3c"
  brand-surface: "#012227"
  brand-hero: "#073334"
  brand-nav-active: "#04292c"
  brand-deep: "#001a1d"
  gold: "#a97c3c"
  gold-text: "#dba765"
  gold-link: "#7a5623"
  gold-tint: "#faf4e9"
  onyx-sidebar: "#0c1817"
  onyx-topbar: "#071110"
  ink: "#171817"
  body: "#545150"
  muted: "#726f6c"
  surface: "#ffffff"
  surface-subtle: "#faf9f7"
  surface-sunken: "#f3f2ef"
  surface-dark: "#0c1817"
  border: "#e5e4e0"
  border-dark: "#22322f"
  success: "#027a48"
  warning: "#b54708"
  danger: "#b42318"
  info: "#1d4ed8"
typography:
  headline:
    fontFamily: "Jost, Segoe UI, sans-serif"
    fontSize: "2.125rem"
    fontWeight: 500
    lineHeight: 1.24
    letterSpacing: "-0.015em"
  title:
    fontFamily: "Jost, Segoe UI, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: "-0.015em"
  body:
    fontFamily: "Inter Tight, Segoe UI, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  label:
    fontFamily: "Inter Tight, Segoe UI, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.33
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  pill: "999px"
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
  button-on-dark:
    backgroundColor: "{colors.gold}"
    textColor: "{colors.surface}"
    rounded: "{rounded.md}"
    padding: "10px 24px"
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
    backgroundColor: "{colors.brand-nav-active}"
    textColor: "{colors.surface}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
  brand-band:
    backgroundColor: "{colors.brand-surface}"
    textColor: "{colors.surface}"
---

# Design System — Velaro

> Sistema visual do **Velaro Alianças**, plataforma B2B entre a fábrica e o lojista.
> Derivado da identidade oficial da marca e dos protótipos aprovados, com os valores
> amostrados pixel a pixel da arte. Referência viva: `docs/mockups/` (31 telas) e
> `docs/mockups/velaro-tokens.css` (fonte única de verdade dos tokens).

## Overview

**Creative North Star: "Joalheria em operação"**

O sistema equilibra duas naturezas que normalmente se excluem. A marca é joalheria:
esmeralda profunda, dourado contido, espaço generoso, tipografia leve. A operação é
fábrica: tabela densa, status inequívoco, próxima ação evidente, dado numérico legível
em varredura rápida. Nenhuma das duas cede à outra — elas ocupam superfícies diferentes.

A esmeralda é a **superfície da marca**: hero, faixa institucional, navegação, item ativo.
O off-white é a **superfície de trabalho**: tabela, formulário, cartão, relatório. O dourado
atravessa as duas como acento e, sobre o escuro, como ação.

**Key Characteristics:**

- Superfície de marca e superfície de trabalho nunca se misturam na mesma área.
- Densidade alta onde há dado; respiro alto onde há marca.
- Cor de ação reservada a ação, seleção e estado — nunca a preenchimento decorativo.
- Estado sempre comunicado por texto e cor juntos.
- Comportamento mobile-first e paridade dark/light em toda tela.

## Colors

Neutros **quentes com viés esverdeado**. Cinza neutro puro briga com o dourado e
esfria a esmeralda — por isso ele não existe na paleta.

### Primary

- **Esmeralda profunda:** superfície da marca. Hero, faixa institucional, sidebar de contexto,
  item de navegação ativo e fundo dos blocos que falam em nome da Velaro.
- **Esmeralda de ação:** ação primária em superfície clara — botão, foco, link de comando.
  É a variante clara o bastante para passar em AA sobre branco.
- **Dourado:** acento em qualquer superfície e **ação primária sobre superfície escura**.
  Selo, hairline, ícone de destaque, CTA do site público.
- **Dourado de link:** a única variante de dourado permitida como texto ou borda em
  superfície clara, porque é a única que atinge contraste AA ali.

### Neutral

- **Tinta:** títulos, dado prioritário e texto de alto contraste.
- **Corpo:** texto corrente e informação secundária.
- **Superfície clara:** área principal de trabalho, cartão e componente interativo.
- **Superfície recuada:** fundo da página sob os cartões.
- **Ônix:** chrome escuro dos painéis — sidebar e topbar. Preto com viés esverdeado.
- **Divisor:** bordas, separadores e estrutura de tabela.

### Named Rules

**The Gold On Dark Rule.** A ação troca de cor conforme a superfície: sobre fundo escuro
a ação é **dourada**, como no hero da marca; sobre fundo claro a ação é **esmeralda**.
Dourado sobre branco não passa em AA para texto pequeno — por isso o dourado de link é
o único permitido como texto ou borda em superfície clara.

**The One Signal Rule.** A cor de ação aparece somente quando comunica ação, seleção ou
estado. Sua raridade sustenta sua força. Esmeralda cheia não preenche áreas grandes fora
das faixas institucionais.

**The Two Status Families Rule.** Status do pedido e status do pagamento são independentes.
Nunca compartilham a mesma cor de chip na mesma linha: o operacional usa a família fria
(neutro, informação, violeta, verde), o financeiro usa a família quente (âmbar, verde,
vermelho). Chip sempre combina **texto + cor** — cor isolada não comunica situação.

**The White Label Rule.** A vitrine do consumidor final não lê nenhum token Velaro. Ela é
pintada por variáveis próprias do revendedor, carregadas do seu cadastro de loja. A regra é
verificável: nenhuma referência à escala de marca no CSS da vitrine.

**The Audited Action Rule.** Ação sensível — "Ver como revendedor", aprovar ou reprovar
cadastro, ajuste de estoque, baixa financeira, liberação de remessa — recebe tratamento
visual próprio: contorno dourado ou botão de perigo, nunca botão sólido comum. O peso
visual acompanha o fato de que a ação gera registro em auditoria.

**The Contrast Is State Rule.** Texto, ícone e superfície mudam juntos entre temas.
Nenhuma superfície clara mantém texto de dark mode, e vice-versa.

## Typography

**Display Font:** Jost (fallback Segoe UI, sans-serif)
**Body Font:** Inter Tight (fallback Segoe UI, sans-serif)

**Character:** direção geométrica, sem serifa — a linguagem das joalherias nativas digitais.
Luxo por silêncio e espaço, não por ornamento. É também o par que envelhece melhor num
painel que o lojista abre todo dia.

A separação entre as duas vozes é de **peso e espacejamento**, não de família: a display é
leve e aberta, a operacional é neutra e compacta. Como a geométrica é larga por natureza,
todo tamanho grande leva espacejamento negativo. O wordmark vai na direção oposta e abre
para `0.30em` — é ele que dá o ar de joalheria sem serifa nenhuma.

Não existe itálico de marca. Onde o hero precisa separar duas linhas, a hierarquia vem de
**peso** (300 → 500) e **cor** (branco → dourado).

### Hierarchy

- **Headline** (Jost 500, 2.125rem, 1.24): título de página e identificação do contexto.
- **Title** (Jost 500, 1.25rem, 1.4): títulos de cartão, tabela e agrupamento.
- **Body** (Inter Tight 400, 0.875rem, 1.43): conteúdo corrente, descrição e dado operacional.
- **Label** (Inter Tight 600, 0.75rem, 1.33): campo, estado compacto e navegação secundária.

Valor de KPI usa a display em peso 500 com espacejamento negativo e numerais tabulares.

### Named Rules

**The Two Voices Rule.** A display nunca aparece em rótulo de campo nem em célula de tabela.
A sans nunca aparece em título de página. É essa separação que dá o tom de joalheria sem
prejudicar a densidade da operação.

**The Operational Voice Rule.** Texto de produto usa frase curta, verbo direto e caixa normal.
Maiúsculas espaçadas ficam restritas a cabeçalho de tabela, eyebrow e wordmark.

**The Tabular Numbers Rule.** Todo número comparável — valor, quantidade, data, percentual —
usa numerais tabulares e alinhamento à direita. Coluna de dinheiro que dança na varredura é
defeito, não estilo.

## Elevation

O sistema é plano por padrão. Profundidade vem primeiro de contraste tonal, borda discreta e
posição fixa da navegação. Sombra é estrutural e aparece apenas em elemento temporário que
precisa se sobrepor ao fluxo — menu, modal, drawer flutuante e tooltip.

### Shadow Vocabulary

- **Sobreposição baixa** (`0 5px 15px rgba(1, 34, 39, 0.08)`): menu e elemento flutuante pequeno.
- **Sobreposição média** (`0 10px 30px rgba(1, 34, 39, 0.12)`): modal e painel temporário.

A sombra é tingida de esmeralda, não de preto neutro — sombra cinza sobre off-white quente
lê como sujeira.

### Named Rules

**The Flat By Default Rule.** Cartão e seção permanentes nunca combinam borda com sombra
larga. Se o conteúdo está no fluxo da página, contraste e espaçamento resolvem a hierarquia.

## Components

### Buttons

- **Shape:** cantos controlados (12px), sem formato excessivamente arredondado.
- **Primary:** esmeralda de ação em superfície clara; dourado em superfície escura.
  Texto de alto contraste e área mínima de toque de 44px.
- **Hover / Focus:** mudança para a variante profunda e foco visível; transição de 150–200ms.
- **Secondary:** superfície neutra com borda discreta; nunca compete com a ação primária.
- **Ghost dourado:** contorno dourado sobre escuro, para a ação secundária do site público
  e para toda ação auditada.

### Chips

- **Style:** fundo tonal suave, texto semântico, formato pill — restrito a estado e filtro compacto.
- **State:** sempre combina texto com cor. Duas famílias, uma por eixo de status, conforme
  a regra The Two Status Families.

### Cards / Containers

- **Corner Style:** 12–16px conforme o tamanho da superfície.
- **Background:** superfície clara ou ônix, coerente com o tema.
- **Shadow Strategy:** plano por padrão, conforme a regra de elevação.
- **Border:** divisor de 1px quando necessário para estrutura.
- **Internal Padding:** 16px em componente compacto e 24px em seção principal.

### Inputs / Fields

- **Style:** superfície do tema, borda de 1px, raio de 8px e altura mínima de 44px.
- **Focus:** borda na cor de ação acompanhada de anel visível com contraste AA.
- **Required:** asterisco em vermelho semântico junto ao rótulo.
- **Error / Disabled:** mensagem textual explícita; campo desabilitado preserva legibilidade.
- **Upload:** área tracejada com ícone, tipo de arquivo aceito e limite de tamanho visíveis.

### Navigation

A sidebar persistente em ônix organiza as tarefas no desktop. O item ativo usa a esmeralda de
navegação, texto branco, peso 500 e ícone dourado. A topbar em ônix mais profundo carrega
busca global, contexto e as ações de identidade. No mobile a navegação vira cabeçalho compacto
com menu acionável por teclado e área de toque mínima de 44px.

### Tables

Tabelas são superfícies operacionais, não cartões decorativos. Cabeçalho compacto em caixa
alta, alinhamento numérico à direita, estado textual e ação no fim da linha favorecem leitura
rápida. Em tela estreita a estrutura rola horizontalmente dentro do próprio contêiner — a
página nunca rola na horizontal.

### Drawer de detalhe

Lista à esquerda, detalhe à direita, ambos visíveis. O drawer é fixo enquanto a lista rola e
vira bloco empilhado abaixo de 1340px. É o padrão de todas as telas de gestão: catálogo,
clientes, pedidos, estoque, financeiro, revendedores e pré-cadastro.

## Do's and Don'ts

### Do:

- **Do** usar a escala de espaçamento de 4, 8, 12, 16, 24, 32 e 48px.
- **Do** manter a ação primária identificável em até dois segundos.
- **Do** preservar contraste WCAG 2.1 AA em ambos os temas.
- **Do** comunicar estado com texto, ícone e cor em conjunto.
- **Do** usar numerais tabulares em toda coluna comparável.
- **Do** dar tratamento visual próprio a toda ação que gera registro em auditoria.

### Don't:

- **Don't** usar dourado como texto pequeno sobre superfície clara — só o dourado de link.
- **Don't** pintar a vitrine do consumidor com token da Velaro.
- **Don't** hardcodar cor ou fonte no componente — usar os tokens deste arquivo.
- **Don't** entregar tela sem paridade dark/light.
- **Don't** tratar mobile como adaptação: o projeto é mobile-first.
- **Don't** usar a display em rótulo de campo ou célula de tabela.

---

## Registro de decisão — 02/09/2026

Quatro decisões aprovadas ao consolidar a identidade sobre os protótipos:

1. **A esmeralda substitui o bordô nos painéis internos.** Os protótipos do Portal e do Painel
   Master usavam vinho no item ativo e nos botões; a arte de marca não tem bordô. A paleta foi
   unificada na esmeralda.
2. **A ação em superfície clara é esmeralda, não dourada** — condição para WCAG AA.
3. **A faixa de coleções do site é escura**, como na arte de marca, e não clara como no protótipo.
4. **Sidebar e topbar em ônix esverdeado**, não em esmeralda cheia: a esmeralda fica reservada ao
   item ativo e às faixas institucionais, preservando a raridade da cor.

A tipografia foi decidida em separado — direção geométrica (Jost + Inter Tight), entre quatro
avaliadas em contexto. O comparativo fica registrado em `docs/mockups/05-tipografia.html`.
