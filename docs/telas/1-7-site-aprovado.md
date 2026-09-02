# 1.7 · Cadastro aprovado e liberação

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /solicitacao/{protocolo}/aprovado` |
| **Acesso** | Link transacional |
| **Referência contratual** | Anexo I §3.9 |
| **Mockup** | [`docs/mockups/15-site-aprovado.html`](../mockups/15-site-aprovado.html) |
| **Mapa** | [mapa.html#site-aprovado](../mockups/mapa.html#site-aprovado) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `resellers` | novo (módulo Velaro) | status = aprovado, approved_at, approved_by, code |
| `notification_logs` | novo (módulo Velaro) | type, channel (email\|whatsapp), recipient, sent_at, provider_message_id, status |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- —

## 3. Regras críticas

1. Aprovação libera o acesso de Parceiro Premium e cria o vínculo `users.reseller_id`.
2. Aviso transacional por e-mail e/ou WhatsApp — sempre via job.

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

- Nav já autenticada: mostra "Tomazelli Alianças ⌄" no lugar de ENTRAR
- **Hero** "CADASTRO APROVADO!" + "Parabéns! Seu cadastro foi aprovado com sucesso." + texto ·
  mockup de celular + **cartão de notificação WhatsApp** ("Olá! Seu cadastro foi aprovado. Seu acesso à plataforma B2B já está liberado.")
- **Card "SEU CADASTRO FOI APROVADO"** com alerta verde: "Seu acesso à plataforma B2B Velaro foi liberado com sucesso.
  Você já pode explorar o catálogo, consultar preços e realizar pedidos."
- **PRÓXIMO PASSO** + botão **ACESSAR MINHA PLATAFORMA ›** + nota "Enviamos os dados de acesso para seu e-mail e WhatsApp."
- **Coluna direita**: COMO FUNCIONA (4 passos) · "O QUE VOCÊ PODE FAZER AGORA?" (Explorar o catálogo completo ·
  Consultar preços exclusivos · Realizar pedidos com agilidade · Acompanhar seus pedidos e novidades) · 4 selos
