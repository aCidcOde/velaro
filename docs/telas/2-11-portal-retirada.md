# 2.11 · Pedido pronto para retirada

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal/pedidos/{public_number} (estado) · job de notificação` |
| **Acesso** | Parceiro Premium + notificação ao cliente |
| **Referência contratual** | Anexo I §4.12 · §6 |
| **Mockup** | [`docs/mockups/38-portal-retirada.html`](../mockups/38-portal-retirada.html) |
| **Mapa** | [mapa.html#portal-retirada](../mockups/mapa.html#portal-retirada) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `order_velaro_details` | novo (módulo Velaro) | operational_status = pronto_retirada, arrived_at, retirado_em, retirado_por |
| `notification_logs` | novo (módulo Velaro) | type = pedido_pronto, channel, recipient_type (revendedor\|cliente), sent_at, status |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. Chegada na loja dispara comunicação automática por WhatsApp e/ou e-mail **em nome do revendedor**.
2. Notifica o consumidor **e** informa o revendedor no Portal.
3. Confirmação de retirada disponível por pedido.

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

> O protótipo mostra o **resultado da notificação no celular do cliente final**, não uma tela de sistema.
- **Notificação WhatsApp** (remetente = loja do revendedor, "Tomazelli Alianças"):
  "Olá, Maria Silva! Seu pedido #2412 já chegou à loja e está pronto para retirada."
  + 📍 Endereço: Rua das Alianças, 123 - Centro
  + 🕐 Horário: seg. a sex., das 9h às 18h.
  + ✓ "Estamos te esperando!"
- **Notificação e-mail (Gmail)**: assunto "Seu pedido está pronto para retirada" ·
  "Olá, Maria Silva. Informamos que o seu pedido #2412 já está disponível para retirada na loja Tomazelli Alianças."
  + ilustração + "SEU PEDIDO ESTÁ PRONTO!" + "Agradecemos a sua preferência."
> ⚠ Implicação: a tela do sistema é o **painel de disparo/histórico** no Portal — precisa existir com:
> gatilho automático na chegada, prévia da mensagem, canais (WhatsApp/e-mail), reenvio,
> log de envio e **confirmação de retirada**.
