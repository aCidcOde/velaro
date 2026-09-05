# 1.5 · Solicitação enviada

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /solicitacao/{protocolo}/enviada` |
| **Acesso** | Link com protocolo |
| **Referência contratual** | Anexo I §3.5 |
| **Mockup** | [`docs/mockups/13-site-enviada.html`](../mockups/13-site-enviada.html) |
| **Mapa** | [mapa.html#site-enviada](../mockups/mapa.html#site-enviada) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `resellers` | novo (módulo Velaro) | protocolo, created_at |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. Confirmação de recebimento com protocolo e resumo.
2. Informa prazo de análise e canais de acompanhamento.

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

- **Hero** "SOLICITAÇÃO ENVIADA" + "Recebemos seu cadastro com sucesso!" + texto de acompanhamento
- **Card sucesso**: "Cadastro recebido com sucesso!" + 2 parágrafos +
  botões "ACOMPANHAR MINHA SOLICITAÇÃO" e "← VOLTAR AO INÍCIO"
- **Card STATUS ATUAL** (escuro): "Em análise" + "Assim que houver novidades, entraremos em contato."
- **RESUMO DA SOLICITAÇÃO**: Razão social · CNPJ · Responsável · E-mail · Cidade/UF · Origem do contato
  (ex.: Tomazelli Alianças Ltda. · 12.345.678/0001-90 · Edemar Tomazeli · contato@tomazelli.com.br · São Paulo / SP · Site)
- **ACOMPANHE O ANDAMENTO DO SEU CADASTRO** — stepper 4 etapas:
  1. Cadastro recebido (Concluído) · 2. Validação automática CNPJ e CNAE (Em andamento) ·
  3. Aprovação final Velaro (Aguardando) · 4. Acesso liberado (Aguardando)
- **COMO ACOMPANHAR SUA SOLICITAÇÃO**: Notificações por e-mail · Avisos via WhatsApp · Acompanhe no site
- **Aviso IMPORTANTE**: "Guarde seu e-mail e senha. Eles serão seu acesso para acompanhar e entrar na plataforma
  quando seu cadastro for aprovado."
- **Faixa** 4 selos: Ambiente seguro · Validação automática · Atendimento consultivo · Catálogo liberado
> ⚠ FALTA: **número de protocolo** exigido pelo Anexo I §3.5.
