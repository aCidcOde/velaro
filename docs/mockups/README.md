# Velaro — Mockups dos 4 ambientes + Design System (proposta)

Tela inicial de cada ambiente, para fechar o design system **antes** do mapeamento
tela a tela (campo de banco, permissão, ambiente).

Servidor local: `php -S 127.0.0.1:8010 -t docs/mockups`.
Índice: <http://localhost:8010/index.html>

| # | Arquivo | Ambiente | Tela | Acesso |
|---|---------|----------|------|--------|
| 1 | `01-site-publico.html` | Site público | Página Inicial B2B | Sem login |
| 2 | `02-portal-lojista.html` | Portal do Lojista | Dashboard do Lojista | Parceiro Premium aprovado |
| 3 | `03-vitrine-pdv.html` | Vitrine white label + PDV | Vitrine + Carrinho (tablet) | Público, na URL do revendedor |
| 4 | `04-painel-master.html` | Painel Interno Velaro | Dashboard Master | Equipe Velaro |
| 5 | `05-tipografia.html` | — | Comparativo de tipografia (4 opções) | — |
| 6 | `06-mapa-inputs.html` | Design system | Mapa visual de inputs e estados | — |

Arquivos do sistema visual:

- `velaro-tokens.css` — **fonte única de verdade** de cor, tipografia, forma e espaçamento.
- `velaro-ui.css` — componentes base derivados dos tokens. Nenhum valor literal de cor.
- `06-mapa-inputs.html` — prancha visual dos campos, controles, estados e composições.
- `mapa-de-inputs.md` — regras visuais resumidas da prancha; sem modelagem funcional.
- `velaro-fonts.css` — as 4 direções avaliadas. **Usado apenas por `05-tipografia.html`**,
  como registro da decisão. Os 4 ambientes já carregam só o par escolhido.
- `_gen_rings.py` — gerador dos placeholders de produto. Ver seção 3.

---

## 1. Identidade

Base: a arte de marca oficial (hero em seda esmeralda com dourado). Todos os
valores foram amostrados pixel a pixel dessa arte e dos protótipos aprovados.

> **Esmeralda profunda é a superfície da marca. Dourado é a ação e o acento.
> Off-white é a superfície de trabalho. Nada mais compete.**

### Paleta

| Token | Hex | Papel |
|-------|-----|-------|
| `brand-950` | `#001a1d` | Topo de navegação, rodapé |
| `brand-900` | `#012227` | Faixa de marca, base do site |
| `brand-800` | `#04292c` | Item de navegação ativo |
| `brand-700` | `#073334` | Hero, gradiente de marca |
| `brand-500` | `#0e5b58` | **Ação em superfície clara** (AA sobre branco) |
| `gold-500`  | `#a97c3c` | **CTA sobre superfície escura** |
| `gold-300`  | `#dba765` | Texto, ícone e hairline sobre esmeralda |
| `gold-700`  | `#7a5623` | Link e borda dourada sobre superfície clara (AA) |
| `onyx-900`  | `#0c1817` | Sidebar dos painéis |
| `onyx-950`  | `#071110` | Topbar dos painéis |
| `gray-50`   | `#faf9f7` | Fundo da área de trabalho |

Os neutros são **quentes com viés esverdeado** — cinza neutro puro briga com o dourado.

### Tipografia — decidida

**Display `Jost` · Texto `Inter Tight`.** Direção geométrica, sem serifa — a linguagem
das joalherias nativas digitais. Luxo por silêncio e espaço, não por ornamento; e é o
par que melhor envelhece num painel que o lojista abre todo dia.

| Papel | Fonte | Onde aparece |
|-------|-------|--------------|
| Display | Jost | Hero, título de página, wordmark, valor de KPI |
| Texto | Inter Tight | Tabela, formulário, navegação, chip, rótulo |

A separação entre as duas vozes é de **peso e espacejamento**, não de família: a display
é leve (300–500) e aberta, a operacional é neutra e compacta. Como a geométrica é larga
por natureza, todo tamanho grande leva tracking negativo (`-.015em` a `-.03em`); o
wordmark, ao contrário, abre para `.30em`.

Não existe itálico de marca — no hero, a hierarquia entre as duas linhas vem de **peso
(300 → 500) e cor (branco → dourado)**.

