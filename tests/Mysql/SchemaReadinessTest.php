<?php

namespace Tests\Mysql;

use App\Models\User;
use App\Services\GradeFinalizationService;
use App\Services\KrsSubmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\MysqlTestCase;

class SchemaReadinessTest extends MysqlTestCase
{
    public function test_missing_table_is_checked_before_history_binding_without_stale_cache(): void
    {
        $admin = User::factory()->create(['level' => 3]);
        $service = app(KrsSubmissionService::class);
        $this->assertTrue($service->workflowAvailable());
        Schema::rename('krs_pengajuan_riwayat', 'qa_hidden_history');
        $queries = [];
        DB::connection()->beforeExecuting(function ($query) use (&$queries): void {
            if (preg_match('/\bfrom\s+`?krs_pengajuan_riwayat\b/i', $query)) {
                $queries[] = $query;
            }
        });
        try {
            $this->assertFalse($service->workflowAvailable());
            $this->actingAs($admin)->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [999999, 'disetujui']))->assertStatus(503);
            $this->assertSame([], $queries);
            $this->actingAs(User::factory()->create(['level' => 1]))->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [999999, 'disetujui']))->assertForbidden();
        } finally {
            Schema::rename('qa_hidden_history', 'krs_pengajuan_riwayat');
        }
        $this->assertTrue($service->workflowAvailable());
    }

    public function test_missing_column_is_not_cached_as_ready(): void
    {
        $service = app(GradeFinalizationService::class);
        $this->assertTrue($service->workflowAvailable());
        Schema::table('dosen', fn ($table) => $table->renameColumn('aktif', 'qa_hidden_aktif'));
        try {
            $this->assertFalse($service->workflowAvailable());
        } finally {
            Schema::table('dosen', fn ($table) => $table->renameColumn('qa_hidden_aktif', 'aktif'));
        }
        $this->assertTrue($service->workflowAvailable());
    }
}
