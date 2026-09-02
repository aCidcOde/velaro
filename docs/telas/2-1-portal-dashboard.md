# 2.1 · Dashboard do Lojista

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.1 |
| **Mockup** | [`docs/mockups/02-portal-lojista.html`](../mockups/02-portal-lojista.html) |
| **Mapa** | [mapa.html#portal-dashboard](../mockups/mapa.html#portal-dashboard) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `orders + order_velaro_details` | extensão do core | agregações por operational_status e payment_status |
| `support_tickets` | novo (módulo Velaro) | contagem de chamados abertos |
| `customers + customer_velaro_details` | extensão do core | contagem por reseller_id |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- policy `ResellerScope` em toda query

## 3. Regras críticas

1. Indicadores: em andamento, produção, prontos para retirada, pendência financeira, chamados, clientes.
2. Nenhuma query sem filtro por `reseller_id` — vazamento entre revendedores é falha crítica.

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.
