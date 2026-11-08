<?php

namespace Tests\Mysql;

use App\Models\KrsPengajuanRiwayat;
use App\Models\Nilai;
use App\Models\Semester;
use App\Services\GradeEntryService;
use App\Services\KrsSubmissionService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\MysqlTestCase;
use Tests\Support\MysqlFixtures;

/** Committed synthetic fixtures are visible to independent child connections. */
class ConcurrencyTest extends MysqlTestCase
{
    private function race(array $workers): array
    {
        $file = tempnam(sys_get_temp_dir(), 'uninalar-race-');
        file_put_contents($file, json_encode(compact('workers')));
        $before = DB::selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")?->Value;
        $processes = [];
        foreach ([0, 1] as $slot) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/mysql-race-worker.php'), $file, (string) $slot], base_path());
            $process->setTimeout(25)->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 20;
        while (! is_file($file.'.ready0') || ! is_file($file.'.ready1')) {
            if (microtime(true) > $deadline) {
                $this->fail('Both MySQL workers must reach the barrier');
            }
            usleep(10000);
        }
        file_put_contents($file.'.go', 'go');
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
        }
        $this->assertNotSame($results[0]['connection'], $results[1]['connection']);
        $this->assertGreaterThan(0, $results[0]['locks']);
        $this->assertGreaterThan(0, $results[1]['locks']);
        $after = DB::selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")?->Value;
        echo "\nMYSQL_RACE ".json_encode(['case' => $this->name(), 'results' => $results, 'deadlocks' => $before === null || $after === null ? null : (int) $after - (int) $before])."\n";
        foreach ([$file, $file.'.ready0', $file.'.ready1', $file.'.go'] as $temporary) {
            unlink($temporary);
        }

