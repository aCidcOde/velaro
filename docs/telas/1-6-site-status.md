# 1.6 · Status da solicitação

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /solicitacao/{protocol} · POST /solicitacao/{protocol}/documentos` |
| **Acesso** | Pré-cadastro (acesso limitado ao proprio acompanhamento) |
| **Referência contratual** | Anexo I §3.6 · §2 |
| **Mockup** | [`docs/mockups/14-site-status.html`](../mockups/14-site-status.html) |
| **Mapa** | [mapa.html#site-status](../mockups/mapa.html#site-status) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `resellers` | novo (módulo Velaro) | status (pending\|awaiting_info\|approved\|rejected\|inactive), approved_at, rejected_at, rejection_reason |
| `reseller_verifications` | novo (módulo Velaro) | status, cnpj_valido, empresa_ativa, cnaes_compativeis, score, checked_at |
| `reseller_status_events` | novo (módulo Velaro) | from_status, to_status, actor_id, note, created_at — alimenta a linha do tempo |
| `reseller_documents` | novo (módulo Velaro) | type, original_name, disk, path, size_bytes, mime — reenvio quando a Velaro pede informacao adicional |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. Linha do tempo: cadastro recebido → validação automática → aprovação final → liberação de acesso.
2. Estado de pré-cadastro dá acesso **somente** ao acompanhamento da própria solicitação.
3. Notificação a cada transição.
4. Em `awaiting_info` a tela abre o reenvio de documentos: é a contraparte da ação **Solicitar informações adicionais** do Painel Master (3.11), que até aqui não tinha resposta possível.
5. Documento reenviado registra evento em `reseller_status_events` e devolve a solicitação para `pending`.

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

- Eyebrow "ÁREA DO LOJISTA / PRÉ-CADASTRO" · H1 "STATUS DA SUA SOLICITAÇÃO" +
  "Acompanhe em tempo real a validação automática do seu cadastro e as próximas etapas até a liberação do acesso."
  · box no hero "Faça login para acompanhar sua solicitação" (seta apontando para ENTRAR)
- **Barra de identificação (5 campos)**: Parceiro (Tomazelli Alianças Ltda.) · **Protocolo (VEL-2026-0148)** ·
  Responsável (Edemar Tomazelli) · Login vinculado (contato@tomazelli.com.br) · Última atualização (Hoje, 10:42)
- **Stepper 4 etapas**: 1 Cadastro recebido (Concluído) · 2 Validação automática (Em andamento) ·
  3 Aprovação final Velaro (Aguardando) · 4 Acesso liberado (**Bloqueado até aprovação**)
- **Validação automática com IA** — 5 verificações com status individual:
  Consulta de CNPJ (Concluído) · Validação de CNAE (Concluído) · Compatibilidade com o segmento (Em análise) ·
  Verificação de dados cadastrais (Concluído) · Análise complementar de documentos (Em processamento)
- **Linha do tempo da solicitação** (evento + hora): Cadastro recebido 09:58 · Solicitação recebida 10:01 ·
  IA iniciou validação 10:05 · Validação de CNPJ concluída 10:10 · Validação de CNAE concluída 10:18 ·
  Em análise atual 10:42
- **Dados da solicitação**: CNPJ (32.123.456/0001-78) · Cidade/UF (Caxias do Sul / RS) ·
  Origem do contato (Indicação de lojista parceiro) · WhatsApp ((54) 9 9999-8888) · E-mail
- **Ações**: ATUALIZAR STATUS · FALAR COM NOSSA EQUIPE
- **Reenvio de documentos** — visível apenas quando a solicitação está em **Aguardando informações**:
  faixa de aviso com o pedido registrado pela equipe Velaro (a justificativa da ação
  "Solicitar informações adicionais" da tela 3.11) · campo de upload por tipo de documento
  (contrato social, documento do sócio, cartão CNPJ) com PDF/PNG/JPG até 5MB ·
  botão **ENVIAR DOCUMENTOS**. O envio devolve a solicitação para análise e entra na linha do tempo.
  Fora desse estado o bloco não aparece — o lojista não reenvia documento por conta própria.
- **Coluna direita**: card escuro "Status atual → Em validação automática" + texto ·
  "Como acompanhar" (3 itens) · "Próximas etapas" (3 itens numerados)
- Faixa 4 selos + rodapé ampliado (Fale conosco / Institucional / Para parceiros / Siga / Formas de pagamento)
