# 0 · Login único com roteamento por perfil

| | |
|---|---|
| **Ambiente** | Transversal |
| **Rota** | `GET/POST /login` |
| **Acesso** | Público |
| **Referência contratual** | Anexo I §2 |
| **Mockup** | [`docs/mockups/20-login.html`](../mockups/20-login.html) |
| **Mapa** | [mapa.html#login](../mockups/mapa.html#login) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `users` | core (já existe no scaffold) | email, password, is_admin, two_factor_*, google_id |
| `users` | extensão do core | reseller_id — vínculo com o Parceiro Premium |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- gate `access-backend` decide o destino do Master

## 3. Regras críticas

1. **Um ponto de login** identifica o perfil e direciona ao ambiente correspondente.
2. Master → `/backend` · Parceiro Premium aprovado → `/portal` · Pré-cadastro → `/solicitacao/{protocolo}`.
3. Revendedor reprovado ou inativo não autentica.
4. Cliente final **não tem login** — existe só como `customers` na carteira do revendedor.
5. Login entra em `audit_logs` (§7 exige log de ações sensíveis).

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.
