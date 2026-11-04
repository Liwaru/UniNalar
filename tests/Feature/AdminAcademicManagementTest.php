<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_separate_resource_pages_and_non_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $routes = [
            'program-studi', 'semester', 'mata-kuliah', 'dosen', 'mahasiswa',
        ];

        foreach ($routes as $resource) {
            $name = "dashboard.admin-akademik.{$resource}.index";
            $this->actingAs($admin)->get(route($name))->assertOk()
                ->assertSee('Data operasional belum tersedia.')
                ->assertSee('aria-current="page"', false);
            $this->actingAs($admin)->get(route("dashboard.admin-akademik.{$resource}.create"))->assertOk();

        }

        foreach ([User::ROLE_MAHASISWA, User::ROLE_DOSEN, User::ROLE_KAPRODI] as $level) {
            $otherRole = User::factory()->create(['level' => $level]);
            foreach ($routes as $resource) {
                $base = "dashboard.admin-akademik.{$resource}";
                $this->actingAs($otherRole)->get(route("{$base}.index"))->assertForbidden();
                $this->actingAs($otherRole)->get(route("{$base}.create"))->assertForbidden();
                $this->actingAs($otherRole)->post(route("{$base}.store"))->assertForbidden();
                $this->actingAs($otherRole)->get(route("{$base}.edit", 1))->assertForbidden();
                $this->actingAs($otherRole)->put(route("{$base}.update", 1))->assertForbidden();
                $this->actingAs($otherRole)->delete(route("{$base}.destroy", 1))->assertForbidden();
            }
        }

        $this->actingAs($admin)->get(route('dashboard.admin-akademik'))
            ->assertOk()->assertSee('Program Studi')->assertSee('Data Mahasiswa')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('>Dashboard</a>', false);
    }

    public function test_program_studi_validation_create_and_relation_protected_delete(): void
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.program-studi.store'), [
            'kode' => 'CS-OPS', 'nama' => 'Ilmu Komputer', 'jenjang' => 'S1', 'aktif' => '1',
        ])->assertRedirect(route('dashboard.admin-akademik.program-studi.index'));

        $program = ProgramStudi::query()->where('kode', 'CS-OPS')->firstOrFail();
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.program-studi.store'), [
            'kode' => 'CS-OPS', 'nama' => 'Duplikat', 'jenjang' => 'S1',
        ])->assertSessionHasErrors('kode');

        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        Mahasiswa::query()->create([
            'user_id' => $student->id, 'nim' => '20260001', 'nama' => 'Mahasiswa Operasional',
            'angkatan' => 2026, 'program_studi_id' => $program->id, 'status' => 'aktif',
        ]);

        $this->actingAs($admin)->delete(route('dashboard.admin-akademik.program-studi.destroy', $program))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('program_studi', ['id' => $program->id]);
    }

    public function test_student_profile_links_only_an_existing_student_account_and_demo_data_is_not_listed_as_operational(): void
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $program = ProgramStudi::query()->create(['kode' => 'IT-OPS', 'nama' => 'Informatika', 'jenjang' => 'S1', 'aktif' => true]);
        ProgramStudi::query()->create(['kode' => 'IT-DEMO', 'nama' => 'Informatika Demo', 'jenjang' => 'S1', 'aktif' => true]);
        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $originalPasswordHash = $student->password;

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.mahasiswa.store'), [
            'nim' => '20260003', 'nama' => 'Profil Dengan Program Demo', 'angkatan' => 2026,
            'program_studi_id' => ProgramStudi::query()->where('kode', 'IT-DEMO')->value('id'), 'status' => 'aktif',
        ])->assertSessionHasErrors('program_studi_id');

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.mahasiswa.store'), [
            'user_id' => $student->id, 'nim' => '20260002', 'nama' => 'Mahasiswa Terdaftar',
            'angkatan' => 2026, 'program_studi_id' => $program->id, 'status' => 'aktif',
        ])->assertRedirect(route('dashboard.admin-akademik.mahasiswa.index'));

        $this->assertDatabaseHas('mahasiswa', ['user_id' => $student->id, 'nim' => '20260002', 'program_studi_id' => $program->id]);
        $this->assertSame($originalPasswordHash, $student->fresh()->password);

        $this->actingAs($admin)->get(route('dashboard.admin-akademik.program-studi.index'))
            ->assertOk()->assertSee('IT-OPS')->assertDontSee('IT-DEMO')->assertDontSee('Informatika Demo');

        $profile = Mahasiswa::query()->where('user_id', $student->id)->firstOrFail();
        $this->actingAs($admin)->get(route('dashboard.admin-akademik.mahasiswa.edit', $profile))->assertOk();
        $this->actingAs($admin)->put(route('dashboard.admin-akademik.mahasiswa.update', $profile), [
            'user_id' => $student->id, 'nim' => '20260002', 'nama' => 'Mahasiswa Terdaftar Revisi',
            'angkatan' => 2026, 'program_studi_id' => $program->id, 'status' => 'aktif',
        ])->assertRedirect(route('dashboard.admin-akademik.mahasiswa.index'));
        $this->actingAs($admin)->delete(route('dashboard.admin-akademik.mahasiswa.destroy', $profile))
            ->assertRedirect(route('dashboard.admin-akademik.mahasiswa.index'));
        $this->assertDatabaseMissing('mahasiswa', ['id' => $profile->id]);
    }

    public function test_semester_course_and_lecturer_resources_support_create_update_and_delete(): void
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $program = ProgramStudi::query()->create(['kode' => 'OPS-01', 'nama' => 'Program Operasional', 'jenjang' => 'S1', 'aktif' => true]);

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.semester.store'), [
            'kode' => '2026-GANJIL', 'tahun_ajaran' => '2026/2027', 'periode' => 1,
            'tanggal_mulai' => '2026-08-01', 'tanggal_selesai' => '2026-12-31', 'aktif' => '1',
        ])->assertRedirect(route('dashboard.admin-akademik.semester.index'));
        $semester = Semester::query()->where('kode', '2026-GANJIL')->firstOrFail();
        $this->actingAs($admin)->get(route('dashboard.admin-akademik.semester.edit', $semester))->assertOk();
        $this->actingAs($admin)->put(route('dashboard.admin-akademik.semester.update', $semester), [
            'kode' => '2026-GANJIL', 'tahun_ajaran' => '2026/2027', 'periode' => 1,
            'tanggal_mulai' => '2026-08-01', 'tanggal_selesai' => '2026-12-31', 'aktif' => '1',
        ])->assertRedirect(route('dashboard.admin-akademik.semester.index'));

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.mata-kuliah.store'), [
            'program_studi_id' => $program->id, 'kode' => 'OPS101', 'nama' => 'Dasar Operasional', 'sks' => 3, 'semester_rekomendasi' => 1,
        ])->assertRedirect(route('dashboard.admin-akademik.mata-kuliah.index'));
        $course = MataKuliah::query()->where('kode', 'OPS101')->firstOrFail();
        $this->actingAs($admin)->get(route('dashboard.admin-akademik.mata-kuliah.edit', $course))->assertOk();
        $this->actingAs($admin)->put(route('dashboard.admin-akademik.mata-kuliah.update', $course), [
            'program_studi_id' => $program->id, 'kode' => 'OPS101', 'nama' => 'Dasar Operasional Revisi', 'sks' => 2, 'semester_rekomendasi' => 1,
        ])->assertRedirect(route('dashboard.admin-akademik.mata-kuliah.index'));

        $lecturerUser = User::factory()->create(['level' => User::ROLE_DOSEN]);
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.dosen.store'), [
            'user_id' => $lecturerUser->id, 'nidn' => '19880001', 'nama' => 'Dosen Operasional',
            'email' => 'dosen.operasional@example.test', 'program_studi_id' => $program->id, 'aktif' => '1',
        ])->assertRedirect(route('dashboard.admin-akademik.dosen.index'));
        $lecturer = Dosen::query()->where('nidn', '19880001')->firstOrFail();
        $this->actingAs($admin)->get(route('dashboard.admin-akademik.dosen.edit', $lecturer))->assertOk();
        $this->actingAs($admin)->put(route('dashboard.admin-akademik.dosen.update', $lecturer), [
            'user_id' => $lecturerUser->id, 'nidn' => '19880001', 'nama' => 'Dosen Operasional Revisi',
            'email' => 'dosen.operasional@example.test', 'program_studi_id' => $program->id, 'aktif' => '1',
        ])->assertRedirect(route('dashboard.admin-akademik.dosen.index'));

        $this->actingAs($admin)->delete(route('dashboard.admin-akademik.dosen.destroy', $lecturer))->assertRedirect(route('dashboard.admin-akademik.dosen.index'));
        $this->actingAs($admin)->delete(route('dashboard.admin-akademik.mata-kuliah.destroy', $course))->assertRedirect(route('dashboard.admin-akademik.mata-kuliah.index'));
        $this->actingAs($admin)->delete(route('dashboard.admin-akademik.semester.destroy', $semester))->assertRedirect(route('dashboard.admin-akademik.semester.index'));

        $this->assertDatabaseMissing('dosen', ['id' => $lecturer->id]);
        $this->assertDatabaseMissing('mata_kuliah', ['id' => $course->id]);
        $this->assertDatabaseMissing('semester', ['id' => $semester->id]);
    }

    public function test_reserved_demo_identifiers_are_rejected_and_only_one_new_semester_can_be_active(): void
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.program-studi.store'), [
            'kode' => 'IT-DEMO', 'nama' => 'Demo', 'jenjang' => 'S1',
        ])->assertSessionHasErrors('kode');

        $program = ProgramStudi::query()->create(['kode' => 'IT-REAL', 'nama' => 'Informatika', 'jenjang' => 'S1', 'aktif' => true]);
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.semester.store'), [
            'kode' => 'DEMO-TEST', 'tahun_ajaran' => '2026/2027', 'periode' => 1,
        ])->assertSessionHasErrors('kode');
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.semester.store'), [
            'kode' => '2026-GANJIL', 'tahun_ajaran' => '2026/2027', 'periode' => 1, 'aktif' => '1',
        ])->assertRedirect(route('dashboard.admin-akademik.semester.index'));
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.semester.store'), [
            'kode' => '2026-GENAP', 'tahun_ajaran' => '2026/2027', 'periode' => 2, 'aktif' => '1',
        ])->assertSessionHasErrors('aktif');

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.mata-kuliah.store'), [
            'program_studi_id' => $program->id, 'kode' => 'DEMO-MK-1', 'nama' => 'Demo', 'sks' => 3,
        ])->assertSessionHasErrors('kode');
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.dosen.store'), [
            'nidn' => 'DEMO123', 'nama' => 'Demo',
        ])->assertSessionHasErrors('nidn');
        $this->actingAs($admin)->post(route('dashboard.admin-akademik.mahasiswa.store'), [
            'nim' => 'DEMO123', 'nama' => 'Demo', 'angkatan' => 2026,
            'program_studi_id' => $program->id, 'status' => 'aktif',
        ])->assertSessionHasErrors('nim');
    }

    public function test_search_pagination_and_blade_output_are_safe(): void
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);

        for ($number = 1; $number <= 17; $number++) {
            ProgramStudi::query()->create([
                'kode' => 'OPS-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'nama' => $number === 1 ? '<script>alert(1)</script>' : "Program Operasional {$number}",
                'jenjang' => 'S1', 'aktif' => true,
            ]);
        }

        $indexUrl = route('dashboard.admin-akademik.program-studi.index');
        $this->actingAs($admin)->get($indexUrl)
            ->assertOk()->assertSee('page=2')->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($admin)->get($indexUrl.'?page=2')
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($admin)->get($indexUrl.'?q=OPS-12')
            ->assertOk()->assertSee('OPS-12')->assertSee('Program Operasional 12')
            ->assertDontSee('OPS-02');

        $this->actingAs($admin)->get($indexUrl.'?q=%3Cscript%3Ealert(1)%3C%2Fscript%3E')
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
