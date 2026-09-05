# 2.2 · Catálogo Revendedor

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal/catalogo` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.2 |
| **Mockup** | [`docs/mockups/30-portal-catalogo.html`](../mockups/30-portal-catalogo.html) |
| **Mapa** | [mapa.html#portal-catalogo](../mockups/mapa.html#portal-catalogo) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `products` | core + colunas Velaro | price — **custo B2B**, visível só aqui; name, sku, description, is_active, collection_id, material_id, finish_id, largura_mm, formato (filtros), prazo_entrega_dias (ficha do drawer) e is_made_to_order (chip “Sob encomenda”); created_at (selo “NOVO” e ordenação “Lançamento”) |
| `product_variants` | novo (módulo Velaro) | sku, aro — disponibilidade por tamanho |
| `product_images` | novo (módulo Velaro) | path, position, is_primary — foto do card e galeria de miniaturas do drawer |
| `stock_items` | novo (módulo Velaro) | disponivel, stock_location_id — o portal apenas consulta |
| `collections` | novo (módulo Velaro) | name, slug, is_active — select “Coleção” e KPI “Coleções ativas” |
| `materials` | novo (módulo Velaro) | name, slug — select “Material” |
| `finishes` | novo (módulo Velaro) | name, slug — select “Acabamento” |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- policy: revendedor aprovado

## 3. Regras críticas

1. Exibe o custo B2B Velaro. **Esse custo nunca chega à vitrine do consumidor.**
2. Estoque é somente leitura: o controle físico pertence à Velaro (§6).
3. Inclusão de item em pedido a partir daqui.

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

- Hero "CATÁLOGO REVENDEDOR" + "Catálogo com custo exclusivo para lojistas, disponibilidade e ferramentas para criação de pedidos."
- **4 KPIs**: Total de produtos 1.248 (Ver catálogo →) · Em estoque 892 (Produtos disponíveis) ·
  Sob encomenda 356 (Produtos sob pedido) · Coleções ativas 18 (Ver coleções →)
- **Filtros**: busca "Buscar produto, código ou referência…" · Coleção (Todas) · Material (Todos) ·
  Acabamento (Todos) · Largura (Todas) · Disponibilidade (Todas) · **Limpar filtros** ·
  **Ordenar por (Lançamento)** · **Exportar catálogo**
- **Grade 5×2 = 10 cards**, cada um: selo "NOVO" (quando aplicável), foto, **SKU**, nome,
  **preço de custo** (R$), chip de disponibilidade (Em estoque / Sob encomenda), "Ver detalhes", "+ Adicionar"
  Dados reais: ALC4-4MM Aliança Clássica 4mm R$15,00 · ALTD-4MM Diamantada 4mm R$17,90 ·
  ALTA-6MM Trabalhada 6mm R$22,90 (sob encomenda) · ALTA-4MM Tradicional R$13,00 (sob encomenda) ·
  ALT-4MM Fosca 6mm R$26,00 · ALCON-6MM Conforto 6mm R$21,00 · ALIN-6MM Anatômica 6mm R$31,90 ·
  ALF-5MM Fina 5mm R$14,90 · ALBH-6MM Brilhante 6mm R$29,00 (sob encomenda) · ALDZ-6MM Dupla Zircônia 6mm R$27,00
- **Painel lateral de detalhe do produto** (drawer): nome · "Ref. ALC4-4MM" · chip "Em estoque" ·
  foto grande + **galeria de miniaturas** (5 + seta) · "CUSTO PARA O LOJISTA" R$ 15,00 +
  **"Preço interno. Não exibir a clientes."** · ficha: Material (Ouro 18k) · Largura (4mm) · Acabamento (Polido) ·
  Prazo de entrega (Até 2 dias úteis) · Disponibilidade (Em estoque) · **Quantidade (unid.)** com −/+ ·
  botão **Adicionar ao pedido**
- **Aviso**: "Os preços exibidos são exclusivos para revendedores e não devem ser compartilhados com clientes finais."
