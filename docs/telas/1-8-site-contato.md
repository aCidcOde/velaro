# 1.8 · Fale conosco

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /contato · POST /contato` |
| **Acesso** | Público |
| **Referência contratual** | Anexo I §3.1 · §3.2 |
| **Mockup** | [`docs/mockups/19-site-contato.html`](../mockups/19-site-contato.html) |
| **Mapa** | [mapa.html#site-contato](../mockups/mapa.html#site-contato) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `contact_leads` | novo (módulo Velaro) | name, email, phone, company, subject, message, origin, status, handled_by, handled_at — **a única tela que grava esta tabela**; as chamadas “Fale conosco”, “Solicitar atendimento” e “Falar com especialista” das telas 1.1, 1.2 e 1.3 desembocam aqui, cada uma marcando o seu `origin` |
| `settings` | novo (módulo Velaro) | contact.* — telefone, WhatsApp, e-mail e horário de atendimento do bloco de canais diretos; os mesmos valores do rodapé do site, lidos com `is_public = true` |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —
- A fila de leads é lida no Painel Interno; `handled_by` referencia `users.id` da equipe Velaro

## 3. Regras críticas

1. Rota pública com Form Request e throttle — é formulário aberto na internet.
2. **Lead não é pré-cadastro:** o envio não cria `resellers`, não cria `users` e não libera preço. Quem quer revender é encaminhado para a **1.4**.
3. **Lead não é chamado:** quem ainda não é revendedor não tem `support_tickets`; a mensagem nasce em `contact_leads` com `status = new`.
4. `origin` guarda a página de partida (home, sobre, catálogo, contato) e a fila anda por `status`/`handled_by`/`handled_at`.
5. Consentimento LGPD obrigatório para enviar, gravado com data, IP, user agent e versão do texto. `reseller_consents` **não serve** — exige `reseller_id`, e o lead ainda não é revendedor: as colunas de aceite precisam nascer em `contact_leads`.
6. Nenhum preço B2B renderizado nesta rota.

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

> O protótipo aprovado não tem esta tela: "Fale Conosco" era só uma âncora para o bloco de CTA
> do catálogo, e `contact_leads` existia no banco sem formulário que a alimentasse. A transcrição
> abaixo é do mockup `19-site-contato.html`, e vale como régua de aceite igual às demais.
- **Hero** eyebrow "ATENDIMENTO A LOJISTAS" · H1 "FALE CONOSCO" ·
  "Uma conversa direta com quem fabrica a aliança." + parágrafo (dúvida sobre coleção, prazo de produção,
  condição comercial ou solicitação de cadastro em andamento; retorno em até 1 dia útil) ·
  **aviso no hero**: "A Velaro é fábrica e vende somente para lojistas com CNPJ. Este canal é atendimento —
  quem quer revender precisa do pré-cadastro."
- **Barra de canais diretos (4 células)**, com os mesmos valores do rodapé do site (`settings` grupo `contact.*`):
  Telefone comercial +55 (16) 99487-7800 · WhatsApp +55 (16) 99487-7800 ·
  E-mail comercial vendas@velaro.com.br · Horário de atendimento "Segunda a sexta, das 8h às 18h"
- **Formulário "ENVIE SUA MENSAGEM"** — 2 colunas:
  | campo | obrig. | placeholder / tipo |
  | Nome | * | "Como podemos chamar você?" |
  | E-mail | * | "seuemail@exemplo.com.br" |
  | Telefone / WhatsApp | * | máscara (00) 00000-0000 · hint "O retorno pode sair pelo mesmo número, por WhatsApp." |
  | Empresa | opcional | "Nome fantasia da sua loja" · hint "ajuda a direcionar o atendimento" |
  | Assunto | * | select "Selecione o assunto", largura total |
  | Mensagem | * | textarea largura total · hint "Até 1.000 caracteres." |
  - **Opções do select Assunto**: Condições comerciais e catálogo · Acompanhar solicitação de cadastro ·
    Suporte a lojista já aprovado · Prazo de produção e entrega · Imprensa e parcerias · Outro assunto
  - **Consentimento (1 checkbox obrigatório)**: "Li e concordo com a Política de Privacidade e autorizo a
    Velaro a usar os dados acima para responder a este contato." (link para a 17) +
    nota "O aceite é obrigatório para enviar e fica registrado com data, hora, IP e a versão do texto vigente —
    a mesma prova exigida no cadastro de lojista."
  - Botão **ENVIAR MENSAGEM ›** (largura total) · nota "Registramos de qual página do site você veio,
    para direcionar o atendimento."
- **Coluna lateral (3 cards)**:
  1. "QUER REVENDER A VELARO?" (escuro) — "Este formulário **não substitui o pré-cadastro**" + 4 negativas
     (Não cria cadastro de revendedor · Não libera preço nem condição comercial · Não dá acesso ao Portal do
     Lojista · Não dispensa o envio dos documentos) + botão **QUERO SER REVENDEDOR** → tela 1.4
  2. "COMO FUNCIONA O ATENDIMENTO" (escuro) — 3 passos: 1 Mensagem recebida (entra na fila com a página de
     origem registrada) · 2 Triagem pelo assunto (o contato ganha responsável e data de retorno) ·
     3 Resposta em até 1 dia útil (e-mail ou WhatsApp)
  3. "JÁ É LOJISTA VELARO?" — pedido, financeiro e produção se resolvem pelo chamado dentro do Portal +
     botões ENTRAR NO PORTAL (tela 0) e ACOMPANHAR SOLICITAÇÃO (tela 1.6)
- **Faixa de aviso** "Contato não é chamado." + "Quem ainda não é revendedor não abre chamado de suporte:
  a mensagem vira um lead na fila comercial, com responsável e data de atendimento registrados."
- **Faixa** 4 pilares + rodapé iguais aos das demais telas do site
> ⚠ FALTA no banco: o aceite LGPD desta tela não tem onde ser gravado. `reseller_consents` exige
> `reseller_id` NOT NULL e o lead não é revendedor; `contact_leads` precisa das colunas de aceite
> (data, IP, user agent e versão do texto).
