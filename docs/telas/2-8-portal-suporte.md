# 2.8 · Suporte — chamados

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal/suporte · /portal/suporte/{code}` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.8 · §5.12 |
| **Mockup** | [`docs/mockups/36-portal-suporte.html`](../mockups/36-portal-suporte.html) |
| **Mapa** | [mapa.html#portal-suporte](../mockups/mapa.html#portal-suporte) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `support_tickets` | novo (módulo Velaro) | code, reseller_id, order_id, customer_id, subject, category, priority, status, assignee_id, channel |
| `support_messages` | novo (módulo Velaro) | author_id, author_role (revendedor\|velaro), body, is_internal_note |
| `support_attachments` | novo (módulo Velaro) | original_name, path, size_bytes, mime |
| `help_categories` | novo (módulo Velaro) | name, slug, position — as categorias da central de ajuda |
| `help_articles` | novo (módulo Velaro) | type (faq\|guia\|video), title, slug, excerpt, body, video_url, file_path, is_published |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. Vinculável a pedido, financeiro, troca, defeito, prazo, ajuste, vitrine ou dúvida operacional.
2. Conversa é **Velaro ↔ revendedor**. O cliente final aparece só como pessoa vinculada ao pedido.
3. `is_internal_note` nunca é exposto ao revendedor.

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

- Hero "SUPORTE" + "Estamos aqui para ajudar você a vender mais e ter a melhor experiência."
- **ACESSO RÁPIDO** (5 atalhos): Abrir chamado (Fale com nossa equipe) · Perguntas frequentes (Tire suas dúvidas) ·
  Guias e manuais (Aprenda a usar a plataforma) · Vídeos tutoriais (Assista e aprenda) · WhatsApp (Atendimento rápido)
- **MEUS CHAMADOS** — filtros: busca "Buscar por número, assunto ou mensagem…" · Status (Todos) ·
  Categoria (Todas) · Período (Últimos 90 dias) · **Filtros**
- **Tabela** colunas: Nº DO CHAMADO · ASSUNTO (título + resumo da mensagem) · CATEGORIA ·
  STATUS · PRIORIDADE (bolinha + label) · ÚLTIMA ATUALIZAÇÃO · AÇÕES (⋯)
  **Categorias reais**: Pedidos · Financeiro · Vitrine / Loja · Personalização da loja
  **Status reais**: Em atendimento · Aguardando retorno · Em análise · Respondido
  **Prioridades**: Alta (vermelho) · Média (amarelo) · Baixa (verde)
  Paginação "Exibindo 1 a 5 de 23 chamados" · 5 por página
- **STATUS DO SUPORTE** ⓘ (4 números): Total de chamados 23 · Em atendimento 5 · Aguardando retorno 8 · Respondidos 10
- **HORÁRIO DE ATENDIMENTO**: Segunda a Sexta-feira · 08h às 18h (horário de Brasília) · Exceto feriados
- **CANAIS DE ATENDIMENTO**: Chat online (Disponível na plataforma — Online) · WhatsApp ((11) 99999-9999 — Online) ·
  E-mail (suporte@velaroaliancas.com.br — 24h) · Telefone ((11) 3000-0000 — 08h às 18h)
- **Central de ajuda completa** + link "Acessar central de ajuda →"
