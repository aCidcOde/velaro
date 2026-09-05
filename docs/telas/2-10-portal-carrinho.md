# 2.10 · Carrinho de compras (tablet / PDV)

| | |
|---|---|
| **Ambiente** | Vitrine white label |
| **Rota** | `GET /loja/{slug}/carrinho` |
| **Acesso** | Público / vendedor da loja |
| **Referência contratual** | Anexo I §4.10 · §4.11 · §6 |
| **Mockup** | [`docs/mockups/03-vitrine-pdv.html`](../mockups/03-vitrine-pdv.html) |
| **Mapa** | [mapa.html#portal-carrinho](../mockups/mapa.html#portal-carrinho) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `orders` | core + colunas Velaro | nasce em `draft`, vinculado a reseller_id e customer_id; subtotal_amount, engraving_amount, shipping_amount, discount_amount, total_amount — as quatro linhas de “Totais” mais o TOTAL |
| `order_items` | core + colunas Velaro | product_id, product_variant_id, quantity, unit_price = snapshot do preço B2C no momento da seleção, total_price |
| `order_item_engravings` | novo (módulo Velaro) | enabled, text, date, chars, price |
| `order_promotions` | novo (módulo Velaro) | promotion_id, type, discount_amount — origem da linha “Descontos” |
| `settings` | novo (módulo Velaro) | group, key, value — gravacao.max_chars e gravacao.preco, parametrizáveis |
| `reseller_stores` | novo (módulo Velaro) | slug, name, slogan, logo_path, banner_path, show_prices, pickup_only, payment_in_store, endereco — cabeçalho, banner, retirada na loja e pagamento no caixa |
| `reseller_store_categories` | novo (módulo Velaro) | category_id, position — abas Todos / Alianças / Solitários / Acessórios |
| `categories` | novo (módulo Velaro) | name, slug, position — rótulo de cada aba |
| `reseller_store_products` | novo (módulo Velaro) | product_id, position, is_featured — grade “Todos os produtos” |
| `reseller_price_settings` | novo (módulo Velaro) | pricing_model, multiplier, margin_global, rounding — origem do preço B2C exibido |
| `reseller_price_rules` | novo (módulo Velaro) | scope, collection_id, product_id, mode, value, rounding, priority — exceções que resolvem o preço B2C do card |
| `favorites` | novo (módulo Velaro) | product_id, reseller_store_id, customer_id, visitor_token — o ♡ dos cards |
| `products` | core + colunas Velaro | name, price, category_id, material_id, finish_id, permite_gravacao, gravacao_max_chars |
| `product_variants` | novo (módulo Velaro) | aro |
| `product_images` | novo (módulo Velaro) | path, is_primary — miniatura do card e da linha do carrinho |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. Atendimento presencial em tablet. O carrinho totaliza e orienta pagamento **no caixa do revendedor**.
2. **Nenhum** processamento de Pix, cartão, link de pagamento ou recebimento do consumidor pela Velaro/SVD.
3. Gravação adicional: Sim/Não, texto, data, limite de caracteres parametrizável e valor **discriminado separadamente**.

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

- Header da loja: logo TOMAZELLI ALIANÇAS · abas Todos os produtos / Alianças / Solitários / Acessórios · ♡
- Banner "SÍMBOLOS DE AMOR PARA TODA A VIDA" + "Alianças que unem histórias e eternizam momentos." (4 bolinhas)
- **Grade "Todos os produtos"** — 8 cards com ♡: nome · linha "Ouro 18k · Anel · <acabamento>" · "Aro: 18" ·
  **preço B2C** · "Ver detalhes"
  Aliança Clássica 4mm R$200,00 (Polido) · Diamantada 6mm R$265,00 (Diamantado) · Fosca 6mm R$165,00 (Fosca) ·
  Trabalhada 6mm R$210,00 (Trabalhada) · Conforto 4mm R$190,00 (Conforto) · Aro Quadrado 5mm R$220,00 (Polido) ·
  Tricolor 6mm R$310,00 (Polido) · Cravejada 4mm R$340,00 (Cravejada)
- **Painel CARRINHO DE COMPRAS** (com X para fechar) · chip "4 itens"
  Cada linha: miniatura · nome · "Ouro 18k · Anel · <acabamento>" · "Aro: 18" · stepper − 1 + · valor · **ícone lixeira**
- **GRAVAÇÃO ADICIONAL (OPCIONAL)**: "Deseja gravação adicional?" radio **Sim, desejo gravação** / Não, obrigado ·
  campo **Texto / nome** ("Ana ❤ Pedro") · campo **Data** (12/05/2025) · nota "Cobrada à parte por aliança." · **R$ 30,00**
- **Totais**: Subtotal R$970,00 · Adicional de gravação R$30,00 · Frete **Retirada na loja** · Descontos R$0,00 ·
  **TOTAL R$ 1.000,00**
- **RETIRADA EXCLUSIVA NA LOJA**: "Seu pedido estará disponível para retirada na loja Tomazelli Alianças."
- Botão **PAGAMENTO REALIZADO NO CAIXA DA LOJA** + nota "O pagamento será realizado no caixa da loja."
