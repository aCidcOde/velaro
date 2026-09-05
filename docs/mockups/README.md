# Velaro — Mockups dos 4 ambientes + Design System (proposta)

Tela inicial de cada ambiente, para fechar o design system **antes** do mapeamento
tela a tela (campo de banco, permissão, ambiente).

Servidor local: `php -S 127.0.0.1:8010 -t docs/mockups`.
Índice: <http://localhost:8010/index.html>

**31 telas contratadas + 33 telas internas, todas navegáveis** — 69 arquivos `.html` na pasta.
Índice completo em `index.html`; mapa consolidado em `mapa.html`; diagrama do banco em
`er-banco.html`; documentação por tela em [`docs/telas/`](../telas/README.md).

| Etapa | Ambiente | Telas | Arquivos |
|-------|----------|-------|----------|
| 0 | Transversal | 1 | `20-login` |
| 1 | Site público | 7 | `01-site-publico` · `10-site-sobre` · `11-site-catalogo` · `12-site-cadastro` · `13-site-enviada` · `14-site-status` · `15-site-aprovado` |
| 2 | Portal do Lojista | 9 | `02-portal-lojista` · `30-portal-catalogo` · `31-portal-clientes` · `32-portal-financeiro` · `33-portal-pedidos` · `34-portal-loja` · `35-portal-precos` · `36-portal-suporte` · `38-portal-retirada` |
| 2 | Vitrine white label | 2 | `03-vitrine-pdv` (vitrine + carrinho PDV) · `37-portal-vitrine` (gestão) |
| 3 | Painel Interno Velaro | 12 | `04-painel-master` · `50-master-clientes` · `51-master-config` · `52-master-estoque` · `53-master-financeiro` · `54-master-pedidos` · `55-master-produtos` · `56-master-promocoes` · `57-master-relatorios` · `58-master-revendedores` · `59-master-precadastro` · `60-master-suporte` |

As 33 telas internas (detalhe, formulário de criação, subtela de configuração, relatório) são os
destinos dos botões dessas 31 — numeradas `07`, `08`, `16`, `17`, `18`, `21`, `39`–`43`,
`51a`–`51h`, `52a`, `52b`, `53a`, `53b` e `61`–`70`.

Além das telas: `05-tipografia` (registro da decisão de fonte), `06-mapa-inputs` (prancha de
inputs), `mapa` (spec das 31 telas) e `er-banco` (diagrama do banco).

Arquivos do sistema visual:

- `velaro-tokens.css` — **fonte única de verdade** de cor, tipografia, forma e espaçamento.
- `velaro-ui.css` — componentes base derivados dos tokens. Nenhum valor literal de cor.
- `06-mapa-inputs.html` — prancha visual dos campos, controles, estados e composições.
- `mapa-de-inputs.md` — regras visuais resumidas da prancha; sem modelagem funcional.
- `velaro-screens.css` — padrões que se repetem nas telas: filtros, formulário, drawer,
  timeline, stepper, abas, toggle, checklist, tabela, gráfico.
- `velaro-fonts.css` — as 4 direções tipográficas avaliadas. **Usado apenas por `05-tipografia.html`**,
  como registro da decisão.

---

## 0. Build — leia antes de editar qualquer coisa

> ### ⚠️ `docs/telas/*.md` são ARQUIVOS GERADOS
>
> Os 31 documentos de tela e o `docs/telas/README.md` saem do `_build_docs.py`, que lê
> `_mapa.py` + `_notas.md`. **Editar um `.md` à mão é trabalho perdido:** a próxima execução
> do gerador sobrescreve o arquivo inteiro, sem aviso e sem conflito.
>
> Toda correção de tabela, campo, rota, permissão ou regra de tela entra no **`_mapa.py`**,
> e só depois se regenera. O mesmo vale para `mapa.html` e `er-banco.html` — nenhum dos três
> é fonte, os três são saída.

### Qual Python

**Obrigatório `/opt/homebrew/bin/python3.14`** (qualquer Python ≥ 3.12 serve; 3.14 é o que está
instalado). O `python3` do PATH neste Mac resolve para `/usr/bin/python3`, que é **3.9** e morre
com `SyntaxError` ao compilar `_build_er.py` — a f-string aninhada com aspas do mesmo tipo
(PEP 701) só existe a partir do 3.12. Os outros geradores compilam no 3.9, mas não há motivo
para manter dois interpretadores em jogo: use o 3.14 em toda a cadeia.

Os scripts usam caminhos relativos (`_mapa.py`, `_notas.md`, saída em `../telas`), então
**precisam rodar com `docs/mockups/` como diretório corrente**.

### A cadeia de geração

