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
| `agregações` | — | pedidos, pré-cadastros, produção, pendências financeiras, NF, solicitações, suporte |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

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
