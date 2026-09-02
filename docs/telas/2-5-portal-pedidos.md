# 2.5 · Pedidos — lista e detalhe

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal/pedidos · /portal/pedidos/{public_number}` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.5 |
| **Mockup** | [`docs/mockups/33-portal-pedidos.html`](../mockups/33-portal-pedidos.html) |
| **Mapa** | [mapa.html#portal-pedidos](../mockups/mapa.html#portal-pedidos) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `orders` | core (já existe no scaffold) | public_number, customer_id, total_amount, notes |
| `order_velaro_details` | novo (módulo Velaro) | reseller_id, batch_id, operational_status, payment_status, previsao, retirado_em, retirado_por |
| `order_items` | core (já existe no scaffold) | product_id, quantity, unit_price (snapshot imutável) |
| `order_item_engravings` | novo (módulo Velaro) | enabled, text, date, chars, price |
| `order_status_events` | novo (módulo Velaro) | from, to, actor_id, note, created_at — timeline |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. **Campos separados** para Status do Pedido e Status do Pagamento — são independentes (§6).
2. Detalhe registra eventual gravação adicional.
3. Rota sempre por `public_number`; `orders.id` interno nunca é exposto.

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

- Topbar desta tela: INÍCIO · VITRINE · CLIENTES · FINANCEIRO · PEDIDOS · PERSONALIZAÇÃO DA LOJA · PREÇOS E MARGENS · SUPORTE
- H1 "PEDIDOS" + "Acompanhe e gerencie todos os pedidos da sua loja."
- **6 KPIs** (cada com "Ver pedidos →"): Todos os pedidos 248 · Aguardando pagamento 18 · Em produção 36 ·
  Em transporte 24 · Entregues 168 · Cancelados 2
- **Filtros**: busca "Buscar por número do pedido, cliente ou produto…" · Período (Últimos 90 dias) ·
  Status do pedido (Todos) · Status do pagamento (Todos) · **Filtros avançados**
- **Tabela** colunas: PEDIDO · CLIENTE · DATA (data+hora) · ITENS · VALOR (CUSTO VELARO) ·
  **STATUS DO PEDIDO** · **STATUS DO PAGAMENTO** · ENTREGA PREVISTA · AÇÕES (⋯)
  Status do pedido observados: Pedido registrado · Em produção · Em transporte · Entregue · Cancelado
  Status do pagamento observados: Pendente · Pago · Aguardando compensação · Vencido
  Paginação "Exibindo 1 a 8 de 248 pedidos" · 1 2 3 … 31 · seletor "8 por página"
- **Drawer "Pedido #12548"**: chip de status · **Dados do pedido** (Cliente, Data do pedido,
  Status do pedido, Status do pagamento, Entrega prevista) ·
  **Card "Gravação interna"**: Solicitada (Sim) · Texto (Maria + João) · Limite (até 20 caracteres) · Custo adicional (R$ 35,00) ·
  **Itens do pedido (2)**: miniatura + nome + "Ouro 18k - Anat. / Polido" + "Aro: 18" + Qtd + valor ·
  **Resumo do pedido (custo Velaro)**: Subtotal dos itens R$ 450,00 · Gravação interna (1 unidade) R$ 35,00 ·
  Frete R$ 0,00 · Descontos R$ 0,00 · **Total do pedido (custo Velaro) R$ 485,00** ·
  botões **Ver detalhes** · **Faturamento / Pagamento**