```
_notas.md ─┐
           ├──(_build_docs.py)──> docs/telas/*.md + docs/telas/README.md
_mapa.py ──┤
           ├──(_mapa.py, como __main__)──> mapa.html
           │
           └──(_build_er.py)──> er-banco.html
                ^ o schema (56 caixas, 90 relações, 18 lacunas registradas) está
                  declarado DENTRO do próprio _build_er.py, transcrito do
                  information_schema; o _mapa.py entra só para dizer quais das 31
                  telas usam cada tabela. O script NÃO lê as migrations.

_ui.py + _gen_rings.py ──(_build_*.py)──> as 62 páginas .html geradas
```

`_mapa.py` tem dois papéis e é fácil esquecer o segundo: é **o banco de dados da documentação**
(consumido por `_build_docs.py` e `_build_er.py`) e, rodado direto, é **o gerador do `mapa.html`**.
Mexer nele sem regenerar os três destinos deixa a documentação em três versões diferentes.

`_mapa.py` e `_build_er.py` só escrevem sob `if __name__ == "__main__"`, então importá-los
(como `_build_docs.py` faz) não sobrescreve nada. `_build_docs.py` **não** tem essa guarda:
importá-lo regrava os 31 `.md` na hora.

### Receita

```bash
cd docs/mockups

P=/opt/homebrew/bin/python3.14

# 1) telas HTML
$P _build_site.py && $P _build_portal.py && $P _build_master.py \
  && $P _build_novas_site.py && $P _build_novas_portal.py \
  && $P _build_novas_config.py && $P _build_novas_master.py

# 2) mapa, documentação por tela e diagrama do banco
$P _mapa.py && $P _build_docs.py && $P _build_er.py
```

O passo 1 e o passo 2 são independentes entre si; dentro de cada passo a ordem não importa.
`_gen_rings.py` **não** entra na receita: ele é uma biblioteca importada pelo `_ui.py`, não um
gerador de assets. Rodá-lo direto só escreve `_teste-alianças.html`, uma prancha de contato dos
17 acabamentos — útil para revisar o desenho, irrelevante para o build.

### O que NÃO tem gerador

`01-site-publico`, `02-portal-lojista`, `03-vitrine-pdv`, `04-painel-master`, `05-tipografia`,
`06-mapa-inputs` e **`index.html`** são escritos à mão: nenhum `_build_*.py` os produz.
Regenerar a pasta não os toca, e `religar()` não passa por eles — os links deles são literais
no HTML.

Consequência prática: toda mudança em `_ui.py` que altere a marcação de um shell precisa ser
repetida à mão nesses sete. Foi o que aconteceu quando a busca do topo virou `<details>`: as
geradas acompanharam, as escritas à mão ficaram com a marcação antiga e a busca perdeu a caixa.

### Para onde cada botão aponta

Todo botão nasce com `href="#"` (o default de `btn()`). Na hora de gravar a tela, o `W()` de
cada `_build_*.py` chama `religar()` do `_ui.py`, que decide o destino de cada um:

1. se o rótulo estiver na tabela `DESTINOS` (por tela) ou `DESTINOS_GLOBAIS`, aponta para a **tela**;
2. senão, aponta para `mapa.html#<slug-da-tela>`.

O caso 2 é decisão consciente, não esquecimento: botão de **ação** (Salvar, Exportar, Aprovar)
não navega num protótipo estático, e o mapa explica em que tela aquilo vive. Hoje são
**2.473 links para telas** e **352 para o mapa** (282 em âncora de verbete, 70 no atalho do topo),
com **zero** `href="#"` e zero link quebrado.

> Este passo já existiu só no HTML versionado, fora do gerador. Quem regenerasse a pasta
> perdia todos os destinos de uma vez, sem erro nenhum. Se for mexer em `W()`, mantenha o `religar()`.

### Fontes de geração (não são entregáveis — são o como)

| Arquivo | Papel |
|---------|-------|
| `_notas.md` | Transcrição literal, campo a campo, do protótipo em PDF — 27 blocos numerados por tela. Vira a seção 5 de cada `docs/telas/*.md`. |
| `_mapa.py` | Spec das 31 telas: rota, acesso, tabelas/campos, permissões, regras. Fonte de `mapa.html`, de `docs/telas/*.md` e do uso por tela no `er-banco.html`. |
| `_ui.py` | Biblioteca de componentes: shells dos 4 ambientes, 20 blocos reutilizáveis, `religar()` e as tabelas `DESTINOS`. |
| `_gen_rings.py` | Placeholders de aliança em SVG, por acabamento. Importado pelo `_ui.py`. |
| `_build_site.py` · `_build_portal.py` · `_build_master.py` | Geram 27 das 31 telas contratadas — as outras 4 são escritas à mão. |
| `_build_novas_site.py` · `_build_novas_portal.py` · `_build_novas_config.py` · `_build_novas_master.py` | Geram as 33 telas internas. |
| `_build_docs.py` | Gera `docs/telas/*.md` **e** `docs/telas/README.md`, a partir de `_mapa.py` + `_notas.md`. |
| `_build_er.py` | Gera `er-banco.html` — schema transcrito do `information_schema` dentro do próprio script, mais as 18 lacunas de modelagem e o uso por tela vindo do `_mapa.py`. |

