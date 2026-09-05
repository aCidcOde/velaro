# 2.9 · Vitrine para clientes (white label)

| | |
|---|---|
| **Ambiente** | Vitrine white label |
| **Rota** | `GET /loja/{slug} — ou domínio próprio` |
| **Acesso** | Público, no domínio/URL do revendedor |
| **Referência contratual** | Anexo I §4.9 · §6 |
| **Mockup** | [`docs/mockups/03-vitrine-pdv.html`](../mockups/03-vitrine-pdv.html) |
| **Mapa** | [mapa.html#portal-vitrine](../mockups/mapa.html#portal-vitrine) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `reseller_stores` | novo (módulo Velaro) | name, slogan, logo_path, banner_path, slug, domain, color_primary, color_secondary, color_background, color_text, is_active, published_at, updated_at (KPI “Última atualização”), whatsapp (“Iniciar atendimento”) — pinta 100% da tela; os toggles do bloco de configurações são own_brand_only, hide_supplier_brand, show_prices, pickup_only, payment_in_store |
| `reseller_store_categories` | novo (módulo Velaro) | category_id, position — “Categorias visíveis” e as abas da pré-visualização |
| `reseller_store_products` | novo (módulo Velaro) | product_id, position, is_featured — “Destaque de produtos” |
| `products` | core + colunas Velaro | catálogo exposto ao consumidor — name, slug, description, is_active, price (**base do cálculo B2C — nunca exibido na vitrine**), category_id, collection_id, material_id, finish_id, largura_mm, formato, permite_gravacao |
| `product_images` | novo (módulo Velaro) | path, alt, position, is_primary — foto do card na vitrine |
| `categories` | novo (módulo Velaro) | name, slug, position — origem das categorias visíveis |
| `collections` | novo (módulo Velaro) | name, slug, is_active — KPI “Coleções ativas” |
| `reseller_price_settings` | novo (módulo Velaro) | pricing_model, multiplier, margin_global, rounding — a base do cálculo |
| `reseller_price_rules` | novo (módulo Velaro) | scope, collection_id, product_id, mode, value, rounding, priority — resolve o **preço B2C** |
| `favorites` | novo (módulo Velaro) | product_id, reseller_store_id, visitor_token — coração na grade da vitrine |
| `orders` | core + colunas Velaro | reseller_id, created_at — KPI “Pedidos iniciados na vitrine” |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. **Zero marca Velaro ou SVD** perante o consumidor final. Vazamento de marca é pendência de escopo (§9).
2. Preço exibido é o B2C do revendedor — nunca o custo B2B.
3. Retirada somente na loja.

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

> Atenção: esta tela é o **painel de gestão da vitrine dentro do Portal**, não a vitrine em si.
- Hero "VITRINE PARA CLIENTES" + "Personalize e gerencie a vitrine da sua loja. É assim que seus clientes veem e
  escolhem as alianças e joias diretamente na loja."
- **4 KPIs**: Produtos publicados 268 (+18 novos esta semana) · Coleções ativas 12 (Ver coleções) ·
  Pedidos iniciados na vitrine 37 (+9 esta semana) · Última atualização 24/05/2025 10:32 (Atualizado há 2 horas)
- **CONFIGURAÇÕES DA VITRINE** (linhas com toggle/valor):
  Status da vitrine → chip **Ativa**
  Exibir apenas marca Tomazelli Alianças → toggle
  Mostrar preços ao cliente final → toggle
  Retirada somente na loja → toggle
  Pagamento realizado diretamente na loja → toggle
  Categorias visíveis → "Todas as categorias ›"
  Destaque de produtos → "12 produtos selecionados ›"
  Botões **Salvar configurações** · **Abrir vitrine ↗**
  Aviso: "A vitrine não processa pagamento online. O cliente escolhe os produtos e o pagamento é realizado diretamente na loja."
- **PRÉ-VISUALIZAÇÃO DA VITRINE**: header da loja (logo, Buscar, sacola com contador 0), abas
  Todas os produtos / Alianças / Solitários / Acessórios, carrossel com "Amor que se eterniza." +
  "Alianças e joias que celebram os melhores momentos." + CTA "Conheça nossa coleção" (4 bolinhas),
  grade de 5 produtos com coração/favorito, nome, **preço B2C** e chip "Parcela simulada na loja",
  botão "Ver todos os produtos"
  Produtos: Aliança Ouro 18k Tradicional 4mm R$1.890,00 · Diamantada 4mm R$2.160,00 ·
  Par de Alianças Ouro 18k 4mm R$5.490,00 · Filete de Pedra 4mm R$2.490,00 · Solitário com Diamante 20pts R$2.890,00
- **ACESSO RÁPIDO** (4): Abrir em tablet (Visualizar em tablet) · Copiar link da vitrine (Compartilhar link de acesso) ·
  Visualizar no celular (Como o cliente vê) · Iniciar atendimento (Falar com um atendente)
