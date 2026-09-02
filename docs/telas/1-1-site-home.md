# 1.1 · Página inicial B2B

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /` |
| **Acesso** | Público |
| **Referência contratual** | Anexo I §3.1 |
| **Mockup** | [`docs/mockups/01-site-publico.html`](../mockups/01-site-publico.html) |
| **Mapa** | [mapa.html#site-home](../mockups/mapa.html#site-home) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `collections` | novo (módulo Velaro) | name, slug, description, cover_path, position, is_active |
| `settings` | novo (módulo Velaro) | company.*, contact.* — telefone, e-mail, horário de atendimento |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- —

## 3. Regras críticas

1. Comunicação expressa de que a plataforma é exclusiva para lojistas.
2. Nenhum preço B2B renderizado nesta rota, nem em JSON embutido.
3. Sem venda direta ao consumidor final.

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.
