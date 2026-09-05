# 3.5 · Financeiro B2B

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/financeiro` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.5 · §6 |
| **Mockup** | [`docs/mockups/53-master-financeiro.html`](../mockups/53-master-financeiro.html) |
| **Mapa** | [mapa.html#master-financeiro](../mockups/mapa.html#master-financeiro) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `order_batches` | novo (módulo Velaro) | code, reseller_id, cut_date, due_date, status, total_amount, paid_at, shipped_at, arrived_at |
| `payments` | novo (módulo Velaro) | method, amount, paid_at, status, external_id, receipt_path, reconciled_by |
| `invoices` | novo (módulo Velaro) | batch_id, number, series, amount, issued_at, pdf_path, xml_path, provider, issued_by — a nota é **do lote** |
| `invoice_items` | novo (módulo Velaro) | order_id, amount — rateio da nota pelos pedidos do lote |
| `shipments` | novo (módulo Velaro) | code, order_batch_id, status, carrier, tracking_code, released_by, released_at, shipped_at — a liberação logística |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.finance.view`
- `velaro.finance.reconcile`
- `velaro.finance.issue_invoice`
- `velaro.finance.release_shipment`

## 3. Regras críticas

1. Fluxo obrigatório: **recebimento identificado → baixa financeira → NF emitida/enviada → pedidos aprovados → liberação para a remessa.**
2. A Velaro emite a NF da venda B2B ao lojista; o lojista emite a do consumidor final.
3. Baixa financeira e liberação logística são ações sensíveis: log obrigatório (§7).
4. Nenhuma remessa sai sem quitação confirmada do lote.

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

- H1 "Financeiro" + "Acompanhe recebimentos, baixas, notas fiscais e liberações de remessas."
- **5 KPIs**: Recebimentos do período R$ 128.560,00 (+18,6%) · Lotes aguardando baixa 12 (+9,1%) ·
  Notas fiscais emitidas 48 (+22,4%) · Remessas liberadas 31 (+14,3%) · Pagamentos pendentes R$ 32.450,00 (−8,7%)
- **Filtros**: busca "Buscar por lote, revendedor, pedido…" · Período (Todos) · Status (Todos) · **Situação** (Todos) ·
  Filtros · Exportar · botão **+ Novo recebimento**
- **Tabela** (checkbox) colunas: **LOTE** (SEM-2405 / ABR-2404) · REVENDEDOR · PERÍODO (Mai/2024) ·
  **PEDIDOS VINCULADOS** (#8765, #8766, #8767) · VALOR DO LOTE · DATA DE VENCIMENTO · DATA DE PAGAMENTO ·
  **STATUS FINANCEIRO** (Pago / Aguardando baixa / Em aberto) · **NOTA FISCAL** (NF enviada / −) ·
  **LIBERAÇÃO DE ENTREGA** (Liberado / Pendente) · AÇÕES
  Paginação "Mostrando 1 a 8 de 28 lotes" · 10 por página
- **Drawer "Lote #SEM-2405"** + chip **"Pago e liberado"**
  - **Fluxo financeiro e operacional** (5 passos com data/hora):
    1. Recebimento identificado — "Pagamento do lote confirmado" 27/05/2024 10:35
    2. Baixa financeira realizada — "Baixa registrada com sucesso" 10:42
    3. Nota fiscal emitida e enviada — "NF emitida e enviada ao revendedor" 11:05
    4. Pedidos aprovados — "Aprovados para produção/expedição" 11:20
    5. Liberação para entrega — "Liberado para próxima remessa semanal" 11:35
  - **Dados do revendedor**: nome, Responsável, telefone, e-mail
  - **Resumo do lote**: Lote · Período · Pedidos vinculados (3 pedidos) · Valor total
  - **Pedidos do lote** (tabela): Pedido | Cliente final | Valor do pedido | Status (Aprovado)
  - **Nota fiscal**: NF nº 000.024.587 · Data de emissão · chip "NF enviada"
  - **Liberação logística**: "Liberado para envio na próxima remessa semanal." · **Previsão de envio: 31/05/2024**
  - Ações: **Ver pedidos** · **Ver nota fiscal** · **✓ Confirmar liberação**
