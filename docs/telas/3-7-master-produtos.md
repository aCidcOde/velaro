# 3.7 · Produtos — catálogo mestre

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/produtos` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.7 |
| **Mockup** | [`docs/mockups/55-master-produtos.html`](../mockups/55-master-produtos.html) |
| **Mapa** | [mapa.html#master-produtos](../mockups/mapa.html#master-produtos) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `products` | core + colunas Velaro | name, slug, sku, description, price (B2B), is_active, collection_id, category_id, material_id, finish_id, largura_mm, formato, permite_gravacao, gravacao_max_chars, prazo_entrega_dias, is_made_to_order |
| `product_variants` | novo (módulo Velaro) | sku, aro/tamanho, is_active |
| `product_images` | novo (módulo Velaro) | path, alt, position, is_primary — carrossel e “Gerenciar imagens” |
| `collections` | novo (módulo Velaro) | name, slug, position, is_active — aba “Coleções”; tabela própria, não enum |
| `categories` | novo (módulo Velaro) | name, slug, parent_id, position — aba “Categorias” e select da lista |
| `materials` | novo (módulo Velaro) | name, slug, position, is_active — aba “Materiais” |
| `finishes` | novo (módulo Velaro) | name, slug, position, is_active — aba “Acabamentos” |
| `stock_items` | novo (módulo Velaro) | disponivel — “Estoque disponível” no resumo do produto |
| `resellers` | novo (módulo Velaro) | status — “Revendedores ativos” no resumo do produto |
| `product_revisions` | novo (módulo Velaro) | action, actor_id, before, after — “Histórico de alterações” |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.products.view`
- `velaro.products.manage`
- `velaro.products.duplicate`
- `velaro.products.deactivate`

## 3. Regras críticas

1. Novo produto, SKU/referência, categoria, coleção, material, acabamento, largura, formato, aro, preço B2B, disponibilidade, gravação, imagens, status, duplicação, histórico, inativação.
2. Produto inativo não aparece para revendedores.
3. Mudança de preço **não** afeta pedido já criado — `unit_price` é snapshot.

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

- H1 "Produtos" + "Gerencie seu catálogo de produtos." + **Exportar** · **+ Novo produto**
- **Abas do módulo**: Lista de produtos · **Categorias** · **Coleções** · **Materiais** · **Acabamentos** · **Gravações**
- **Coluna esquerda — lista**: busca "Buscar produto por nome, código ou referência…" · ícone filtro ·
  select "Todas as categorias"
  Cada linha: miniatura · nome · **código** (ALC-4MM-18K) · material (Ouro 18K) · **preço** · chip **Ativo**
  Paginação "Mostrando 1 a 8 de 248 produtos" · Itens por página 10
- **Coluna central — "Editando produto"** + nome + chip Ativo + botão **Ver produto**
  **Abas do formulário**: Informações gerais · **Preço e disponibilidade** · **Especificações** · **Gravação** · **Imagens**
  Campos de "Informações gerais":
  | Nome do produto * | Código / Referência * |
  | Categoria * (Alianças Tradicionais) | Coleção (Classic) |
  | Material * (Ouro 18K) | Acabamento * (Polido) |
  | Largura (4 mm) | Formato (Reta) |
  | Descrição (textarea) |
  **Toggle "Produto ativo"** — "Produtos inativos não aparecem para os revendedores."
  Botões **Cancelar** · **Salvar alterações**
- **Coluna direita**: **Imagem do produto** (carrossel com setas + 4 miniaturas + **Gerenciar imagens**) ·
  **Resumo do produto** (Material · Largura · Acabamento · Status · **Preço base** · **Estoque disponível** (128 unidades) ·
  **Revendedores ativos** (42)) ·
  **Ações rápidas**: **Duplicar produto** · **Histórico de alterações** · **Inativar produto**