### Rótulo de origem das tabelas

`_mapa.py` classifica cada tabela citada por uma tela em três valores — e só três:

| Valor | Significado | Tabelas |
|-------|-------------|---------|
| `novo` | Nasceu no módulo Velaro | 49 tabelas |
| `extensao` | Tabela do core que ganhou colunas Velaro | `products` · `orders` · `order_items` · `customers` · `users` |
| `core` | Tabela do scaffold lida como está | `audit_logs` e as 4 `acl_*` |

O valor `core/novo` foi eliminado — não use. As 31 telas citam **59 tabelas** ao todo.

### O banco, em números

**71 tabelas** · **101 chaves estrangeiras** · **54 migrations** do módulo Velaro
(o schema real, extraído do `information_schema`). Dessas, 49 nasceram no módulo e 5 tabelas do
core receberam colunas próprias.

A regra "nenhuma tabela do núcleo é alterada" foi abandonada por decisão registrada. Em
consequência, as três tabelas de extensão 1:1 que a documentação antiga previa —
`product_attributes`, `order_velaro_details` e `customer_velaro_details` — **não existem**:
seus campos foram absorvidos por `products`, `orders` e `customers`.

Decisões, política de exclusão de FKs e o fechamento das 18 lacunas:
[`docs/banco-de-dados.md`](../banco-de-dados.md).

### Responsividade

O alvo é **não haver rolagem horizontal de 360px para cima**. Isso é medido, não estimado:

```bash
cd /tmp && node audita-mobile.js 390 844   # o script vive no scratchpad da sessão
```

Duas armadilhas que já custaram caro e estão travadas por convenção:

- **Nunca emita `style="grid-template-columns:…"`.** Estilo inline vence media query, e por
  causa disso o colapso mobile de `.split`/`.split3` era ignorado em 15 telas. Use
  `style="--gcols:…"` — o CSS lê a variável e a neutraliza no celular.
- **Tabela larga vai dentro de `.table-scroll`** (é o que `tabela()` já faz). Sem isso o
  `min-width: 680px` da `.table` estica a página inteira em vez de rolar por dentro.
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
independentes (Anexo I §6) — `orders.operational_status` e `orders.payment_status`
são colunas separadas, nenhuma derivada da outra. Nunca compartilham a mesma cor
de chip na mesma linha: operacional usa a família fria (neutro / info / violeta / verde), financeiro usa a
família quente (âmbar / verde / vermelho). O chip sempre combina **texto + cor** —
cor isolada não comunica situação.

**The White Label Rule.** A vitrine do consumidor nunca lê tokens Velaro. Ela é
pintada por `--shop-*`, carregado das quatro colunas de cor de `reseller_stores`
(`color_primary`, `color_secondary`, `color_background`, `color_text`). É o único
ambiente com essa regra, e ela é verificável: nenhuma referência a `--color-brand-*`
no CSS da vitrine.

**The Flat By Default Rule.** Cartão e seção permanentes não combinam borda com
sombra larga. Sombra só em elemento que se sobrepõe ao fluxo (menu, modal, drawer).

**The Audited Action Rule.** Ação sensível — "Ver como revendedor", aprovar/reprovar
cadastro, ajuste de estoque, baixa financeira — tem tratamento visual próprio
(contorno dourado, nunca botão sólido comum) porque gera registro em `audit_logs`.

### Placeholder de produto

As imagens de aliança nos mockups são **SVG desenhado, não foto**: `_gen_rings.py`
monta um par de alianças com gradiente de metal, aresta interna em sombra, aresta
externa em luz e reflexo no quadrante superior esquerdo. O SVG é escrito **em linha**
no HTML durante o build — `_ui.py` importa o módulo e chama `svg()` / `thumb()`. Não
existe arquivo de imagem na pasta.

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
# Prancha de contato dos 17 acabamentos (`VARIANTS` lista todos).
# Só para revisar o desenho — o build das telas não depende deste comando.
cd docs/mockups && /opt/homebrew/bin/python3.14 _gen_rings.py   # escreve _teste-alianças.html
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
