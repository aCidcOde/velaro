<?php

/*
 * Registro das telas da plataforma B2B Velaro: liga cada rota nomeada à tela do
 * escopo (número do Anexo), ao mockup aprovado e ao documento de aceite.
 * Usado pelo cabeçalho das views (título) e pelo comando velaro:links.
 *
 * [n, título, mockup em docs/mockups, doc em docs/telas]
 */
return [
    // Site público
    'site.home'                 => ['1.1', 'Página inicial B2B',               '01-site-publico.html',   '1-1-site-home.md'],
    'site.sobre'                => ['1.2', 'Sobre nós',                        '10-site-sobre.html',     '1-2-site-sobre.md'],
    'site.catalogo'             => ['1.3', 'Catálogo público',                 '11-site-catalogo.html',  '1-3-site-catalogo.md'],
    'site.produto'              => ['1.3', 'Detalhe do produto (sem preço)',   '16-site-produto.html',   '1-3-site-catalogo.md'],
    'site.cadastro'             => ['1.4', 'Cadastro como lojista',            '12-site-cadastro.html',  '1-4-site-cadastro.md'],
    'site.solicitacao.enviada'  => ['1.5', 'Solicitação enviada',              '13-site-enviada.html',   '1-5-site-enviada.md'],
    'site.solicitacao.status'   => ['1.6', 'Status da solicitação',            '14-site-status.html',    '1-6-site-status.md'],
    'site.solicitacao.aprovado' => ['1.7', 'Cadastro aprovado e liberação',    '15-site-aprovado.html',  '1-7-site-aprovado.md'],
    'site.privacidade'          => ['1.2', 'Política de Privacidade',          '17-site-privacidade.html', '1-2-site-sobre.md'],
    'site.termos'               => ['1.2', 'Termos de Uso',                    '18-site-termos.html',    '1-2-site-sobre.md'],
    // Login (Fortify)
    'login'                     => ['0',   'Login único com roteamento por perfil', '20-login.html',     '0-login.md'],
    'password.request'          => ['0',   'Recuperar senha',                  '21-login-senha.html',    '0-login.md'],
    // Portal do Lojista
    'portal.dashboard'          => ['2.1', 'Dashboard do Lojista',             '02-portal-lojista.html', '2-1-portal-dashboard.md'],
    'portal.catalogo'           => ['2.2', 'Catálogo Revendedor',              '30-portal-catalogo.html','2-2-portal-catalogo.md'],
    'portal.clientes.index'     => ['2.3', 'Clientes / CRM',                   '31-portal-clientes.html','2-3-portal-clientes.md'],
    'portal.clientes.show'      => ['2.3', 'Cliente',                          '31-portal-clientes.html','2-3-portal-clientes.md'],
    'portal.financeiro.index'   => ['2.4', 'Financeiro',                       '32-portal-financeiro.html','2-4-portal-financeiro.md'],
    'portal.financeiro.notas'   => ['2.4', 'Notas fiscais emitidas',           '40-portal-notas.html',   '2-4-portal-financeiro.md'],
    'portal.financeiro.pagamento'=> ['2.4','Pagamento do lote à Velaro',       '41-portal-pagamento.html','2-4-portal-financeiro.md'],
    'portal.pedidos.index'      => ['2.5', 'Pedidos',                          '33-portal-pedidos.html', '2-5-portal-pedidos.md'],
    'portal.pedidos.show'       => ['2.5', 'Detalhe do pedido',                '39-portal-pedido.html',  '2-5-portal-pedidos.md'],
    'portal.loja.edit'          => ['2.6', 'Personalização da loja',           '34-portal-loja.html',    '2-6-portal-loja.md'],
    'portal.precos.edit'        => ['2.7', 'Preços e margens',                 '35-portal-precos.html',  '2-7-portal-precos.md'],
    'portal.suporte.index'      => ['2.8', 'Suporte — chamados',               '36-portal-suporte.html', '2-8-portal-suporte.md'],
    'portal.suporte.create'     => ['2.8', 'Abrir chamado',                    '42-portal-chamado.html', '2-8-portal-suporte.md'],
    'portal.suporte.show'       => ['2.8', 'Chamado de suporte',               '42-portal-chamado.html', '2-8-portal-suporte.md'],
    'portal.ajuda'              => ['2.8', 'Central de ajuda',                 '43-portal-ajuda.html',   '2-8-portal-suporte.md'],
    // Vitrine white label
    'vitrine.index'             => ['2.9', 'Vitrine para clientes',            '03-vitrine-pdv.html',    '2-9-portal-vitrine.md'],
    'vitrine.produto'           => ['2.9', 'Produto na vitrine',               '07-vitrine-produto.html','2-9-portal-vitrine.md'],
    'vitrine.carrinho'          => ['2.10','Carrinho (tablet / PDV)',          '03-vitrine-pdv.html',    '2-10-portal-carrinho.md'],
    'vitrine.confirmado'        => ['2.10','Pedido registrado no balcão',      '08-vitrine-pedido-confirmado.html','2-10-portal-carrinho.md'],
    // Painel Master
    'backend.dashboard'         => ['3.1', 'Dashboard Master',                 '04-painel-master.html',  '3-1-master-dashboard.md'],
    'backend.clientes.index'    => ['3.2', 'Clientes (base consolidada)',      '50-master-clientes.html','3-2-master-clientes.md'],
    'backend.clientes.show'     => ['3.2', 'Cliente',                          '50-master-clientes.html','3-2-master-clientes.md'],
    'backend.configuracoes.index'=> ['3.3','Configurações',                    '51-master-config.html',  '3-3-master-config.md'],
    'backend.configuracoes.secao'=> ['3.3','Configurações · seção',            '51a-master-config-usuarios.html','3-3-master-config.md'],
    'backend.estoque.index'     => ['3.4', 'Estoque',                          '52-master-estoque.html', '3-4-master-estoque.md'],
    'backend.estoque.movimentacao'=> ['3.4','Nova movimentação de estoque',    '52a-master-estoque-movimentacao.html','3-4-master-estoque.md'],
    'backend.estoque.historico' => ['3.4', 'Movimentações do item',            '52b-master-estoque-historico.html','3-4-master-estoque.md'],
    'backend.financeiro.index'  => ['3.5', 'Financeiro B2B',                   '53-master-financeiro.html','3-5-master-financeiro.md'],
    'backend.financeiro.recebimento'=> ['3.5','Novo recebimento',              '53a-master-financeiro-recebimento.html','3-5-master-financeiro.md'],
    'backend.financeiro.nota'   => ['3.5', 'Nota fiscal do lote',              '53b-master-financeiro-nota.html','3-5-master-financeiro.md'],
    'backend.pedidos.index'     => ['3.6', 'Pedidos — ciclo completo',         '54-master-pedidos.html', '3-6-master-pedidos.md'],
    'backend.pedidos.create'    => ['3.6', 'Novo pedido interno',              '61-master-pedido-novo.html','3-6-master-pedidos.md'],
    'backend.pedidos.show'      => ['3.6', 'Pedido',                           '54-master-pedidos.html', '3-6-master-pedidos.md'],
    'backend.produtos.index'    => ['3.7', 'Produtos — catálogo mestre',       '55-master-produtos.html','3-7-master-produtos.md'],
    'backend.produtos.create'   => ['3.7', 'Novo produto',                     '62-master-produto-novo.html','3-7-master-produtos.md'],
    'backend.produtos.show'     => ['3.7', 'Produto',                          '55-master-produtos.html','3-7-master-produtos.md'],
    'backend.promocoes.index'   => ['3.8', 'Promoções',                        '56-master-promocoes.html','3-8-master-promocoes.md'],
    'backend.promocoes.create'  => ['3.8', 'Nova promoção',                    '63-master-promocao-nova.html','3-8-master-promocoes.md'],
    'backend.promocoes.show'    => ['3.8', 'Promoção',                         '56-master-promocoes.html','3-8-master-promocoes.md'],
    'backend.promocoes.desempenho'=> ['3.8','Desempenho da promoção',          '64-master-promocao-desempenho.html','3-8-master-promocoes.md'],
    'backend.relatorios.index'  => ['3.9', 'Relatórios',                       '57-master-relatorios.html','3-9-master-relatorios.md'],
    'backend.relatorios.vendas' => ['3.9', 'Vendas por período',               '65-master-relatorio-vendas.html','3-9-master-relatorios.md'],
    'backend.relatorios.revendedores'=> ['3.9','Ranking de revendedores',      '66-master-relatorio-revendedores.html','3-9-master-relatorios.md'],
    'backend.relatorios.produtos'=> ['3.9','Produtos mais vendidos',           '67-master-relatorio-produtos.html','3-9-master-relatorios.md'],
    'backend.relatorios.agendados'=> ['3.9','Relatórios agendados',            '68-master-relatorios-agendados.html','3-9-master-relatorios.md'],
    'backend.relatorios.todos'  => ['3.9', 'Todos os relatórios',              '69-master-relatorios-biblioteca.html','3-9-master-relatorios.md'],
    'backend.revendedores.index'=> ['3.10','Revendedores + cadastro manual',   '58-master-revendedores.html','3-10-master-revendedores.md'],
    'backend.revendedores.show' => ['3.10','Revendedor',                       '58-master-revendedores.html','3-10-master-revendedores.md'],
    'backend.pre-cadastros.index'=> ['3.11','Solicitações pré-cadastro',       '59-master-precadastro.html','3-11-master-precadastro.md'],
    'backend.pre-cadastros.show'=> ['3.11','Solicitação',                      '59-master-precadastro.html','3-11-master-precadastro.md'],
    'backend.suporte.index'     => ['3.12','Suporte — fila de chamados',       '70-master-suporte-lista.html','3-12-master-suporte.md'],
    'backend.suporte.show'      => ['3.12','Atendimento do chamado',           '60-master-suporte.html', '3-12-master-suporte.md'],
];
