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
| `orders + order_velaro_details` | core + novo | nasce em `draft`, vinculado a reseller_id e customer_id |
| `order_items` | core (já existe no scaffold) | unit_price = snapshot do preço B2C no momento da seleção |
| `order_item_engravings` | novo (módulo Velaro) | enabled, text, date, chars, price |
| `settings` | novo (módulo Velaro) | gravacao.max_chars, gravacao.preco — parametrizáveis |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

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
