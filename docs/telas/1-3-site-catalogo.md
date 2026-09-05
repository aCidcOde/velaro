# 1.3 · Catálogo público

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /catalogo · GET /catalogo/{colecao} · GET /produto/{slug}` |
| **Acesso** | Público |
| **Referência contratual** | Anexo I §3.3 |
| **Mockup** | [`docs/mockups/11-site-catalogo.html`](../mockups/11-site-catalogo.html) |
| **Mapa** | [mapa.html#site-catalogo](../mockups/mapa.html#site-catalogo) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `products` | core + colunas Velaro | name, slug, sku, description, is_active, collection_id, category_id, material_id, finish_id, largura_mm, formato, permite_gravacao |
| `product_variants` | novo (módulo Velaro) | sku, aro (tamanho), is_active |
| `product_images` | novo (módulo Velaro) | path, alt, position, is_primary |
| `collections` | novo (módulo Velaro) | name, slug, position, is_active — filtro “Coleção” |
| `categories` | novo (módulo Velaro) | name, slug, parent_id, position |
| `materials` | novo (módulo Velaro) | name, slug — ex.: Prata 950, Ouro Rosé 18k, Ouro Amarelo 18k, Aço |
| `finishes` | novo (módulo Velaro) | name, slug — ex.: Diamantada, Fosca, Polida, PVD Preto e Dourado, Texturizada, Cravejada |
| `favorites` | novo (módulo Velaro) | product_id, visitor_token — ícone de coração no card; o consumidor final não tem login |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. **Bloqueio de preço:** `products.price` nunca serializado nesta rota. A checagem entra em teste automatizado.
2. Exibe material, acabamento, largura, formato e características técnicas.
3. Preço e condição comercial só depois do cadastro aprovado.

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.

---

## 5. Inventário literal do protótipo

> Transcrição do que a tela do PDF mostra, campo a campo. É a régua de aceite:
> ausência de campo aqui descrito caracteriza **pendência de escopo**, não melhoria (Anexo I §9).

- **Hero** "CATÁLOGO VELARO" + "Coleções que unem design, qualidade e confiança." +
  "Conheça nossas coleções de alianças. Preços e condições comerciais são liberados após cadastro e aprovação do lojista."
- **Barra de filtros**: busca "Buscar modelos, coleções ou materiais…" · Coleção · Material · Acabamento · Largura ·
  seletor de visão "Todos os modelos"
- **Grade 6×2 = 12 produtos**, cada card: foto, ícone favoritar (coração), **SKU**, **nome**,
  linha "material | acabamento", linha "largura | acabamento polido", botão "Ver detalhes"
  SKUs e dados reais do protótipo:
  VL-DM-01 Diamond · Prata 950 | Diamantada · 5mm
  VL-PR-02 Premium Rosé · Ouro Rosé 18k | Fosca · 4mm
  VL-CL-03 Clássica · Ouro Amarelo 18k | Polida · 4mm
  VL-UB-04 Urbana · Prata 950 | Diamantada · 5mm
  VL-UB-05 Urbana Black · Aço | PVD Preto e Dourado · 6mm
  VL-PS-06 Personalizada · Ouro Amarelo 18k | Texturizada · 5mm
  VL-FS-07 Essence · Prata 950 | Fosca · 4mm
  VL-PR-08 Premium Cravejada · Ouro Rosé 18k | Cravejada · 3mm
  VL-DM-09 Diamond Heart · Prata 950 | Diamantada · 5mm
  VL-LI-10 Line · Ouro Amarelo 18k | Fosca · 4mm
  VL-FS-11 Essence Rosé · Ouro Rosé 18k | Fosca · 4mm
  VL-DM-12 Diamond Lux · Prata 950 | Cravejada · 4mm
- **Faixa de aviso** "Catálogo público sem preço interno." + "Condições exclusivas para lojistas disponíveis após aprovação do cadastro."
- **CTA final** "Seja um revendedor Velaro." + FAZER CADASTRO COMO LOJISTA (Acesso às condições exclusivas) +
  FALAR COM ESPECIALISTA (Atendimento personalizado)
- Rodapé igual ao da Sobre Nós
> Materiais reais: Prata 950 · Ouro Rosé 18k · Ouro Amarelo 18k · Aço
> Acabamentos reais: Diamantada · Fosca · Polida · PVD Preto e Dourado · Texturizada · Cravejada
> Larguras reais: 3mm · 4mm · 5mm · 6mm
