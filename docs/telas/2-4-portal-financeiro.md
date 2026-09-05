# 2.4 · Financeiro

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal/financeiro` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.4 · §6 |
| **Mockup** | [`docs/mockups/32-portal-financeiro.html`](../mockups/32-portal-financeiro.html) |
| **Mapa** | [mapa.html#portal-financeiro](../mockups/mapa.html#portal-financeiro) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `order_batches` | novo (módulo Velaro) | code, cut_date, due_date, status, total_amount |
| `orders` | core + colunas Velaro | batch_id, payment_status, total_amount — os pedidos que compõem o lote |
| `payments` | novo (módulo Velaro) | method (pix\|boleto\|transferencia), amount, due_date, paid_at, status, external_id, receipt_path |
| `invoices` | novo (módulo Velaro) | batch_id, number, series, amount, issued_at, pdf_path, xml_path |
| `invoice_items` | novo (módulo Velaro) | order_id, amount — rateio da nota do lote por pedido; é o que a coluna NF-E baixa |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. Mostra pedidos e lotes da conta, custo Velaro, data máxima de pagamento, status financeiro, NF emitida.
2. O revendedor paga **a Velaro** por meios B2B habilitados.
3. **Não existe** saldo do consumidor, carteira de recebíveis B2C nem saque.
4. Webhook de pagamento compara token com `hash_equals()`.

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

- Hero "FINANCEIRO" + "Acompanhe os pedidos feitos pela Tomazelli Alianças e seus clientes, controle lotes e
  pagamentos à Velaro, e consulte notas fiscais emitidas." + **card de alerta**: "Lote atual vence em
  28/05/2026 às 18h — Evite atrasos e mantenha seus pedidos em produção."
- **5 KPIs**: Total em aberto R$ 48.750,00 (Ver detalhes →) · Pedidos no lote atual 12 pedidos / R$ 48.750,00 ·
  Próximo vencimento 28/05/2026 às 18h · Notas fiscais emitidas 24 (Este mês) · Pagamentos confirmados R$ 96.320,00 (Este mês)
- **Abas**: Pedidos do lote atual · Todos os pedidos · Lotes anteriores + botão **Filtros**
- **Tabela de pedidos** colunas: PEDIDO (#5128 + "Pedido: 65012") · CLIENTE FINAL (avatar+nome) ·
  DATA DO PEDIDO (data + hora) · VALOR CUSTO VELARO · LOTE (24/2026) · PRAZO MÁXIMO PARA PAGAMENTO (data + hora) ·
  STATUS DO PAGAMENTO (Aguardando pagamento / Aguardando compensação / Pago) · **NF-E** (Baixar NF | —) · AÇÕES (⋯)
  Paginação "Mostrando 1 a 6 de 12 pedidos do lote 24/2026"
- **Tabela "Notas fiscais emitidas"** colunas: NÚMERO NF-E · DATA DE EMISSÃO · COMPETÊNCIA (Maio/2026) ·
  VALOR TOTAL · STATUS (Autorizada) · AÇÕES (Consultar) + link "Ver todas as notas fiscais emitidas →"
- **Drawer "Pagamento à Velaro / Pagar lote semanal"**:
  Lote selecionado (Lote semanal 24/2026 · 15/05/2026 a 21/05/2026) · **Data limite para pagamento** (28/05/2026 às 18h) ·
  Pedidos no lote (12 pedidos · R$ 48.750,00 · "Ver detalhes dos pedidos ⌄") ·
  **Resumo do pagamento**: Subtotal (custos Velaro) R$ 48.750,00 · Descontos ⓘ −R$ 0,00 · **Total a pagar R$ 48.750,00** ·
  aviso "A produção dos pedidos deste lote será liberada após a confirmação do pagamento." ·
  **Método de pagamento (radio)**: PIX (Aprovação imediata) · Boleto bancário (Compensação em até 1 dia útil) ·
  Transferência bancária (Compensação em até 1 dia útil) ·
  botão **Realizar pagamento à Velaro** + aviso "Após a confirmação do pagamento, o lote será liberado para produção
  e você receberá a confirmação por e-mail."
