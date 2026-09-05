# 1.2 · Sobre nós

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET /sobre` |
| **Acesso** | Público |
| **Referência contratual** | Anexo I §3.2 |
| **Mockup** | [`docs/mockups/10-site-sobre.html`](../mockups/10-site-sobre.html) |
| **Mapa** | [mapa.html#site-sobre](../mockups/mapa.html#site-sobre) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `settings` | novo (módulo Velaro) | about.* — história, fábrica própria, diferenciais, mídia |
| `contact_leads` | novo (módulo Velaro) | origin = sobre — o CTA final “Vamos crescer juntos? · SOLICITAR ATENDIMENTO (Fale com um especialista)” encaminha para a **1.8**, que grava o lead; esta página não tem formulário próprio |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- —

## 3. Regras críticas

1. Página institucional: fábrica própria, qualidade, atendimento consultivo, logística, posicionamento B2B.
2. O CTA de atendimento leva para `GET /contato` (tela **1.8**); o lead nasce lá, com `origin = sobre`.

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

Nav: Início · Sobre Nós · Catálogo · Seja um Revendedor · Fale Conosco · Entrar · Solicitar atendimento (Exclusivo para lojistas)
- **Hero** eyebrow "QUEM É A VELARO" · H1 "A excelência por trás da Velaro." · 2 parágrafos
  (marca especializada na fabricação e distribuição de alianças e joias de alta qualidade para lojistas em todo o Brasil;
   tradição, tecnologia e design; acabamento impecável) · CTA "SEJA UM REVENDEDOR / Solicite seu cadastro" +
   "VER CATÁLOGO / Conheça nossas coleções" · foto de alianças
- **Nossa história** eyebrow "NOSSA HISTÓRIA" · H2 "Feita para lojistas. Feita para durar." · 2 parágrafos
  + 4 cards: FÁBRICA PRÓPRIA · QUALIDADE E ACABAMENTO SUPERIOR · ATENDIMENTO CONSULTIVO · ENTREGA PARA TODO O BRASIL
- **Pensado para o seu negócio** eyebrow · H2 "Pensado para abastecer vitrines com qualidade, consistência e confiança."
  + parágrafo + faixa de 3 imagens
- **Números que reforçam nossa essência** (4 itens 2×2): Produção com padrão premium · Atendimento nacional ·
  Coleções para diferentes perfis de loja · Parceria focada em revenda
- **CTA final** "Vamos crescer juntos?" + texto + SOLICITAR ATENDIMENTO (Fale com um especialista) + VER CATÁLOGO
- **Rodapé** Links rápidos (Início, Sobre Nós, Catálogo, Seja um Revendedor, Fale Conosco) ·
  Atendimento (WhatsApp +55 (68) 99457-7800, e-mail velaroab2b@velaro.com.br, seg–sex 8h–18h) ·
  Siga a Velaro (Instagram, Facebook, WhatsApp) · Formas de pagamento (Boleto, Visa, Mastercard, Elo) ·
  © + Política de Privacidade + Termos de Uso
