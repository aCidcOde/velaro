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
| `contact_leads` | novo (módulo Velaro) | origin = home — o “Fale conosco” do menu e os CTAs “Solicitar atendimento” / “Falar com especialista” não gravam nada aqui: levam para a **1.8**, que é a dona do formulário |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. Comunicação expressa de que a plataforma é exclusiva para lojistas.
2. Nenhum preço B2B renderizado nesta rota, nem em JSON embutido.
3. Sem venda direta ao consumidor final.
4. O “Fale conosco” do menu aponta para `GET /contato` (tela **1.8**); esta página só origina o lead, marcando `origin`.

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.
