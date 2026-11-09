<?php

namespace Tests\Mysql;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\MysqlTestCase;
use Tests\Support\MysqlFixtures;

class HostingSchemaTest extends MysqlTestCase
{
    use DatabaseTransactions;

    public function test_current_migrations_types_indexes_and_foreign_keys_on_actual_mysql(): void
    {
        $this->assertSame(count(glob(database_path('migrations/*.php'))), DB::table('migrations')->count());
        $tables = Schema::getTableListing(schemaQualified: false);
        foreach (['users', 'program_studi', 'dosen', 'mahasiswa', 'semester', 'mata_kuliah', 'kelas_kuliah',
            'krs', 'krs_detail', 'krs_pengajuan_riwayat', 'nilai', 'kehadiran', 'analisis_risiko',
            'faktor_risiko', 'rekomendasi_intervensi', 'tindak_lanjut', 'groq_demo_budgets', 'groq_demo_user_budgets'] as $name) {
            $this->assertContains($name, $tables);
        }
        $columns = collect(DB::select('SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()'));
        foreach (['skor', 'kategori'] as $column) {
            $this->assertSame('YES', $columns->first(fn ($c) => $c->TABLE_NAME === 'analisis_risiko' && $c->COLUMN_NAME === $column)->IS_NULLABLE);
        }
        $this->assertSame('json', $columns->first(fn ($c) => $c->TABLE_NAME === 'analisis_risiko' && $c->COLUMN_NAME === 'ringkasan')->DATA_TYPE);
        $this->assertSame(50, $columns->first(fn ($c) => $c->TABLE_NAME === 'analisis_risiko' && $c->COLUMN_NAME === 'versi_model')->CHARACTER_MAXIMUM_LENGTH);
        $unique = DB::select('SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND NON_UNIQUE=0', ['users', 'username']);
        $this->assertNotEmpty($unique);
        $this->assertGreaterThan(0, count(DB::select('SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND REFERENCED_TABLE_NAME=?', ['rekomendasi_intervensi', 'analisis_risiko'])));
        $this->assertTrue(Schema::hasIndex('analisis_risiko', ['mahasiswa_id', 'dianalisis_pada']));
        $this->assertSame(1, DB::table('groq_demo_budgets')->where('id', 1)->count());
        $settings = DB::selectOne('SELECT VERSION() AS version, @@transaction_isolation AS isolation_level, @@collation_database AS db_collation, @@session.time_zone AS session_timezone');
        echo "\nMYSQL_SCHEMA ".json_encode(['tables' => count($tables), 'settings' => $settings])."\n";
    }

    public function test_role_check_and_budget_primary_key_reject_invalid_values(): void
    {
        try {
            DB::table('groq_demo_budgets')->insert(['id' => 1]);
            $this->fail('Duplicate budget mutex accepted');
        } catch (QueryException $e) {
            $this->assertSame('23000', $e->getCode());
        }
        $this->assertSame(1, DB::table('groq_demo_budgets')->where('id', 1)->count());
        try {
            User::factory()->create(['level' => 9]);
            $this->fail('Invalid role accepted despite CHECK');
        } catch (QueryException $e) {
            $this->assertSame(3819, $e->errorInfo[1]);
        }
        try {
            DB::table('rekomendasi_intervensi')->insert(['analisis_risiko_id' => PHP_INT_MAX, 'judul' => 'QA invalid FK']);
            $this->fail('Orphan recommendation accepted');
        } catch (QueryException $e) {
            $this->assertSame(1452, $e->errorInfo[1]);
        }
        $first = User::factory()->create(['username' => 'qa-case-'.bin2hex(random_bytes(5))]);
        $second = User::factory()->create();
        try {
            DB::table('users')->where('id', $second->id)->update(['username' => strtoupper($first->username)]);
            $this->fail('Case-insensitive duplicate username accepted');
        } catch (QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
        $this->assertSame(1, count(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='users_level_range_check'")));
    }

    public function test_descriptive_rollback_refuses_existing_null_classifications_without_data_loss(): void
    {
        $d = MysqlFixtures::make();
        $id = DB::table('analisis_risiko')->insertGetId(['mahasiswa_id' => $d['profiles'][0]->id,
            'semester_id' => $d['semester']->id, 'dianalisis_pada' => now(), 'skor' => null, 'kategori' => null]);
        try {
            (require database_path('migrations/2026_11_08_000000_allow_descriptive_academic_analysis.php'))->down();
            $this->fail('Unsafe descriptive rollback accepted');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Descriptive analyses must be reviewed', $e->getMessage());
        }
        $this->assertNull(DB::table('analisis_risiko')->where('id', $id)->value('skor'));
        $this->assertSame(1, DB::table('analisis_risiko')->where('id', $id)->count());
    }
}
