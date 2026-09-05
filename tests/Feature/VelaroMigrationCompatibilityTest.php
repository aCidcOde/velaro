<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Reseller;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class VelaroMigrationCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'migration_tests',
            'database.connections.migration_tests' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
        ]);
        $this->app->make('db')->purge('migration_tests');
        $this->migrator()->setConnection('migration_tests');
        $this->migrator()->getRepository()->createRepository();
    }

    public function test_upgrade_preserves_legacy_data_and_translates_columns_values_and_defaults(): void
    {
        $this->migrateLegacy();
        $this->seedLegacyRecords();
        $this->migrator()->run([database_path('migrations')]);

        $this->assertFalse(Schema::hasColumn('resellers', 'protocolo'));
        $this->assertFalse(Schema::hasColumn('products', 'largura_mm'));
        $this->assertTrue(Schema::hasColumn('customers', 'birth_date'));
        $this->assertTrue(Schema::hasIndex('resellers', 'resellers_state_index'));
        $this->assertTrue(Schema::hasIndex('product_variants', 'product_variants_product_id_ring_size_unique'));
        $this->assertTrue(Schema::hasIndex('stock_items', 'stock_items_available_index'));
        $this->assertDatabaseHas('resellers', ['id' => 1, 'protocol' => 'LEGACY-1', 'legal_name' => 'Loja Antiga', 'status' => 'approved']);
        $this->assertDatabaseHas('customers', ['id' => 1, 'person_type' => 'company', 'postal_code' => '12345678']);
        $this->assertDatabaseHas('products', ['id' => 1, 'width_mm' => 6, 'allows_engraving' => true, 'sku' => 'OLD-SKU']);
        $this->assertDatabaseHas('product_variants', ['id' => 1, 'ring_size' => '18']);
        $this->assertDatabaseHas('stock_items', ['id' => 1, 'on_hand' => 10, 'reserved' => 3, 'available' => 7]);
        $this->assertDatabaseHas('orders', [
            'id' => 1, 'operational_status' => 'picked_up', 'payment_status' => 'awaiting_clearance',
            'picked_up_by_customer_id' => 1, 'picked_up_by_name' => 'Pessoa autorizada', 'total_amount' => 240,
            'updated_at' => '2026-09-01 10:00:00',
        ]);
        $this->assertDatabaseHas('order_status_events', ['id' => 1, 'from_status' => 'registered', 'to_status' => 'payment_confirmed']);
        $this->assertDatabaseHas('order_status_events', ['id' => 2, 'from_status' => 'pending', 'to_status' => 'awaiting_clearance']);
        $this->assertDatabaseHas('reseller_status_events', ['id' => 1, 'from_status' => 'pending', 'to_status' => 'approved']);
        $this->assertDatabaseHas('payments', ['id' => 1, 'status' => 'paid', 'method' => 'bank_transfer', 'amount' => 240]);
        $this->assertDatabaseHas('resellers', ['id' => 2, 'status' => 'custom_status', 'deleted_at' => '2026-09-01 10:00:00']);
        $this->assertSame('Loja Antiga', Order::findOrFail(1)->reseller->legal_name);
        $this->assertSame('18', Product::findOrFail(1)->variants()->firstOrFail()->ring_size);
        Reseller::factory()->create();

        Schema::getConnection()->table('customers')->insert(['id' => 2, 'name' => 'Novo cliente']);
        Schema::getConnection()->table('orders')->insert(['id' => 2, 'public_number' => 'NEW-ORDER']);
        $this->assertDatabaseHas('customers', ['id' => 2, 'person_type' => 'individual']);
        $this->assertDatabaseHas('orders', ['id' => 2, 'operational_status' => 'registered', 'payment_status' => 'pending']);

        Schema::getConnection()->table('customers')->where('id', 1)->delete();
        $this->assertDatabaseHas('orders', ['id' => 1, 'picked_up_by_customer_id' => null]);
    }

    public function test_translation_can_run_on_an_already_translated_local_schema(): void
    {
        $this->migrateLegacy();
        $this->seedLegacyRecords();
        $this->migration('translate_velaro_schema_to_english')->up();
        $before = Schema::getConnection()->table('orders')->find(1);
        $this->migrator()->run([database_path('migrations')]);

        $this->assertEquals($before, Schema::getConnection()->table('orders')->find(1));
        $this->assertDatabaseHas('resellers', ['id' => 1, 'legal_name' => 'Loja Antiga']);
    }

    public function test_conflicting_columns_block_translation_before_any_table_changes(): void
    {
        $this->migrateLegacy();
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('postal_code')->nullable();
        });

        try {
            $this->migration('translate_velaro_schema_to_english')->up();
            $this->fail('Colunas conflitantes deveriam bloquear a conversão.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('customers', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('resellers', 'protocolo'));
        $this->assertFalse(Schema::hasColumn('resellers', 'protocol'));
    }

    public function test_translation_rolls_back_to_the_historical_schema_with_data_intact(): void
    {
        $this->migrateLegacy();
        $this->seedLegacyRecords();
        $before = Schema::getConnection()->table('orders')->find(1);
        $this->migrator()->run([database_path('migrations')]);
        $this->migrator()->rollback([database_path('migrations')]);

        $this->assertEquals($before, Schema::getConnection()->table('orders')->find(1));
        $this->assertDatabaseHas('resellers', ['id' => 1, 'protocolo' => 'LEGACY-1', 'status' => 'aprovado']);
        $this->assertDatabaseHas('customers', ['id' => 1, 'person_type' => 'pj']);
        $this->assertDatabaseHas('payments', ['id' => 1, 'method' => 'transferencia', 'status' => 'pago']);
        $this->assertDatabaseHas('order_status_events', ['id' => 2, 'to_status' => 'aguardando_compensacao']);
        $this->assertTrue(Schema::hasIndex('resellers', 'resellers_uf_index'));
        $this->assertTrue(Schema::hasIndex('product_variants', 'product_variants_product_id_aro_unique'));
        Schema::getConnection()->table('customers')->where('id', 1)->delete();
        $this->assertDatabaseHas('orders', ['id' => 1, 'retirado_por_customer_id' => null]);
    }

    public function test_translation_rollback_blocks_an_unrepresentable_person_type_before_changes(): void
    {
        $this->migrator()->run([database_path('migrations')]);
        Schema::getConnection()->table('customers')->insert(['name' => 'Cliente', 'person_type' => 'custom_type']);

        try {
            $this->migration('translate_velaro_schema_to_english')->down();
            $this->fail('Rollback deveria ser bloqueado.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tipo de pessoa incompatível', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('resellers', 'protocol'));
        $this->assertDatabaseHas('customers', ['person_type' => 'custom_type']);
    }

    public function test_global_sku_upgrade_stops_before_ddl_when_legacy_duplicates_exist(): void
    {
        $this->migrateLegacy('2026_09_04_102000');
        $connection = Schema::getConnection();
        $connection->table('users')->insert([
            ['id' => 1, 'name' => 'Um', 'email' => 'one@example.test', 'password' => 'test'],
            ['id' => 2, 'name' => 'Dois', 'email' => 'two@example.test', 'password' => 'test'],
        ]);
        $connection->table('products')->insert([
            ['user_id' => 1, 'name' => 'Um', 'sku' => 'SHARED'],
            ['user_id' => 2, 'name' => 'Dois', 'sku' => 'SHARED'],
        ]);
        $columns = Schema::getColumns('products');
        $foreignKeys = Schema::getForeignKeys('products');
        $indexes = Schema::getIndexes('products');

        try {
            $this->migration('add_velaro_fields_to_products_table')->up();
            $this->fail('Duplicatas deveriam bloquear a migração.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SKUs duplicados', $exception->getMessage());
        }

        $this->assertSame($columns, Schema::getColumns('products'));
        $this->assertSame($foreignKeys, Schema::getForeignKeys('products'));
        $this->assertSame($indexes, Schema::getIndexes('products'));
        $this->assertSame(2, $connection->table('products')->where('sku', 'SHARED')->count());
        $connection->table('products')->where('user_id', 2)->update(['sku' => 'RESOLVED']);
        $this->migration('add_velaro_fields_to_products_table')->up();
        $this->assertTrue(Schema::hasIndex('products', 'products_sku_unique', 'unique'));
        $this->assertDatabaseHas('products', ['user_id' => 1, 'sku' => 'SHARED']);
    }

    #[DataProvider('ownerTables')]
    public function test_owner_rollback_preserves_schema_and_rows_when_null_owner_exists(string $table): void
    {
        $this->migrateLegacy();
        $this->seedLegacyRecords();
        Schema::getConnection()->table($table)->where('id', 1)->update(['user_id' => null]);
        $columns = Schema::getColumns($table);
        $foreignKeys = Schema::getForeignKeys($table);
        $indexes = Schema::getIndexes($table);
        $row = Schema::getConnection()->table($table)->find(1);

        try {
            $this->migration('add_velaro_fields_to_'.$table.'_table')->down();
            $this->fail('Rollback deveria ser bloqueado.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Rollback bloqueado', $exception->getMessage());
        }

        $this->assertSame($columns, Schema::getColumns($table));
        $this->assertSame($foreignKeys, Schema::getForeignKeys($table));
        $this->assertSame($indexes, Schema::getIndexes($table));
        $this->assertEquals($row, Schema::getConnection()->table($table)->find(1));
    }

    #[DataProvider('ownerTables')]
    public function test_owner_rollback_still_succeeds_when_every_record_has_an_owner(string $table): void
    {
        $this->migrateLegacy();
        $this->seedLegacyRecords();
        $this->migration('add_velaro_fields_to_'.$table.'_table')->down();

        $this->assertFalse(Schema::hasColumn($table, $table === 'products' ? 'largura_mm' : 'reseller_id'));
        $this->assertDatabaseHas($table, ['id' => 1, 'user_id' => 1]);
        $ownerColumn = collect(Schema::getColumns($table))->firstWhere('name', 'user_id');
        $this->assertFalse($ownerColumn['nullable']);
    }

    /** @return array<string, array{string}> */
    public static function ownerTables(): array
    {
        return ['products' => ['products'], 'customers' => ['customers'], 'orders' => ['orders']];
    }

    private function migrateLegacy(string $before = '2026_09_05'): void
    {
        $files = array_filter(glob(database_path('migrations/*.php')), fn (string $file): bool => strcmp(basename($file), $before) < 0);
        $this->migrator()->run(array_values($files));
    }

    private function migrator(): Migrator
    {
        return $this->app->make('migrator');
    }

    private function migration(string $name): Migration
    {
        $files = glob(database_path('migrations/*_'.$name.'.php'));
        $this->assertCount(1, $files);

        return require $files[0];
    }

    private function seedLegacyRecords(): void
    {
        $connection = Schema::getConnection();
        $connection->table('users')->insert(['id' => 1, 'name' => 'Dono', 'email' => 'legacy@example.test', 'password' => 'test']);
        $connection->table('resellers')->insert([
            ['id' => 1, 'protocolo' => 'LEGACY-1', 'razao_social' => 'Loja Antiga', 'cnpj' => '11111111111111', 'responsavel_nome' => 'Contato', 'email' => 'shop@example.test', 'status' => 'aprovado', 'deleted_at' => null],
            ['id' => 2, 'protocolo' => 'LEGACY-2', 'razao_social' => 'Loja Arquivada', 'cnpj' => '22222222222222', 'responsavel_nome' => 'Contato', 'email' => 'archived@example.test', 'status' => 'custom_status', 'deleted_at' => '2026-09-01 10:00:00'],
        ]);
        $connection->table('customers')->insert(['id' => 1, 'user_id' => 1, 'reseller_id' => 1, 'name' => 'Cliente', 'person_type' => 'pj', 'cep' => '12345678']);
        $connection->table('products')->insert(['id' => 1, 'user_id' => 1, 'name' => 'Aliança', 'sku' => 'OLD-SKU', 'largura_mm' => 6, 'permite_gravacao' => true]);
        $connection->table('product_variants')->insert(['id' => 1, 'product_id' => 1, 'sku' => 'OLD-SKU-18', 'aro' => '18']);
        $connection->table('stock_items')->insert(['id' => 1, 'product_variant_id' => 1, 'atual' => 10, 'reservado' => 3, 'disponivel' => 7]);
        $connection->table('order_batches')->insert(['id' => 1, 'code' => 'OLD-BATCH', 'reseller_id' => 1, 'cut_date' => '2026-09-01', 'due_date' => '2026-09-10']);
        $connection->table('orders')->insert([
            'id' => 1, 'user_id' => 1, 'customer_id' => 1, 'reseller_id' => 1, 'batch_id' => 1,
            'public_number' => 'OLD-ORDER', 'operational_status' => 'retirado', 'payment_status' => 'aguardando_compensacao',
            'retirado_por_customer_id' => 1, 'retirado_por' => 'Pessoa autorizada', 'total_amount' => 240,
            'updated_at' => '2026-09-01 10:00:00',
        ]);
        $connection->table('order_status_events')->insert([
            ['id' => 1, 'order_id' => 1, 'scope' => 'operational', 'from_status' => 'registrado', 'to_status' => 'pagamento_confirmado'],
            ['id' => 2, 'order_id' => 1, 'scope' => 'payment', 'from_status' => 'pendente', 'to_status' => 'aguardando_compensacao'],
        ]);
        $connection->table('reseller_status_events')->insert(['id' => 1, 'reseller_id' => 1, 'from_status' => 'pre_cadastro', 'to_status' => 'aprovado']);
        $connection->table('payments')->insert(['id' => 1, 'batch_id' => 1, 'method' => 'transferencia', 'amount' => 240, 'status' => 'pago']);

    }
}
