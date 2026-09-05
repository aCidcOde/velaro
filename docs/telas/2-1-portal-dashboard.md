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
| `orders` | core + colunas Velaro | reseller_id, customer_id, public_number, operational_status, payment_status, total_amount, previsao — KPIs e tabela “Últimos pedidos”; todo somatório da tela é escopado por reseller_id |
| `customers` | core + colunas Velaro | reseller_id, name — KPI “Clientes cadastrados” e coluna “Cliente final” da tabela “Últimos pedidos” |
| `support_tickets` | novo (módulo Velaro) | reseller_id, code, status — KPI “Chamados abertos” e pendências |
| `reseller_stores` | novo (módulo Velaro) | name, slogan, logo_path, banner_path, domain, is_active, published_at — cartão “Vitrine da sua loja” |
| `reseller_price_settings` | novo (módulo Velaro) | reseller_id, margin_global — item “definir margem padrão” do checklist de configuração |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

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
