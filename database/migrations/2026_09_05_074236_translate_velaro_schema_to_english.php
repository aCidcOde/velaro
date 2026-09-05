<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertColumnLayout();
        $this->renameColumns();
        $this->changeDefaults();
        $this->translateValues();
    }

    public function down(): void
    {
        $this->assertColumnLayout();

        if (Schema::getConnection()->table('customers')
            ->whereNotIn('person_type', ['individual', 'company', 'pf', 'pj'])->exists()) {
            throw new RuntimeException('Rollback bloqueado: existe tipo de pessoa incompatível com o formato anterior de clientes. Nenhum campo foi revertido.');
        }

        $this->translateValues(reverse: true);
        $this->changeDefaults(reverse: true);
        $this->renameColumns(reverse: true);
    }

    private function assertColumnLayout(): void
    {
        foreach ($this->columnRenames() as $tableName => $columns) {
            foreach ($columns as $previous => $current) {
                $hasPrevious = Schema::hasColumn($tableName, $previous);
                $hasCurrent = Schema::hasColumn($tableName, $current);

                if ($hasPrevious === $hasCurrent) {
                    throw new RuntimeException("Conversão bloqueada: {$tableName} deve conter exatamente uma das colunas {$previous} ou {$current}. Nenhum campo foi alterado.");
                }
            }
        }
    }

    private function renameColumns(bool $reverse = false): void
    {
        foreach ($this->columnRenames() as $tableName => $columns) {
            foreach ($reverse ? array_flip($columns) : $columns as $from => $to) {
                if (! Schema::hasColumn($tableName, $from)) {
                    continue;
                }

                if ($tableName === 'orders' && in_array($from, ['retirado_por_customer_id', 'picked_up_by_customer_id'], true)) {
                    Schema::table($tableName, function (Blueprint $table) use ($from): void {
                        $table->dropForeign([$from]);
                    });
                }

                Schema::table($tableName, function (Blueprint $table) use ($from, $to): void {
                    $table->renameColumn($from, $to);
                });

                if ($tableName === 'orders' && in_array($to, ['retirado_por_customer_id', 'picked_up_by_customer_id'], true)) {
                    Schema::table($tableName, function (Blueprint $table) use ($to): void {
                        $table->foreign($to)->references('id')->on('customers')->nullOnDelete();
                    });
                }
            }
        }

        foreach ([
            'resellers' => ['resellers_uf_index' => 'resellers_state_index'],
            'product_variants' => ['product_variants_product_id_aro_unique' => 'product_variants_product_id_ring_size_unique'],
            'stock_items' => ['stock_items_disponivel_index' => 'stock_items_available_index'],
        ] as $tableName => $indexes) {
            foreach ($reverse ? array_flip($indexes) : $indexes as $from => $to) {
                if (Schema::hasIndex($tableName, $from)) {
                    Schema::table($tableName, function (Blueprint $table) use ($from, $to): void {
                        $table->renameIndex($from, $to);
                    });
                }
            }
        }
    }

    private function changeDefaults(bool $reverse = false): void
    {
        foreach ([
            'resellers' => ['status' => [30, 'pre_cadastro', 'pending'], 'registration_type' => [20, 'automatico', 'automatic']],
            'reseller_verifications' => ['status' => [30, 'pendente', 'pending']],
            'production_requests' => ['status' => [30, 'pendente', 'pending']],
            'promotions' => ['status' => [20, 'rascunho', 'draft']],
            'order_batches' => ['status' => [30, 'aberto', 'open']],
            'orders' => ['operational_status' => [30, 'registrado', 'registered'], 'payment_status' => [30, 'pendente', 'pending']],
            'shipments' => ['status' => [30, 'aguardando_liberacao', 'awaiting_release']],
            'payments' => ['status' => [30, 'pendente', 'pending']],
            'invoices' => ['status' => [30, 'pendente', 'pending']],
            'support_tickets' => ['status' => [30, 'aberta', 'open'], 'priority' => [20, 'media', 'medium']],
            'notification_logs' => ['status' => [30, 'pendente', 'pending']],
            'report_exports' => ['status' => [30, 'pendente', 'pending']],
            'contact_leads' => ['status' => [30, 'novo', 'new']],
        ] as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $reverse): void {
                foreach ($columns as $column => [$length, $previous, $current]) {
                    $table->string($column, $length)->default($reverse ? $previous : $current)->change();
                }
            });
        }

        Schema::table('customers', function (Blueprint $table) use ($reverse): void {
            $table->string('person_type', $reverse ? 2 : 20)->default($reverse ? 'pf' : 'individual')->change();
        });
    }

    private function translateValues(bool $reverse = false): void
    {
        foreach ($this->valueTranslations() as $tableName => $columns) {
            foreach ($columns as $column => $values) {
                foreach ($reverse ? array_flip($values) : $values as $from => $to) {
                    Schema::getConnection()->table($tableName)->where($column, $from)->update([$column => $to]);
                }
            }
        }
    }

    /** @return array<string, array<string, string>> */
    private function columnRenames(): array
    {
        return [
            'resellers' => [
                'protocolo' => 'protocol', 'razao_social' => 'legal_name', 'nome_fantasia' => 'trade_name',
                'inscricao_estadual' => 'state_registration', 'responsavel_nome' => 'contact_name',
                'responsavel_cpf' => 'contact_cpf', 'telefone' => 'phone', 'cep' => 'postal_code',
                'logradouro' => 'street', 'numero' => 'street_number', 'complemento' => 'address_complement',
                'bairro' => 'district', 'cidade' => 'city', 'uf' => 'state', 'origem_contato' => 'contact_source',
                'observacoes' => 'notes', 'observacoes_internas' => 'internal_notes',
            ],
            'products' => [
                'largura_mm' => 'width_mm', 'formato' => 'shape', 'permite_gravacao' => 'allows_engraving',
                'gravacao_max_chars' => 'engraving_max_chars', 'prazo_entrega_dias' => 'delivery_days',
            ],
            'product_variants' => ['aro' => 'ring_size'],
            'stock_items' => [
                'atual' => 'on_hand', 'reservado' => 'reserved', 'disponivel' => 'available',
                'minimo' => 'minimum', 'reposicao' => 'restock_point',
            ],
            'promotion_audiences' => ['publico_alvo' => 'target_audience', 'canais' => 'channels'],
            'customers' => [
                'cep' => 'postal_code', 'endereco' => 'address', 'cidade' => 'city', 'uf' => 'state',
                'data_nascimento' => 'birth_date', 'data_casamento' => 'wedding_date',
                'data_namoro' => 'relationship_date', 'origem_contato' => 'contact_source',
            ],
            'order_batches' => [
                'retirado_em' => 'picked_up_at', 'retirado_por' => 'picked_up_by_name',
                'retirado_por_documento' => 'picked_up_by_document',
            ],
            'orders' => [
                'previsao' => 'expected_at', 'retirado_em' => 'picked_up_at', 'retirado_por' => 'picked_up_by_name',
                'retirado_por_documento' => 'picked_up_by_document', 'retirado_por_customer_id' => 'picked_up_by_customer_id',
            ],
            'reseller_stores' => ['endereco' => 'address'],
        ];
    }

    /** @return array<string, array<string, array<string, string>>> */
    private function valueTranslations(): array
    {
        $resellerStatuses = ['pre_cadastro' => 'pending', 'aprovado' => 'approved', 'reprovado' => 'rejected', 'inativo' => 'inactive'];
        $operationalStatuses = [
            'registrado' => 'registered', 'pagamento_confirmado' => 'payment_confirmed',
            'producao_andamento' => 'in_production', 'producao_finalizada' => 'production_completed',
            'em_transporte' => 'in_transit', 'pronto_retirada' => 'ready_for_pickup', 'retirado' => 'picked_up',
        ];
        $paymentStatuses = [
            'pendente' => 'pending', 'aguardando_compensacao' => 'awaiting_clearance',
            'pago' => 'paid', 'vencido' => 'overdue',
        ];
        $promotionTypes = [
            'desconto_progressivo' => 'tiered_discount', 'preco_especial' => 'special_price',
            'frete_gratis' => 'free_shipping', 'desconto_fixo' => 'fixed_discount', 'lancamento' => 'launch',
        ];
        $supportStatuses = [
            'aberta' => 'open', 'em_atendimento' => 'in_progress', 'aguardando_retorno' => 'awaiting_customer',
            'em_analise' => 'under_review', 'respondido' => 'answered', 'resolvido' => 'resolved',
        ];

        return [
            'resellers' => ['status' => $resellerStatuses, 'registration_type' => ['automatico' => 'automatic']],
            'reseller_status_events' => ['from_status' => $resellerStatuses, 'to_status' => $resellerStatuses],
            'reseller_verifications' => ['status' => ['pendente' => 'pending']],
            'reseller_consents' => ['type' => ['termos' => 'terms', 'lgpd' => 'privacy_policy']],
            'reseller_documents' => ['type' => [
                'contrato_social' => 'articles_of_incorporation', 'documento_socio' => 'partner_id_document', 'cartao_cnpj' => 'cnpj_card',
            ]],
            'customers' => ['person_type' => ['pf' => 'individual', 'pj' => 'company']],
            'customer_consents' => ['type' => ['transacional' => 'transactional']],
            'orders' => ['operational_status' => $operationalStatuses, 'payment_status' => $paymentStatuses],
            'order_status_events' => ['from_status' => $operationalStatuses + $paymentStatuses, 'to_status' => $operationalStatuses + $paymentStatuses],
            'order_batches' => ['status' => ['aberto' => 'open'] + $paymentStatuses],
            'payments' => ['status' => $paymentStatuses, 'method' => ['transferencia' => 'bank_transfer']],
            'invoices' => ['status' => ['pendente' => 'pending']],
            'shipments' => ['status' => ['aguardando_liberacao' => 'awaiting_release']],
            'production_requests' => ['status' => ['pendente' => 'pending']],
            'stock_movements' => ['type' => [
                'entrada' => 'inbound', 'saida' => 'outbound', 'ajuste' => 'adjustment', 'reserva' => 'reservation', 'producao' => 'production',
            ]],
            'promotions' => ['type' => $promotionTypes, 'status' => [
                'rascunho' => 'draft', 'agendada' => 'scheduled', 'ativa' => 'active', 'pausada' => 'paused', 'encerrada' => 'ended',
            ]],
            'order_promotions' => ['type' => $promotionTypes],
            'support_tickets' => ['status' => $supportStatuses, 'priority' => ['alta' => 'high', 'media' => 'medium', 'baixa' => 'low']],
            'support_status_events' => ['from_status' => $supportStatuses, 'to_status' => $supportStatuses],
            'support_messages' => ['author_role' => ['revendedor' => 'reseller']],
            'help_articles' => ['type' => ['guia' => 'guide']],
            'notification_logs' => [
                'status' => ['pendente' => 'pending'], 'type' => ['pedido_pronto' => 'order_ready'],
                'recipient_type' => ['revendedor' => 'reseller', 'cliente' => 'customer'],
            ],
            'report_exports' => ['status' => ['pendente' => 'pending']],
            'contact_leads' => ['status' => ['novo' => 'new']],
        ];
    }
};
