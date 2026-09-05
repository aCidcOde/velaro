# 3.1 · Dashboard Master

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.1 |
| **Mockup** | [`docs/mockups/04-painel-master.html`](../mockups/04-painel-master.html) |
| **Mapa** | [mapa.html#master-dashboard](../mockups/mapa.html#master-dashboard) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `orders` | core + colunas Velaro | pedidos recebidos e em produção — contagem por operational_status e payment_status |
| `resellers` | novo (módulo Velaro) | status = pre_cadastro — fila de solicitações |
| `order_batches` | novo (módulo Velaro) | lotes em aberto e vencidos |
| `payments` | novo (módulo Velaro) | pagamentos B2B pendentes |
| `invoices` | novo (módulo Velaro) | notas emitidas no período |
| `support_tickets` | novo (módulo Velaro) | chamados abertos |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.dashboard.view`

## 3. Regras críticas

1. Visão consolidada da operação.
2. Fluxo do lote em destaque: recebimento → baixa → NF → pedidos aprovados → liberação.

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.
