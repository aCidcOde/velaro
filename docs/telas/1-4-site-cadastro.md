# 1.4 · Cadastro como lojista

| | |
|---|---|
| **Ambiente** | Site público |
| **Rota** | `GET/POST /seja-revendedor` |
| **Acesso** | Público |
| **Referência contratual** | Anexo I §3.4 |
| **Mockup** | [`docs/mockups/12-site-cadastro.html`](../mockups/12-site-cadastro.html) |
| **Mapa** | [mapa.html#site-cadastro](../mockups/mapa.html#site-cadastro) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `resellers` | novo (módulo Velaro) | razao_social, nome_fantasia, cnpj, inscricao_estadual, responsavel_nome, responsavel_cpf, email, telefone, whatsapp, cep, logradouro, numero, complemento, bairro, cidade, uf, origem_contato, observacoes, status, protocolo |
| `reseller_documents` | novo (módulo Velaro) | type (contrato_social\|documento_socio\|cartao_cnpj), original_name, disk, path, size_bytes, mime |
| `reseller_cnaes` | novo (módulo Velaro) | code, description, is_primary, compatible |
| `users` | core (já existe no scaffold) | name, email, password — criado em estado de pré-cadastro |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- —

## 3. Regras críticas

1. Form Request obrigatório; CNPJ e CPF validados por regra de dígito.
2. Aceites (termos + LGPD) gravados com data, IP e versão do texto.
3. Upload de 3 documentos; nasce em `status = pre_cadastro`.
4. Dispara `VerifyResellerCnpjJob` — consulta externa nunca é síncrona.

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

- **Hero** "CADASTRO COMO LOJISTA" + "Solicite seu acesso à plataforma B2B Velaro. Cadastro exclusivo para lojistas
  com CNPJ e atividade compatível com o segmento." + "Após o cadastro, seu CNPJ e CNAE passam por validação automática
  e aprovação final da equipe Velaro."
- **Formulário "FAÇA SEU CADASTRO COMO LOJISTA"** — 3 colunas:
  | campo | obrig. | placeholder / tipo |
  | Razão social | * | "Digite a razão social da empresa" |
  | Nome fantasia | * | "Digite o nome fantasia" |
  | CNPJ | * | máscara 00.000.000/0000-00 |
  | Inscrição estadual | opcional | "Digite a inscrição estadual" |
  | Nome do responsável | * | "Digite o nome do responsável" |
  | WhatsApp | * | máscara (00) 00000-0000 |
  | E-mail | * | "seuemail@exemplo.com.br" |
  | Cidade / UF | * | select "Selecione a cidade / UF" |
  | Origem do contato | * | select "Selecione a origem" |
  | Criar senha | * | "Mínimo 8 caracteres" + olho |
  | Confirmar senha | * | "Confirme sua senha" + olho |
  | Observações | opcional | textarea, contador 0/300 |
  - **Aceites (3 checkboxes)**: "Declaro que sou lojista / empresa formalizada." ·
    "Autorizo a validação automática do meu CNPJ e CNAE." ·
    "Li e concordo com a Política de Privacidade e Termos de Uso." (com links)
  - Botão **ENVIAR CADASTRO ›** · nota "Você receberá atualizações por e-mail e WhatsApp."
- **Coluna lateral**: "COMO FUNCIONA" 4 passos (1 Cadastro · 2 Validação automática CNPJ + CNAE ·
  3 Aprovação final Velaro · 4 Acesso liberado) · "QUEM PODE SE CADASTRAR?" (Joalherias · Lojas de alianças ·
  Empresas com CNPJ · Atividade compatível com o segmento) · 4 selos (Ambiente seguro · Atendimento consultivo ·
  Condições exclusivas · Catálogo completo)
- **Faixa** 4 pilares: Compra direto da fábrica · Produção sob demanda · Suporte especializado · Entrega para todo o Brasil
> ⚠ FALTA no protótipo, exigido pelo Anexo I §3.4: **CPF do responsável/sócio**, **CEP, endereço, número,
> complemento, bairro** e o **upload de contrato social, documento do sócio e cartão/comprovante do CNPJ**.
