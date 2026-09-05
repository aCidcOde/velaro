# 3.6 · Pedidos — ciclo completo

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/pedidos · /backend/pedidos/{public_number}` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.6 · §6 · §7.2 |
| **Mockup** | [`docs/mockups/54-master-pedidos.html`](../mockups/54-master-pedidos.html) |
| **Mapa** | [mapa.html#master-pedidos](../mockups/mapa.html#master-pedidos) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `orders` | core + colunas Velaro | public_number, customer_id, reseller_id, batch_id, shipment_id, operational_status, payment_status, previsao, arrived_at, retirado_em, retirado_por, retirado_por_documento, retirado_por_customer_id, notes, created_at (data/hora do pedido na lista e no detalhe), e os valores subtotal_amount, discount_amount, total_amount |
| `order_items` | core + colunas Velaro | product_id, product_variant_id, quantity, unit_price, total_price |
| `order_item_engravings` | novo (módulo Velaro) | text, date — coluna ESPECIFICAÇÕES (“Gravação: M ❤ S”) |
| `order_status_events` | novo (módulo Velaro) | scope, from_status, to_status, actor_id, note, created_at — timeline de 7 etapas e histórico de atualizações |
| `order_batches` | novo (módulo Velaro) | code, arrived_at, retirado_em, retirado_por, retirado_por_documento — confirmação de chegada/retirada **por lote inteiro** |
| `shipments` | novo (módulo Velaro) | code, status, carrier, tracking_code, tracking_url, released_by, released_at, shipped_at, estimated_at, delivered_at — o transporte, mesmo sem API da transportadora (§7.2) |
| `payments` | novo (módulo Velaro) | method, status, paid_at — “Forma de pagamento (PIX)” |
| `notification_logs` | novo (módulo Velaro) | recipient_type (revendedor\|cliente), channel, status, sent_at — card “Notificações enviadas” |
| `customers` | core + colunas Velaro | name, document — Cliente (nome + CPF) |
| `resellers` | novo (módulo Velaro) | razao_social, nome_fantasia, code, logradouro, numero, cidade, uf, cep — Revendedor e endereço de entrega (loja do revendedor) |
| `products` | core + colunas Velaro | name, material_id |
| `product_variants` | novo (módulo Velaro) | sku, aro — coluna CÓDIGO e ESPECIFICAÇÕES |
| `product_images` | novo (módulo Velaro) | path, is_primary — miniatura do item |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.orders.view`
- `velaro.orders.update_status`
- `velaro.orders.confirm_pickup`
- `velaro.orders.confirm_batch_pickup`

## 3. Regras críticas

1. Estados: registrado → pagamento confirmado → produção em andamento → produção finalizada → em transporte → pronto para retirada → retirado.
2. **Status operacional é independente do status financeiro.**
3. Confirmação de chegada/retirada por pedido **e** por lote inteiro.
4. Campos e status de transporte já entram no escopo mesmo sem a API da transportadora (§7.2).

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

- H1 "Pedidos" + "Acompanhe, gerencie e visualize todos os pedidos realizados." + **Exportar** · **+ Novo pedido**
- **5 KPIs/abas**: Todos 1.248 · Aguardando pagamento 24 · Em produção 58 · **Em transporte 16** (selecionado) · Concluídos 1.150
- **Coluna esquerda — lista de pedidos** com filtros (busca "Buscar pedido, cliente ou código…", ícone filtro,
  Status (Todos), Período (Últimos 30 dias)). Cada cartão: **#PED-2025-05678** · chip de status · data ·
  **cliente** · "Revendedor: João Ferreira Joias & Cia" · **Total R$ 3.240,00** · "3 itens" · seta
  Paginação "Mostrando 1 a 5 de 1.248 pedidos" · Itens por página 10
- **Coluna central — detalhe**: "← Voltar para pedidos" · **Pedido #PED-2025-05678** + chip "Em transporte" ·
  "Data do pedido: 15/05/2025 às 10:32" · menu **⋮ Mais ações**
  - Faixa de dados: **Cliente** (nome + CPF) · **Revendedor** (nome + **Código: RV-0156**) · **Total do pedido** ·
    **Forma de pagamento** (PIX) · **Lote** (L-2025-0312)
  - **Itens do pedido (3)** — tabela: PRODUTO (miniatura + nome + material) · **CÓDIGO** (ALC-4MM-18K) ·
    **ESPECIFICAÇÕES** (Aro: 18 · **Gravação: M ❤ S**) · QTD · VALOR UNIT. · TOTAL
    + Subtotal e **Total**
  - **Endereço de entrega (loja do revendedor)**: nome, rua, cidade/UF, CEP
  - **Observações**: "Cliente solicitou gravação interna."
- **Coluna direita — Status do pedido (timeline de 7 etapas com data/hora)**:
  ✓ Pedido realizado 15/05 10:32 · ✓ Pagamento confirmado 15/05 10:45 · ✓ Produção em andamento 16/05 09:10 ·
  ✓ Produção finalizada 21/05 14:25 · ● **Em transporte para a loja** 22/05 08:30 ·
  ○ Pronto para retirada na loja (Aguardando chegada na loja) · ○ Retirado pelo cliente (Aguardando confirmação)
  - Aviso: "Quando o pedido chegar na loja, o revendedor e o cliente serão notificados automaticamente que o pedido
    está pronto para retirada."
  - **Confirmação de retirada**: "Confirme abaixo quando o pedido for retirado pelo cliente na loja." ·
    botões **Confirmar retirada do lote inteiro** e **Confirmar retirada por pedido**
  - **Notificações enviadas**: Revendedor (João Ferreira Joias & Cia) — "Enviado em 22/05/2025 às 08:30" chip Enviado ·
    Cliente (Maria Silva Oliveira) — chip Enviado
- **Histórico de atualizações** (rodapé, 2 colunas com data/hora + descrição):
  22/05 08:30 Pedido em transporte — "Pedido saiu da fábrica e está a caminho da loja do revendedor."
  21/05 14:25 Produção finalizada · 16/05 09:10 Produção em andamento ·
  15/05 10:45 Pagamento confirmado ("via PIX") · 15/05 10:32 Pedido realizado ("Pedido criado pelo revendedor.")
