# Documentação das telas — Velaro B2B

Uma página por tela: rota, perfil de acesso, tabelas e campos, permissões, regras críticas,
critérios de aceite e o inventário literal do protótipo aprovado.

Fontes: Anexo I (escopo funcional e critérios de aceite), Plano de Negócio VELARO B2B
e a apresentação "PLATAFORMA B2B VELARO ALIANÇAS".

- Mockups navegáveis: [`docs/mockups/`](../mockups/index.html)
- Mapa consolidado das 32 telas: [`docs/mockups/mapa.html`](../mockups/mapa.html)

**Como ler.** A seção 5 de cada documento é a régua de aceite: ela transcreve o que o
protótipo mostra. Ausência de campo, regra, permissão, automação ou relatório ali descrito
caracteriza **pendência de escopo**, e não melhoria opcional (Anexo I §9).

### Transversal

| # | Tela | Mockup |
|---|------|--------|
| 0 | [Login único com roteamento por perfil](0-login.md) | [mockup](../mockups/20-login.html) |

### Site público

| # | Tela | Mockup |
|---|------|--------|
| 1.1 | [Página inicial B2B](1-1-site-home.md) | [mockup](../mockups/01-site-publico.html) |
| 1.2 | [Sobre nós](1-2-site-sobre.md) | [mockup](../mockups/10-site-sobre.html) |
| 1.3 | [Catálogo público](1-3-site-catalogo.md) | [mockup](../mockups/11-site-catalogo.html) |
| 1.4 | [Cadastro como lojista](1-4-site-cadastro.md) | [mockup](../mockups/12-site-cadastro.html) |
| 1.5 | [Solicitação enviada](1-5-site-enviada.md) | [mockup](../mockups/13-site-enviada.html) |
| 1.6 | [Status da solicitação](1-6-site-status.md) | [mockup](../mockups/14-site-status.html) |
| 1.7 | [Cadastro aprovado e liberação](1-7-site-aprovado.md) | [mockup](../mockups/15-site-aprovado.html) |
| 1.8 | [Fale conosco](1-8-site-contato.md) | [mockup](../mockups/19-site-contato.html) |

### Portal do Lojista

| # | Tela | Mockup |
|---|------|--------|
| 2.1 | [Dashboard do Lojista](2-1-portal-dashboard.md) | [mockup](../mockups/02-portal-lojista.html) |
| 2.2 | [Catálogo Revendedor](2-2-portal-catalogo.md) | [mockup](../mockups/30-portal-catalogo.html) |
| 2.3 | [Clientes / CRM](2-3-portal-clientes.md) | [mockup](../mockups/31-portal-clientes.html) |
| 2.4 | [Financeiro](2-4-portal-financeiro.md) | [mockup](../mockups/32-portal-financeiro.html) |
| 2.5 | [Pedidos — lista e detalhe](2-5-portal-pedidos.md) | [mockup](../mockups/33-portal-pedidos.html) |
| 2.6 | [Personalização da loja](2-6-portal-loja.md) | [mockup](../mockups/34-portal-loja.html) |
| 2.7 | [Preços e margens](2-7-portal-precos.md) | [mockup](../mockups/35-portal-precos.html) |
| 2.8 | [Suporte — chamados](2-8-portal-suporte.md) | [mockup](../mockups/36-portal-suporte.html) |
| 2.11 | [Pedido pronto para retirada](2-11-portal-retirada.md) | [mockup](../mockups/38-portal-retirada.html) |

### Vitrine white label

| # | Tela | Mockup |
|---|------|--------|
| 2.9 | [Vitrine para clientes (white label)](2-9-portal-vitrine.md) | [mockup](../mockups/03-vitrine-pdv.html) |
| 2.10 | [Carrinho de compras (tablet / PDV)](2-10-portal-carrinho.md) | [mockup](../mockups/03-vitrine-pdv.html) |

### Painel Interno Velaro

| # | Tela | Mockup |
|---|------|--------|
| 3.1 | [Dashboard Master](3-1-master-dashboard.md) | [mockup](../mockups/04-painel-master.html) |
| 3.2 | [Clientes (base consolidada)](3-2-master-clientes.md) | [mockup](../mockups/50-master-clientes.html) |
| 3.3 | [Configurações](3-3-master-config.md) | [mockup](../mockups/51-master-config.html) |
| 3.4 | [Estoque](3-4-master-estoque.md) | [mockup](../mockups/52-master-estoque.html) |
| 3.5 | [Financeiro B2B](3-5-master-financeiro.md) | [mockup](../mockups/53-master-financeiro.html) |
| 3.6 | [Pedidos — ciclo completo](3-6-master-pedidos.md) | [mockup](../mockups/54-master-pedidos.html) |
| 3.7 | [Produtos — catálogo mestre](3-7-master-produtos.md) | [mockup](../mockups/55-master-produtos.html) |
| 3.8 | [Promoções](3-8-master-promocoes.md) | [mockup](../mockups/56-master-promocoes.html) |
| 3.9 | [Relatórios](3-9-master-relatorios.md) | [mockup](../mockups/57-master-relatorios.html) |
| 3.10 | [Revendedores + cadastro manual](3-10-master-revendedores.md) | [mockup](../mockups/58-master-revendedores.html) |
| 3.11 | [Solicitações pré-cadastro](3-11-master-precadastro.md) | [mockup](../mockups/59-master-precadastro.html) |
| 3.12 | [Suporte — atendimento](3-12-master-suporte.md) | [mockup](../mockups/60-master-suporte.html) |

---

## Resumo do impacto no banco

**49 tabelas novas** no módulo Velaro ·
**5 tabelas do core alteradas** ·
**71 tabelas** e **101 chaves estrangeiras** no banco, em **54 migrations** do módulo.

A regra "nenhuma tabela do núcleo é alterada" foi **abandonada por decisão registrada**: o core
passou a ser mutável. Em consequência, as três tabelas de extensão 1:1 que a documentação previa
(`product_attributes`, `order_velaro_details`, `customer_velaro_details`) **não existem** — seus
campos foram absorvidos por `products`, `orders` e `customers`.

Decisões, política de exclusão de FKs e o fechamento das 18 lacunas: [`docs/banco-de-dados.md`](../banco-de-dados.md).
