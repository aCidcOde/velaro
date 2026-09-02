# 2.9 · Vitrine para clientes (white label)

| | |
|---|---|
| **Ambiente** | Vitrine white label |
| **Rota** | `GET /loja/{slug} — ou domínio próprio` |
| **Acesso** | Público, no domínio/URL do revendedor |
| **Referência contratual** | Anexo I §4.9 · §6 |
| **Mockup** | [`docs/mockups/03-vitrine-pdv.html`](../mockups/03-vitrine-pdv.html) |
| **Mapa** | [mapa.html#portal-vitrine](../mockups/mapa.html#portal-vitrine) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `reseller_stores` | novo (módulo Velaro) | logo, cores, banner, domínio — pinta 100% da tela |
| `products` | core (já existe no scaffold) | catálogo exposto ao consumidor |
| `product_attributes` | novo (módulo Velaro) | categorias, destaques, características técnicas |
| `reseller_price_rules` | novo (módulo Velaro) | resolve o **preço B2C** |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- —

## 3. Regras críticas

1. **Zero marca Velaro ou SVD** perante o consumidor final. Vazamento de marca é pendência de escopo (§9).
2. Preço exibido é o B2C do revendedor — nunca o custo B2B.
3. Retirada somente na loja.

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

> Atenção: esta tela é o **painel de gestão da vitrine dentro do Portal**, não a vitrine em si.
- Hero "VITRINE PARA CLIENTES" + "Personalize e gerencie a vitrine da sua loja. É assim que seus clientes veem e
  escolhem as alianças e joias diretamente na loja."
- **4 KPIs**: Produtos publicados 268 (+18 novos esta semana) · Coleções ativas 12 (Ver coleções) ·
  Pedidos iniciados na vitrine 37 (+9 esta semana) · Última atualização 24/05/2025 10:32 (Atualizado há 2 horas)
- **CONFIGURAÇÕES DA VITRINE** (linhas com toggle/valor):
  Status da vitrine → chip **Ativa**
  Exibir apenas marca Tomazelli Alianças → toggle
  Mostrar preços ao cliente final → toggle
  Retirada somente na loja → toggle
  Pagamento realizado diretamente na loja → toggle
  Categorias visíveis → "Todas as categorias ›"
  Destaque de produtos → "12 produtos selecionados ›"
  Botões **Salvar configurações** · **Abrir vitrine ↗**
  Aviso: "A vitrine não processa pagamento online. O cliente escolhe os produtos e o pagamento é realizado diretamente na loja."
- **PRÉ-VISUALIZAÇÃO DA VITRINE**: header da loja (logo, Buscar, sacola com contador 0), abas
  Todas os produtos / Alianças / Solitários / Acessórios, carrossel com "Amor que se eterniza." +
  "Alianças e joias que celebram os melhores momentos." + CTA "Conheça nossa coleção" (4 bolinhas),
  grade de 5 produtos com coração/favorito, nome, **preço B2C** e chip "Parcela simulada na loja",
  botão "Ver todos os produtos"
  Produtos: Aliança Ouro 18k Tradicional 4mm R$1.890,00 · Diamantada 4mm R$2.160,00 ·
  Par de Alianças Ouro 18k 4mm R$5.490,00 · Filete de Pedra 4mm R$2.490,00 · Solitário com Diamante 20pts R$2.890,00
- **ACESSO RÁPIDO** (4): Abrir em tablet (Visualizar em tablet) · Copiar link da vitrine (Compartilhar link de acesso) ·
  Visualizar no celular (Como o cliente vê) · Iniciar atendimento (Falar com um atendente)
