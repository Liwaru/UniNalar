<?php

namespace Tests\Support;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;

/** Synthetic Admin acceptance scenarios on SQLite and isolated MySQL. */
trait AdminModuleScenarios
{
    public function test_all_master_resources_support_crud_detail_and_unique_validation(): void
    {
        $admin = User::factory()->create(['level' => 3]);
        $tag = 'QA'.strtoupper(bin2hex(random_bytes(3)));
        $this->actingAs($admin);
        $records = [];
        $programPayload = ['kode' => $tag, 'nama' => 'Program QA Admin', 'jenjang' => 'S1', 'aktif' => 1];
        $this->post(route('dashboard.admin-akademik.program-studi.store'), $programPayload)->assertSessionHasNoErrors();
        $program = ProgramStudi::where('kode', $tag)->firstOrFail();
        $records['program-studi'] = [$program, $programPayload, 'kode', 'nama', 'Program QA Diperbarui'];
        $semesterPayload = ['kode' => $tag.'-SEM', 'tahun_ajaran' => '2034/2035', 'periode' => 1, 'tanggal_mulai' => '2034-08-01', 'tanggal_selesai' => '2034-12-31'];
        $this->post(route('dashboard.admin-akademik.semester.store'), $semesterPayload)->assertSessionHasNoErrors();
        $semester = Semester::where('kode', $semesterPayload['kode'])->firstOrFail();
        $records['semester'] = [$semester, $semesterPayload, 'kode', 'kode', $tag.'-NEW'];
        $coursePayload = ['program_studi_id' => $program->id, 'kode' => $tag.'-MK', 'nama' => 'Mata Kuliah QA Admin', 'sks' => 3];
        $this->post(route('dashboard.admin-akademik.mata-kuliah.store'), $coursePayload)->assertSessionHasNoErrors();
        $course = MataKuliah::where('kode', $coursePayload['kode'])->firstOrFail();
        $records['mata-kuliah'] = [$course, $coursePayload, 'kode', 'nama', 'Mata Kuliah QA Diperbarui'];
        foreach (['dosen' => 2, 'mahasiswa' => 1] as $resource => $role) {
            $user = User::factory()->create(['level' => $role]);
            $payload = ['nama' => 'Profil '.$resource.' QA', 'email' => strtolower($tag).$resource.'@qa.example.test', 'user_id' => $user->id, 'program_studi_id' => $program->id];
            $payload += $resource === 'dosen' ? ['nidn' => $tag, 'aktif' => 1] : ['nim' => $tag, 'angkatan' => 2034, 'status' => 'aktif'];
            $this->post(route('dashboard.admin-akademik.'.$resource.'.store'), $payload)->assertSessionHasNoErrors();
            $model = $resource === 'dosen' ? Dosen::class : Mahasiswa::class;
            $records[$resource] = [$model::where('user_id', $user->id)->firstOrFail(), $payload, 'email', 'nama', 'Profil '.$resource.' Diperbarui'];
        }
        $classPayload = ['mata_kuliah_id' => $course->id, 'semester_id' => $semester->id, 'dosen_id' => $records['dosen'][0]->id, 'kode_kelas' => 'QA-ADMIN', 'kapasitas' => 20];
        $this->post(route('dashboard.admin-akademik.kelas-kuliah.store'), $classPayload)->assertSessionHasNoErrors();
        $class = KelasKuliah::where('mata_kuliah_id', $course->id)->firstOrFail();
        $records['kelas-kuliah'] = [$class, $classPayload, 'kode_kelas', 'ruang', 'Ruang QA'];
        foreach ($records as $resource => [$record, $payload, $unique, $changed, $value]) {
            $route = 'dashboard.admin-akademik.'.$resource.'.';
            $this->post(route($route.'store'), $payload)->assertSessionHasErrors($unique);
            $this->get(route($route.'edit', $record->id))->assertOk();
            $this->put(route($route.'update', $record->id), array_replace($payload, [$changed => $value]))->assertSessionHasNoErrors();
            $this->assertSame($value, $record->fresh()->{$changed});
            $this->get(route($route.'show', $record->id))->assertOk()->assertSee($value);
        }
        foreach (['kelas-kuliah', 'mahasiswa', 'dosen', 'mata-kuliah', 'semester', 'program-studi'] as $resource) {
            $record = $records[$resource][0];
            $this->delete(route('dashboard.admin-akademik.'.$resource.'.destroy', $record->id))->assertRedirect()->assertSessionMissing('error');
            $this->assertDatabaseMissing($record->getTable(), ['id' => $record->id]);
        }
    }

