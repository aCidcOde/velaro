<?php

/*
 * Navegação dos ambientes Velaro — espelho de SITE_NAV / PORTAL_NAV / MASTER_NAV
 * de docs/mockups/_ui.py, só que apontando para nomes de rota.
 * Formato: [ícone, rótulo, rota].
 */
return [
    'site' => [
        ['home', 'Início', 'site.home'],
        ['info', 'Sobre nós', 'site.sobre'],
        ['book', 'Catálogo', 'site.catalogo'],
        ['user-plus', 'Seja um revendedor', 'site.cadastro'],
    ],
    'portal' => [
        ['home', 'Dashboard', 'portal.dashboard'],
        ['book', 'Catálogo Revendedor', 'portal.catalogo'],
        ['users', 'Clientes', 'portal.clientes.index'],
        ['coin', 'Financeiro', 'portal.financeiro.index'],
        ['bag', 'Pedidos', 'portal.pedidos.index'],
        ['store', 'Personalização da loja', 'portal.loja.edit'],
        ['tag', 'Preços e margens', 'portal.precos.edit'],
        ['support', 'Suporte', 'portal.suporte.index'],
        ['cart', 'Vitrine para clientes', 'portal.vitrine'],
    ],
    'master' => [
        ['home', 'Dashboard', 'backend.dashboard'],
        ['users', 'Clientes', 'backend.clientes.index'],
        ['gear', 'Configurações', 'backend.configuracoes.index'],
        ['box', 'Estoque', 'backend.estoque.index'],
        ['coin', 'Financeiro', 'backend.financeiro.index'],
        ['bag', 'Pedidos', 'backend.pedidos.index'],
        ['tag', 'Produtos', 'backend.produtos.index'],
        ['promo', 'Promoções', 'backend.promocoes.index'],
        ['chart', 'Relatórios', 'backend.relatorios.index'],
        ['store', 'Revendedores', 'backend.revendedores.index'],
        ['user-plus', 'Solicitações pré-cadastro', 'backend.pre-cadastros.index'],
        ['support', 'Suporte', 'backend.suporte.index'],
    ],
];
