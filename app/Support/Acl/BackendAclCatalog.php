<?php

namespace App\Support\Acl;

final class BackendAclCatalog
{
    public const string BACKEND_ACCESS_PERMISSION = 'backend.admin.access';

    public const string ADMIN_RESPONSIBILITY_KEY = 'backend.admin';

    public const string ACL_MANAGER_RESPONSIBILITY_KEY = 'backend.acl-manager';

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function moduleDefinitions(): array
    {
        return [
            'users' => [
                'label' => 'Usuários',
                'description' => 'Cadastro, atualização e permissões de usuários.',
            ],
            'customers' => [
                'label' => 'Clientes',
                'description' => 'Gestão de clientes da base.',
            ],
            'products' => [
                'label' => 'Produtos',
                'description' => 'Gestão de catálogo de produtos por usuário.',
            ],
            'orders' => [
                'label' => 'Pedidos',
                'description' => 'Consulta e operação manual dos pedidos e itens.',
            ],
            'auditing' => [
                'label' => 'Auditoria',
                'description' => 'Logs do sistema, changelog e monitoramento de rotinas.',
            ],
            'agent' => [
                'label' => 'Velaro IA',
                'description' => 'Conversa, uploads e filas do módulo Velaro IA.',
            ],
            'dashboard' => [
                'label' => 'Dashboard',
                'description' => 'Acesso ao painel administrativo inicial.',
            ],

            'velaro-dashboard' => [
                'label' => 'Velaro · Dashboard',
                'description' => 'Painel inicial do Master, com os indicadores da operacao.',
            ],
            'velaro-prospects' => [
                'label' => 'Velaro · Pre-cadastros',
                'description' => 'Fila das solicitacoes vindas do site: aprovar, reprovar e pedir informacao.',
            ],
            'velaro-resellers' => [
                'label' => 'Velaro · Revendedores',
                'description' => 'Cadastro do lojista aprovado, habilitacao e acesso assistido.',
            ],
            'velaro-customers' => [
                'label' => 'Velaro · Clientes',
                'description' => 'Base consolidada dos clientes finais, sempre com o revendedor responsavel.',
            ],
            'velaro-products' => [
                'label' => 'Velaro · Produtos',
                'description' => 'Catalogo mestre: ficha tecnica, variacoes, imagens e status.',
            ],
            'velaro-stock' => [
                'label' => 'Velaro · Estoque',
                'description' => 'Saldo por SKU e aro, movimentacao e solicitacao de producao.',
            ],
            'velaro-orders' => [
                'label' => 'Velaro · Pedidos',
                'description' => 'Ciclo do pedido B2B, transicao de status e confirmacao de retirada.',
            ],
            'velaro-finance' => [
                'label' => 'Velaro · Financeiro',
                'description' => 'Lote semanal, baixa de recebimento, nota fiscal e liberacao de remessa.',
            ],
            'velaro-promotions' => [
                'label' => 'Velaro · Promocoes',
                'description' => 'Campanha B2B da Velaro para o lojista, com faixas e publico-alvo.',
            ],
            'velaro-reports' => [
                'label' => 'Velaro · Relatorios',
                'description' => 'Relatorios operacionais, exportacao e agendamento.',
            ],
            'velaro-support' => [
                'label' => 'Velaro · Suporte',
                'description' => 'Atendimento Velaro ao revendedor, com thread e observacao interna.',
            ],
            'velaro-settings' => [
                'label' => 'Velaro · Configuracoes',
                'description' => 'Parametros do sistema, empresa, integracoes e financeiro/fiscal.',
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, module: string, label: string, description: string}>
     */
    public static function permissions(): array
    {
        return [
            ['key' => 'backend.admin.access', 'module' => 'dashboard', 'label' => 'Acessar backend', 'description' => 'Permite entrar no painel administrativo.'],
            ['key' => 'backend.dashboard.view', 'module' => 'dashboard', 'label' => 'Visualizar dashboard', 'description' => 'Permite visualizar o dashboard do backend.'],

            ['key' => 'backend.audit-logs.view', 'module' => 'auditing', 'label' => 'Visualizar logs de auditoria', 'description' => 'Permite acessar os logs de auditoria.'],
            ['key' => 'backend.changelog.view', 'module' => 'auditing', 'label' => 'Visualizar changelog', 'description' => 'Permite acessar o changelog do backend.'],
            ['key' => 'backend.agent-jobs.view', 'module' => 'agent', 'label' => 'Visualizar filas do agente', 'description' => 'Permite acompanhar o processamento do módulo Velaro IA.'],

            ['key' => 'backend.users.view', 'module' => 'users', 'label' => 'Visualizar usuários', 'description' => 'Permite listar e visualizar usuários no backend.'],
            ['key' => 'backend.users.update', 'module' => 'users', 'label' => 'Atualizar usuários', 'description' => 'Permite editar dados do usuário.'],
            ['key' => 'backend.users.permissions.manage', 'module' => 'users', 'label' => 'Gerenciar permissões de usuário', 'description' => 'Permite editar responsabilidades e permissões por usuário.'],

            ['key' => 'backend.customers.view', 'module' => 'customers', 'label' => 'Visualizar clientes', 'description' => 'Permite listar clientes no backend.'],
            ['key' => 'backend.customers.create', 'module' => 'customers', 'label' => 'Criar clientes', 'description' => 'Permite criar clientes no backend.'],
            ['key' => 'backend.customers.update', 'module' => 'customers', 'label' => 'Atualizar clientes', 'description' => 'Permite editar clientes no backend.'],

            ['key' => 'backend.products.view', 'module' => 'products', 'label' => 'Visualizar produtos', 'description' => 'Permite listar produtos no backend.'],
            ['key' => 'backend.products.create', 'module' => 'products', 'label' => 'Criar produtos', 'description' => 'Permite criar produtos no backend.'],
            ['key' => 'backend.products.update', 'module' => 'products', 'label' => 'Atualizar produtos', 'description' => 'Permite editar produtos no backend.'],

            ['key' => 'backend.orders.view', 'module' => 'orders', 'label' => 'Visualizar pedidos', 'description' => 'Permite listar e detalhar pedidos no backend.'],
            ['key' => 'backend.orders.create', 'module' => 'orders', 'label' => 'Criar pedidos', 'description' => 'Permite criar pedidos no backend.'],
            ['key' => 'backend.orders.update', 'module' => 'orders', 'label' => 'Atualizar pedidos', 'description' => 'Permite atualizar pedidos no backend.'],
            ['key' => 'backend.orders.item-status.update', 'module' => 'orders', 'label' => 'Atualizar status de item', 'description' => 'Permite alterar status manual de item de pedido.'],

            // Painel Interno Velaro — uma familia por tela do escopo (3.1 a 3.12).
            ['key' => 'velaro.dashboard.view', 'module' => 'velaro-dashboard', 'label' => 'Ver dashboard Velaro', 'description' => 'Abre o painel inicial do Master.'],
            ['key' => 'velaro.prospects.view', 'module' => 'velaro-prospects', 'label' => 'Ver pre-cadastros', 'description' => 'Consulta a fila de solicitacoes de lojista.'],
            ['key' => 'velaro.prospects.approve', 'module' => 'velaro-prospects', 'label' => 'Aprovar pre-cadastro', 'description' => 'Aprova a solicitacao e libera o acesso de Parceiro Premium. Acao sensivel: exige log.'],
            ['key' => 'velaro.prospects.reject', 'module' => 'velaro-prospects', 'label' => 'Reprovar pre-cadastro', 'description' => 'Reprova a solicitacao com justificativa. Acao sensivel: exige log.'],
            ['key' => 'velaro.prospects.request_info', 'module' => 'velaro-prospects', 'label' => 'Solicitar informacoes', 'description' => 'Devolve a solicitacao ao lojista pedindo documento adicional.'],
            ['key' => 'velaro.resellers.view', 'module' => 'velaro-resellers', 'label' => 'Ver revendedores', 'description' => 'Consulta a base de lojistas habilitados.'],
            ['key' => 'velaro.resellers.create', 'module' => 'velaro-resellers', 'label' => 'Cadastrar revendedor', 'description' => 'Cria lojista manualmente, sem passar pelo site.'],
            ['key' => 'velaro.resellers.approve', 'module' => 'velaro-resellers', 'label' => 'Aprovar revendedor', 'description' => 'Habilita o lojista. Acao sensivel: exige log.'],
            ['key' => 'velaro.resellers.impersonate', 'module' => 'velaro-resellers', 'label' => 'Ver como revendedor', 'description' => 'Abre o portal no contexto do lojista. Acao sensivel: exige log de inicio e fim da sessao.'],
            ['key' => 'velaro.customers.view', 'module' => 'velaro-customers', 'label' => 'Ver clientes finais', 'description' => 'Consulta a base consolidada de clientes.'],
            ['key' => 'velaro.customers.update', 'module' => 'velaro-customers', 'label' => 'Editar cliente final', 'description' => 'Atualiza o cadastro do cliente na carteira do revendedor.'],
            ['key' => 'velaro.products.view', 'module' => 'velaro-products', 'label' => 'Ver produtos', 'description' => 'Consulta o catalogo mestre.'],
            ['key' => 'velaro.products.manage', 'module' => 'velaro-products', 'label' => 'Gerenciar produtos', 'description' => 'Cria e edita produto, ficha tecnica, variacoes e imagens.'],
            ['key' => 'velaro.products.duplicate', 'module' => 'velaro-products', 'label' => 'Duplicar produto', 'description' => 'Cria um produto a partir de outro.'],
            ['key' => 'velaro.products.deactivate', 'module' => 'velaro-products', 'label' => 'Inativar produto', 'description' => 'Tira o produto da vitrine e do catalogo do revendedor.'],
            ['key' => 'velaro.stock.view', 'module' => 'velaro-stock', 'label' => 'Ver estoque', 'description' => 'Consulta saldo por SKU, aro e local.'],
            ['key' => 'velaro.stock.adjust', 'module' => 'velaro-stock', 'label' => 'Ajustar estoque', 'description' => 'Lanca ajuste manual de saldo. Acao sensivel: exige log.'],
            ['key' => 'velaro.stock.request_production', 'module' => 'velaro-stock', 'label' => 'Solicitar producao', 'description' => 'Abre pedido de reposicao para a fabrica.'],
            ['key' => 'velaro.orders.view', 'module' => 'velaro-orders', 'label' => 'Ver pedidos', 'description' => 'Consulta o ciclo completo dos pedidos B2B.'],
            ['key' => 'velaro.orders.update_status', 'module' => 'velaro-orders', 'label' => 'Atualizar status do pedido', 'description' => 'Move o pedido na esteira operacional.'],
            ['key' => 'velaro.orders.confirm_pickup', 'module' => 'velaro-orders', 'label' => 'Confirmar retirada', 'description' => 'Registra a retirada de um pedido na loja.'],
            ['key' => 'velaro.orders.confirm_batch_pickup', 'module' => 'velaro-orders', 'label' => 'Confirmar retirada do lote', 'description' => 'Registra a retirada de um lote inteiro de uma vez.'],
            ['key' => 'velaro.finance.view', 'module' => 'velaro-finance', 'label' => 'Ver financeiro', 'description' => 'Consulta lotes, recebimentos e notas.'],
            ['key' => 'velaro.finance.reconcile', 'module' => 'velaro-finance', 'label' => 'Dar baixa financeira', 'description' => 'Concilia o recebimento do lote. Acao sensivel: exige log.'],
            ['key' => 'velaro.finance.issue_invoice', 'module' => 'velaro-finance', 'label' => 'Emitir nota fiscal', 'description' => 'Emite a NF-e da venda B2B ao lojista.'],
            ['key' => 'velaro.finance.release_shipment', 'module' => 'velaro-finance', 'label' => 'Liberar remessa', 'description' => 'Libera o envio do lote quitado. Acao sensivel: exige log.'],
            ['key' => 'velaro.promotions.view', 'module' => 'velaro-promotions', 'label' => 'Ver promocoes', 'description' => 'Consulta as campanhas B2B.'],
            ['key' => 'velaro.promotions.manage', 'module' => 'velaro-promotions', 'label' => 'Gerenciar promocoes', 'description' => 'Cria e edita campanha, faixas, produtos e publico-alvo.'],
            ['key' => 'velaro.reports.view', 'module' => 'velaro-reports', 'label' => 'Ver relatorios', 'description' => 'Consulta os relatorios operacionais.'],
            ['key' => 'velaro.reports.export', 'module' => 'velaro-reports', 'label' => 'Exportar relatorio', 'description' => 'Gera exportacao; roda em job quando pesada.'],
            ['key' => 'velaro.reports.schedule', 'module' => 'velaro-reports', 'label' => 'Agendar relatorio', 'description' => 'Programa envio recorrente por e-mail.'],
            ['key' => 'velaro.support.view', 'module' => 'velaro-support', 'label' => 'Ver chamados', 'description' => 'Consulta os chamados dos revendedores.'],
            ['key' => 'velaro.support.reply', 'module' => 'velaro-support', 'label' => 'Responder chamado', 'description' => 'Publica resposta na thread do chamado.'],
            ['key' => 'velaro.support.assign', 'module' => 'velaro-support', 'label' => 'Transferir atendimento', 'description' => 'Troca o responsavel pelo chamado.'],
            ['key' => 'velaro.support.resolve', 'module' => 'velaro-support', 'label' => 'Resolver chamado', 'description' => 'Encerra o atendimento.'],
            ['key' => 'velaro.settings.manage', 'module' => 'velaro-settings', 'label' => 'Gerenciar configuracoes', 'description' => 'Edita parametros do sistema. Acao sensivel: exige log.'],
        ];
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, permissions: array<int, string>}>
     */
    public static function responsibilities(): array
    {
        $allPermissionKeys = array_map(static fn (array $permission): string => $permission['key'], self::permissions());

        // Master Velaro: todas as permissoes do modulo, mais a chave que abre o backend.
        $velaroPermissionKeys = array_values(array_merge(
            [self::BACKEND_ACCESS_PERMISSION],
            array_filter($allPermissionKeys, static fn (string $key): bool => str_starts_with($key, 'velaro.')),
        ));

        return [
            [
                'key' => self::ADMIN_RESPONSIBILITY_KEY,
                'name' => 'Admin Backend',
                'description' => 'Acesso completo a todos os módulos do backend.',
                'permissions' => $allPermissionKeys,
            ],
            [
                'key' => self::ACL_MANAGER_RESPONSIBILITY_KEY,
                'name' => 'Gestor ACL',
                'description' => 'Permite gerenciar permissões de usuários no backend.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.dashboard.view',
                    'backend.users.view',
                    'backend.users.permissions.manage',
                ],
            ],
            [
                'key' => 'backend.dashboard',
                'name' => 'Dashboard Backend',
                'description' => 'Acesso apenas ao dashboard administrativo.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.dashboard.view',
                ],
            ],
            [
                'key' => 'backend.auditoria',
                'name' => 'Auditoria Backend',
                'description' => 'Acesso ao módulo de auditoria e monitoramento.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.audit-logs.view',
                    'backend.changelog.view',
                    'backend.agent-jobs.view',
                ],
            ],
            [
                'key' => 'backend.usuarios',
                'name' => 'Usuários Backend',
                'description' => 'Acesso ao módulo de usuários.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.users.view',
                    'backend.users.update',
                ],
            ],
            [
                'key' => 'backend.clientes',
                'name' => 'Clientes Backend',
                'description' => 'Acesso ao módulo de clientes.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.customers.view',
                    'backend.customers.create',
                    'backend.customers.update',
                ],
            ],
            [
                'key' => 'backend.produtos',
                'name' => 'Produtos Backend',
                'description' => 'Acesso ao módulo de produtos.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.products.view',
                    'backend.products.create',
                    'backend.products.update',
                ],
            ],
            [
                'key' => 'backend.pedidos',
                'name' => 'Pedidos Backend',
                'description' => 'Acesso ao módulo de pedidos e operações manuais.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.orders.view',
                    'backend.orders.create',
                    'backend.orders.update',
                    'backend.orders.item-status.update',
                ],
            ],
            [
                'key' => 'velaro.master',
                'name' => 'Velaro · Master',
                'description' => 'Acesso total ao Painel Interno Velaro.',
                'permissions' => $velaroPermissionKeys,
            ],
            [
                'key' => 'velaro.comercial',
                'name' => 'Velaro · Comercial',
                'description' => 'Habilitacao de lojistas e base de clientes.',
                'permissions' => [
                    'velaro.dashboard.view',
                    'velaro.prospects.view',
                    'velaro.prospects.approve',
                    'velaro.prospects.reject',
                    'velaro.prospects.request_info',
                    'velaro.resellers.view',
                    'velaro.resellers.create',
                    'velaro.resellers.approve',
                    'velaro.customers.view',
                    'velaro.customers.update',
                ],
            ],
            [
                'key' => 'velaro.operacao',
                'name' => 'Velaro · Operacao',
                'description' => 'Catalogo, estoque e o ciclo do pedido.',
                'permissions' => [
                    'velaro.dashboard.view',
                    'velaro.products.view',
                    'velaro.products.manage',
                    'velaro.products.duplicate',
                    'velaro.products.deactivate',
                    'velaro.stock.view',
                    'velaro.stock.adjust',
                    'velaro.stock.request_production',
                    'velaro.orders.view',
                    'velaro.orders.update_status',
                    'velaro.orders.confirm_pickup',
                    'velaro.orders.confirm_batch_pickup',
                ],
            ],
            [
                'key' => 'velaro.financeiro',
                'name' => 'Velaro · Financeiro',
                'description' => 'Lote, baixa, nota fiscal e liberacao de remessa.',
                'permissions' => [
                    'velaro.dashboard.view',
                    'velaro.finance.view',
                    'velaro.finance.reconcile',
                    'velaro.finance.issue_invoice',
                    'velaro.finance.release_shipment',
                    'velaro.reports.view',
                    'velaro.reports.export',
                ],
            ],
            [
                'key' => 'velaro.suporte',
                'name' => 'Velaro · Suporte',
                'description' => 'Atendimento ao revendedor.',
                'permissions' => [
                    'velaro.dashboard.view',
                    'velaro.support.view',
                    'velaro.support.reply',
                    'velaro.support.assign',
                    'velaro.support.resolve',
                ],
            ],
        ];
    }
}