    public function test_dashboard_totals_match_operational_lists_and_current_krs_status(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d, 0, 'menunggu');
        $response = $this->actingAs($d['admin'])->get(route('dashboard.admin-akademik'))->assertOk();
        $totals = $response->viewData('totals');
        foreach (['program-studi', 'semester', 'mata-kuliah', 'dosen', 'mahasiswa', 'kelas-kuliah'] as $resource) {
            $list = $this->get(route('dashboard.admin-akademik.'.$resource.'.index'))->assertOk();
            $this->assertSame($list->viewData('records')->total(), $totals[$resource]);
        }
        $response->assertViewHas('krsStatuses', fn ($s) => (int) $s['menunggu'] >= 1);
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner', ['program_studi_id' => $d['program']->id]))
            ->assertOk()->assertViewHas('totals', ['students' => 1, 'lecturers' => 1, 'programs' => 1]);
    }

    public function test_search_zero_and_inactive_filters_are_applied(): void
    {
        $admin = User::factory()->create(['level' => 3]);
        $zero = ProgramStudi::create(['kode' => 'ZERO0'.bin2hex(random_bytes(3)), 'nama' => 'Pencarian nol QA', 'jenjang' => 'S1', 'aktif' => false]);
        $other = ProgramStudi::create(['kode' => 'NONZERO'.bin2hex(random_bytes(3)), 'nama' => 'Pencarian lain QA', 'jenjang' => 'S1', 'aktif' => false]);
        // The other generated code could itself include zero; use a deterministic code.
        $other->update(['kode' => 'NONZERO-'.str_replace('0', 'X', bin2hex(random_bytes(4)))]);
        $this->actingAs($admin)->get(route('dashboard.admin-akademik.program-studi.index', ['q' => '0', 'aktif' => 0]))->assertOk()
            ->assertViewHas('records', fn ($p) => $p->pluck('id')->contains($zero->id) && ! $p->pluck('id')->contains($other->id));
        $d = MysqlFixtures::make();
        foreach (['mata-kuliah', 'dosen', 'mahasiswa'] as $resource) {
            $this->get(route('dashboard.admin-akademik.'.$resource.'.index', ['program_studi_id' => $d['program']->id]))->assertOk()
                ->assertViewHas('records', fn ($p) => $p->total() === 1);
        }
        $this->get(route('dashboard.admin-akademik.kelas-kuliah.index', ['semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id]))->assertOk()
            ->assertViewHas('records', fn ($p) => $p->total() === 1);
        foreach (['program_studi_id' => [1], 'semester_id' => 999999, 'dosen_id' => 'invalid'] as $key => $value) {
            $this->getJson(route('dashboard.admin-akademik.kelas-kuliah.index', [$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
        }
    }

    public function test_master_details_and_new_krs_detail_reject_wrong_roles_and_invalid_ids(): void
    {
        $d = MysqlFixtures::make();
        app(KrsSubmissionService::class)->addClass($d['users'][0], $d['class']->id);
        $history = app(KrsSubmissionService::class)->submit($d['users'][0]);
        $targets = ['program-studi' => $d['program'], 'semester' => $d['semester'], 'mata-kuliah' => $d['course'], 'dosen' => $d['lecturer'], 'mahasiswa' => $d['profiles'][0]];
        foreach ([1, 2, 4] as $role) {
            $this->actingAs(User::factory()->create(['level' => $role]));
            foreach ($targets as $resource => $record) {
                $this->get(route('dashboard.admin-akademik.'.$resource.'.show', $record->id))->assertForbidden();
            }
            $this->get(route('dashboard.admin-akademik.krs-pengajuan.show', $history->id))->assertForbidden();
        }
        $this->actingAs($d['admin']);
        $this->get('/dashboard/admin-akademik/mahasiswa/not-numeric')->assertNotFound();
        $this->get(route('dashboard.admin-akademik.krs-pengajuan.show', 999999))->assertNotFound();
        $demo = ProgramStudi::create(['kode' => 'QA-DEMO', 'nama' => 'Demo QA', 'jenjang' => 'S1']);
        $this->get(route('dashboard.admin-akademik.program-studi.show', $demo->id))->assertNotFound();
    }

    public function test_krs_details_preserve_snapshots_latest_scope_and_stale_decisions(): void
    {
        $d = MysqlFixtures::make();
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $d['class']->id);
        $first = $service->submit($d['users'][0]);
        $originalName = $d['course']->nama;
        app(KrsDecisionService::class)->decide($first->id, $d['admin'], 'ditolak', 'Revisi QA diperlukan');
        $second = $service->submit($d['users'][0]);
        $d['course']->update(['nama' => 'RENAMED-COURSE-QA']);
        $this->actingAs($d['admin'])->get(route('dashboard.admin-akademik.krs-pengajuan.show', $first->id))->assertOk()
            ->assertSee($originalName)->assertDontSee('RENAMED-COURSE-QA')->assertDontSee('>Setujui</button>', false)->assertSee('Revisi QA diperlukan');
        $url = route('dashboard.admin-akademik.krs-pengajuan.index', ['q' => $d['profiles'][0]->nim]);
        $this->get($url)->assertOk()->assertViewHas('submissions', fn ($p) => $p->total() === 1 && $p->first()->id === $second->id);
        $this->get($url.'&status=ditolak')->assertOk()->assertViewHas('submissions', fn ($p) => $p->total() === 0);
        $this->get($url.'&status=ditolak&cakupan=semua_versi')->assertOk()->assertViewHas('submissions', fn ($p) => $p->total() === 1);
        $this->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [$first->id, 'disetujui']))->assertSessionHasErrors('krs');
        $this->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [$second->id, 'disetujui']), ['catatan_keputusan' => str_repeat('x', 2001)])->assertSessionHasErrors('catatan_keputusan');
        $this->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [$second->id, 'disetujui']))->assertSessionHasNoErrors();
        $this->assertSame('disetujui', $second->fresh()->krs->status);
        $this->assertSame('ditolak', $first->fresh()->status);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.krs'))->assertOk()->assertSee('Versi 1')->assertSee('Versi 2');
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.peserta', $d['class']->id))->assertOk()->assertSee($d['profiles'][0]->nim);
    }

    public function test_referenced_records_are_protected_and_used_class_fields_are_locked(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        $this->actingAs($d['admin']);
        foreach (['program-studi' => $d['program'], 'semester' => $d['semester'], 'mata-kuliah' => $d['course'], 'dosen' => $d['lecturer'], 'mahasiswa' => $d['profiles'][0]] as $resource => $record) {
            $this->delete(route('dashboard.admin-akademik.'.$resource.'.destroy', $record->id))->assertSessionHas('error');
            $this->assertDatabaseHas($record->getTable(), ['id' => $record->id]);
        }
        $this->get(route('dashboard.admin-akademik.kelas-kuliah.edit', $d['class']->id))->assertOk()->assertSee('Identitas kelas dikunci')->assertSee('disabled', false);
        $this->get(route('dashboard.admin-akademik.kelas-kuliah.show', $d['class']->id))->assertOk()
            ->assertViewHas('participants', fn ($p) => $p->total() === 1);
        $this->put(route('dashboard.admin-akademik.mata-kuliah.update', $d['course']->id), ['program_studi_id' => $d['program']->id, 'kode' => $d['course']->kode, 'nama' => $d['course']->nama, 'sks' => 4])->assertSessionHasErrors('sks');
    }

    public function test_admin_metadata_and_notes_are_escaped_and_search_values_are_bound(): void
    {
        $d = MysqlFixtures::make();
        $payload = '<script>alert("qa")</script>';
        $d['program']->update(['nama' => $payload]);
        $this->actingAs($d['admin'])->get(route('dashboard.admin-akademik.program-studi.show', $d['program']->id))->assertOk()->assertSee($payload)->assertDontSee($payload, false);
        $this->get(route('dashboard.admin-akademik.program-studi.index', ['q' => "' OR 1=1 --"]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 0);
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $d['class']->id);
        $history = $service->submit($d['users'][0]);
        app(KrsDecisionService::class)->decide($history->id, $d['admin'], 'ditolak', $payload);
        $this->get(route('dashboard.admin-akademik.krs-pengajuan.show', $history->id))->assertOk()->assertSee($payload)->assertDontSee($payload, false);
    }

    public function test_master_validation_rejects_foreign_role_and_preserves_single_active_semester(): void
    {
        $d = MysqlFixtures::make();
        $this->actingAs($d['admin']);
        $this->post(route('dashboard.admin-akademik.mahasiswa.store'), ['nim' => 'QA-INVALID', 'nama' => 'QA', 'angkatan' => 2026, 'program_studi_id' => $d['program']->id, 'user_id' => $d['lecturerUser']->id, 'status' => 'aktif'])->assertSessionHasErrors('user_id');
        $this->post(route('dashboard.admin-akademik.semester.store'), ['kode' => 'QA-SECOND-ACTIVE', 'tahun_ajaran' => '2036/2037', 'periode' => 1, 'aktif' => 1])->assertSessionHasErrors('aktif');
        $this->post(route('dashboard.admin-akademik.semester.store'), ['kode' => 'QA-BAD-DATES', 'tahun_ajaran' => '2036/2037', 'periode' => 1, 'tanggal_mulai' => '2036-08-01', 'tanggal_selesai' => '2036-07-01'])->assertSessionHasErrors('tanggal_selesai');
        $this->assertDatabaseMissing('semester', ['kode' => 'QA-SECOND-ACTIVE']);
        $this->post(route('dashboard.admin-akademik.dosen.store'), ['nama' => 'QA', 'program_studi_id' => 999999])->assertSessionHasErrors('program_studi_id');
    }
}
