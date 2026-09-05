# 3.4 · Estoque

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/estoque` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.4 · §6 |
| **Mockup** | [`docs/mockups/52-master-estoque.html`](../mockups/52-master-estoque.html) |
| **Mapa** | [mapa.html#master-estoque](../mockups/mapa.html#master-estoque) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `stock_locations` | novo (módulo Velaro) | code, name, description, is_default, is_active — o “Local de armazenamento” do protótipo |
| `stock_items` | novo (módulo Velaro) | product_variant_id, stock_location_id, atual, reservado, disponivel, minimo, reposicao |
| `stock_movements` | novo (módulo Velaro) | type (entrada\|saida\|ajuste\|reserva\|producao), qty, before, after, reason, actor_id, order_id |
| `production_requests` | novo (módulo Velaro) | product_variant_id, stock_location_id, qty_requested, qty_delivered, status, priority, due_date, requested_by, completed_at |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.stock.view`
- `velaro.stock.adjust`
- `velaro.stock.request_production`

## 3. Regras críticas

1. Controle por SKU/tamanho (aro). O estoque físico principal pertence à Velaro.
2. Ajuste manual, entrada/reabastecimento, solicitação de produção e histórico.
3. **Ajuste de estoque é ação sensível: exige log (§7).** `before`/`after` gravados no movimento.

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

- H1 "Estoque" + "Acompanhe disponibilidade dos produtos, saldos por tamanho, reservas e necessidade de reposição."
- **5 KPIs** (com variação vs. mês anterior): Itens em estoque 2.587 (↑8,4%) · Baixo estoque 87 (↑12,1%) ·
  Reservados 214 (0,0%) · Sob encomenda 63 (↑5,0%) · Reposições pendentes 23 (↑4,5%)
- **Filtros**: busca "Buscar por SKU, produto ou coleção…" · Categoria (Todas) · Status (Todos) · **Local** (Todos) ·
  **Filtros** · **Exportar** · botão **Nova movimentação +**
- **Tabela** (com checkbox de seleção) colunas: SKU · PRODUTO · COLEÇÃO · MATERIAL · **TAMANHOS** (ex. "10 - 33") ·
  **ESTOQUE ATUAL** · **RESERVADO** · **ESTOQUE MÍNIMO** · **REPOSIÇÃO** (Sugerida / Prioritária) ·
  STATUS (Em estoque / Baixo estoque / Reservado) · AÇÕES (⋯)
  Paginação "Mostrando 1 a 8 de 58 itens" · 10 por página
- **Drawer de detalhe**: nome + chip "Em estoque" · foto ·
  ficha: **SKU** (ALC-4MM-OU) · Coleção · Material · Acabamento · **Local de armazenamento** (Matriz - Cofre A1)
  - **Estoque por tamanho** (tabela): Tamanho | Estoque atual | Reservado | Disponível | Mínimo
    (faixas 10-14, 15-19, 20-24, 25-29, 30-33)
  - Ações: **Ajustar estoque** · **Registrar entrada** · **Solicitar produção**
  - **Reservas em aberto** 18 unidades reservadas ("Ver reservas →") · **Reposição sugerida** 20 unidades sugeridas ("Gerar pedido →")
  - **Últimas movimentações** (tipo, quantidade, data/hora, origem): Entrada +30 (Sistema) · **Reserva −6 (Pedido #5841)** ·
    Entrada +20 (Sistema) · **Ajuste manual −2 (Admin)** — link "Ver todas →"
  - **Ajuste manual rápido** ⓘ: stepper − 0 + · unidade (unidades) · botão **Aplicar ajuste**