        return array_column($results, 'status');
    }

    public function test_concurrent_semester_activation_preserves_existing_single_active_rule(): void
    {
        $d = MysqlFixtures::make();
        $d['semester']->update(['aktif' => false]);
        $codes = ['QA-RACE-SEM-A', 'QA-RACE-SEM-B'];
        try {
            $workers = [];
            foreach ($codes as $index => $code) {
                $workers[] = ['action' => 'semester-store', 'actor' => $d['admin']->id, 'values' => ['kode' => $code, 'tahun_ajaran' => (2037 + $index).'/'.(2038 + $index), 'periode' => 1, 'aktif' => 1]];
            }
            $statuses = $this->race($workers);
            sort($statuses);
            $this->assertSame(['ok', 'validation'], $statuses);
            $this->assertSame(1, Semester::whereIn('kode', $codes)->where('aktif', true)->count());
        } finally {
            Semester::whereIn('kode', $codes)->update(['aktif' => false]);
        }
    }

    public function test_capacity_reduction_racing_submission_never_overbooks(): void
    {
        $d = MysqlFixtures::make(2, 2);
        MysqlFixtures::detail($d, 0);
        app(KrsSubmissionService::class)->addClass($d['users'][1], $d['class']->id);
        $values = $d['class']->only(['mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas', 'ruang']);
        $statuses = $this->race([
            ['action' => 'class-update', 'actor' => $d['admin']->id, 'id' => $d['class']->id, 'values' => $values + ['kapasitas' => 1]],
            ['action' => 'submit', 'actor' => $d['users'][1]->id],
        ]);
        sort($statuses);
        $this->assertSame(['ok', 'validation'], $statuses);
        $reserved = DB::table('krs_detail')->join('krs', 'krs.id', '=', 'krs_detail.krs_id')
            ->where('kelas_kuliah_id', $d['class']->id)->whereIn('krs.status', ['menunggu', 'disetujui'])->count();
        $this->assertLessThanOrEqual($d['class']->fresh()->kapasitas, $reserved);
    }

    public function test_two_submissions_produce_one_version(): void
    {
        $d = MysqlFixtures::make();
        $detail = app(KrsSubmissionService::class)->addClass($d['users'][0], $d['class']->id);
        $worker = ['action' => 'submit', 'actor' => $d['users'][0]->id];
        $statuses = $this->race([$worker, $worker]);
        sort($statuses);
        $this->assertSame(['ok', 'validation'], $statuses);
        $this->assertSame(1, KrsPengajuanRiwayat::where('krs_id', $detail->krs_id)->count());
        $this->assertSame(1, KrsPengajuanRiwayat::where('krs_id', $detail->krs_id)->value('versi'));
    }

    public function test_two_students_compete_for_last_reserved_slot(): void
    {
        $d = MysqlFixtures::make(2, 1);
        foreach ($d['users'] as $user) {
            app(KrsSubmissionService::class)->addClass($user, $d['class']->id);
        }
        $statuses = $this->race($d['users']->map(fn ($user) => ['action' => 'submit', 'actor' => $user->id])->all());
        sort($statuses);
        $this->assertSame(['ok', 'validation'], $statuses);
        $this->assertSame(1, DB::table('krs_detail')->join('krs', 'krs.id', '=', 'krs_detail.krs_id')->where('kelas_kuliah_id', $d['class']->id)->where('krs.status', 'menunggu')->count());
    }

    public function test_competing_decisions_keep_parent_and_history_consistent(): void
    {
        $d = MysqlFixtures::make();
        app(KrsSubmissionService::class)->addClass($d['users'][0], $d['class']->id);
        $history = app(KrsSubmissionService::class)->submit($d['users'][0]);
        $workers = array_map(fn ($decision) => ['action' => 'decide', 'actor' => $d['admin']->id, 'id' => $history->id, 'decision' => $decision], ['disetujui', 'ditolak']);
        $statuses = $this->race($workers);
        sort($statuses);
        $this->assertSame(['ok', 'validation'], $statuses);
        $this->assertSame($history->fresh()->status, $history->fresh()->krs->status);
    }

    public function test_competing_grade_creation_is_unique(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $worker = ['action' => 'grade', 'actor' => $d['lecturerUser']->id, 'id' => $detail->id, 'values' => ['nilai_angka' => 80, 'nilai_huruf' => 'B']];
        $statuses = $this->race([$worker, $worker]);
        $this->assertNotContains('Illuminate\\Database\\QueryException:23000', $statuses);
        $this->assertSame(1, Nilai::where('krs_detail_id', $detail->id)->count());
        // Concurrent editors must not silently overwrite a grade they never saw.
        sort($statuses);
        $this->assertSame(['ok', 'validation'], $statuses);
    }

    public function test_finalization_racing_edit_keeps_final_grade_locked(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $grade = app(GradeEntryService::class)->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 80, 'nilai_huruf' => 'B']);
        $statuses = $this->race([
            ['action' => 'finalize', 'actor' => $d['lecturerUser']->id, 'id' => $grade->id],
            ['action' => 'grade', 'actor' => $d['lecturerUser']->id, 'id' => $detail->id, 'values' => ['nilai_angka' => 90, 'nilai_huruf' => 'A', 'revision' => GradeEntryService::revision($grade)]],
        ]);
        $this->assertSame('ok', $statuses[0]);
        $this->assertContains($statuses[1], ['ok', 'validation']);
        $this->assertNotNull($grade->fresh()->final_at);
        $this->assertSame($d['lecturerUser']->id, $grade->fresh()->difinalkan_oleh_user_id);
    }

    public function test_competing_edits_reject_stale_revision(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $grade = app(GradeEntryService::class)->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 80, 'nilai_huruf' => 'B']);
        $revision = GradeEntryService::revision($grade);
        $workers = array_map(fn ($score) => [
            'action' => 'grade', 'actor' => $d['lecturerUser']->id, 'id' => $detail->id,
            'values' => ['nilai_angka' => $score, 'nilai_huruf' => 'A', 'revision' => $revision],
        ], [90, 95]);
        $statuses = $this->race($workers);
        sort($statuses);
        $this->assertSame(['ok', 'validation'], $statuses);
        $this->assertContains($grade->fresh()->nilai_angka, ['90.00', '95.00']);
    }

    public function test_concurrent_class_deletion_preserves_related_krs(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $worker = ['action' => 'delete', 'actor' => $d['admin']->id, 'id' => $d['class']->id];
        $this->assertSame(['validation', 'validation'], $this->race([$worker, $worker]));
        $this->assertDatabaseHas('kelas_kuliah', ['id' => $d['class']->id]);
        $this->assertDatabaseHas('krs_detail', ['id' => $detail->id]);
    }
}
