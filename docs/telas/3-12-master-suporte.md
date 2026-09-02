# 3.12 · Suporte — atendimento

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/suporte · /backend/suporte/{code}` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.12 |
| **Mockup** | [`docs/mockups/60-master-suporte.html`](../mockups/60-master-suporte.html) |
| **Mapa** | [mapa.html#master-suporte](../mockups/mapa.html#master-suporte) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `support_tickets` | novo (módulo Velaro) | code, reseller_id, order_id, customer_id, subject, category, priority, status, assignee_id |
| `support_messages` | novo (módulo Velaro) | author_role, body, is_internal_note |
| `support_attachments` | novo (módulo Velaro) | original_name, path, size_bytes |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- `velaro.support.view`
- `velaro.support.reply`
- `velaro.support.assign`
- `velaro.support.resolve`

## 3. Regras críticas

1. Atendimento aos chamados abertos pelos revendedores.
2. **A conversa é Velaro ↔ revendedor.** O cliente final aparece apenas como pessoa vinculada ao pedido e não participa.
3. Observação interna nunca é visível ao revendedor.

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

- H1 "Suporte" + "Gerencie suas solicitações de suporte e acompanhe o atendimento."
- "← Voltar para todas as solicitações" + chip "Em atendimento"
- **Cabeçalho do chamado**: **#SUP-2025-0598** + chip "Prioridade: Média" · título "Troca de tamanho de aliança -
  tamanho errado" · "Criado em 18/05/2025 às 10:24 · Atualizado há 35 minutos" ·
  botões **Imprimir** · **Atualizar status ⌄**
- **Faixa de vínculos (5)**: Revendedor (nome + **Código: TOM-0001**) · Contato (nome, telefone, e-mail) ·
  **Pedido relacionado** (PED-2025-0587 + "Ver pedido ↗") · **Cliente final** (Maria Cliente — "(Cliente final)") ·
  Assunto
- **Conversa** (thread): cada mensagem com avatar, autor, **badge de papel** (Revendedor / **Atendente**),
  data/hora, corpo e **anexo** (foto_alianca_recebida.jpg · 1.2 MB, com download)
- **Editor de resposta** com abas **Responder** · **Observação interna** · anexo 📎 · emoji ·
  botão **Enviar resposta ⌄**
- **Detalhes da solicitação** (painel): Status · Prioridade · **Categoria** (Troca / Produto) · Assunto ·
  Revendedor (+código) · Contato · Cliente final · Pedido relacionado · **Data de criação** · **Última atualização** ·
  **Produtos** (Aliança Classic 4mm (Par) · Ouro 18K - Aro 18) · **Tags** (Troca, Tamanho, Aliança, Ouro 18K) ·
  **Ambiente** (Produção) · **Canal de origem** (Portal do Revendedor) · **Navegador** (Google Chrome 124.0.0.0) ·
  **Sistema operacional** (Windows 11) · **IP de acesso** (189.12.34.56)
- **Ações rápidas**: **Resolver solicitação** · **Solicitar informações adicionais** · **Reprovar cadastro**(sic)
- **Histórico de status** (timeline): Em atendimento (Desde 18/05 10:31 · Equipe Velaro Suporte) ·
  Aguardando resposta do revendedor (18/05 10:31) · Aberta (18/05 10:24 · **Portal do Revendedor**)
- **Anexos** (lista com nome, data/hora, tamanho e download) + botão **Adicionar anexo**
- **Atendimento**: Responsável (Equipe Velaro Suporte) + botão **Transferir atendimento**