Comparativo das 4 direções avaliadas: `05-tipografia.html` (mantido como registro da decisão).

### Regras nomeadas

**The Gold On Dark Rule.** A ação troca de cor conforme a superfície: sobre fundo
escuro a ação é **dourada** (como no hero da marca); sobre fundo claro a ação é
**esmeralda**. Dourado sobre branco não passa em AA para texto pequeno — por isso
`gold-700` é o único dourado permitido como texto/borda em superfície clara.

**The Two Status Families Rule.** Status do pedido e status do pagamento são
independentes (Anexo I §6). Nunca compartilham a mesma cor de chip na mesma linha:
operacional usa a família fria (neutro / info / violeta / verde), financeiro usa a
família quente (âmbar / verde / vermelho). O chip sempre combina **texto + cor** —
cor isolada não comunica situação.

**The White Label Rule.** A vitrine do consumidor nunca lê tokens Velaro. Ela é
pintada por `--shop-*`, carregado de `reseller_stores`. É o único ambiente com essa
regra, e ela é verificável: nenhuma referência a `--color-brand-*` no CSS da vitrine.

**The Flat By Default Rule.** Cartão e seção permanentes não combinam borda com
sombra larga. Sombra só em elemento que se sobrepõe ao fluxo (menu, modal, drawer).

**The Audited Action Rule.** Ação sensível — "Ver como revendedor", aprovar/reprovar
cadastro, ajuste de estoque, baixa financeira — tem tratamento visual próprio
(contorno dourado, nunca botão sólido comum) porque gera registro em `AuditLog`.

### Placeholder de produto

As imagens de aliança nos mockups são **SVG desenhado, não foto**: `_gen_rings.py`
gera um par de alianças com gradiente de metal, aresta interna em sombra, aresta
externa em luz e reflexo no quadrante superior esquerdo.

| Onde | Quantidade | Variantes |
|------|-----------|-----------|
| Site público — cards de coleção | 5 | `classica`, `diamond`, `premium`, `urbana`, `personaliz` |
| Vitrine — grade de produtos | 8 | acabamento espelha o nome do produto |
| Vitrine — linhas do carrinho | 4 | miniatura quadrada de 52px |
| Portal — prévia da vitrine | 1 | `classica` |

O acabamento é um parâmetro, não um desenho novo: metal (`ouro`, `rose`, `branco`,
`black`, `grafite`, `fosco`), pedras, friso interno, espessura da banda e forma
(redonda ou quadrada) se combinam. Por isso *Aro Quadrado* e *Conforto* não são a
mesma arte recolorida — a geometria muda.

```bash
python3 docs/mockups/_gen_rings.py   # regera; `VARIANTS` lista os acabamentos
```

**Quando as fotos reais chegarem**, o `<svg>` sai e entra `<img>` no mesmo contêiner
(`.collection__art`, `.prod__img`, `.line__img`) — o layout não muda. Até lá, o
placeholder comunica material e acabamento, que é o que a tela precisa provar.

---

## 2. Como isso entra no scaffold sem mutar o núcleo

O core do CodaFácil usa `--color-brand-*` em todos os componentes. `velaro-tokens.css`
**remapeia** essa escala para a esmeralda Velaro e adiciona `--color-gold-*` e
`--color-onyx-*`. Resultado: todo componente existente do core herda a marca sem
ser reescrito, e o núcleo compartilhado permanece intacto.

Ao aprovar, os tokens migram para `resources/css/panel/theme.css` como um bloco
`@theme` sobreposto, e `DESIGN.md` é atualizado com esta paleta e estas regras.

---

## 3. Decisões que precisam do seu aceite

1. **Esmeralda substitui o bordô dos protótipos internos.** Os protótipos do Portal
   e do Painel Master usavam vinho `#4e0c16` no item ativo e nos botões. A arte de
   marca não tem bordô. Optei por unificar tudo na esmeralda. Se o bordô for
   institucional e precisar voltar nos painéis, é uma troca de token.
2. **Ação em superfície clara é esmeralda, não dourada.** Necessário para WCAG AA.
3. **A faixa "Coleções" do site é escura**, como na arte de marca — e não clara como
   no protótipo em PDF.
4. **Sidebar e topbar em ônix esverdeado**, não em esmeralda cheia: a esmeralda fica
   reservada ao item ativo e às faixas institucionais, preservando a raridade da cor.
