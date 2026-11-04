<?php

namespace Tests\Feature;

use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class AdminFormStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unchecked_active_state_survives_validation_failure(): void
    {
        $admin = User::factory()->create(['level' => 3]);
        $program = ProgramStudi::create(['kode' => 'TI', 'nama' => 'Teknologi Informasi', 'jenjang' => 'S1', 'aktif' => true]);
        $edit = route('dashboard.admin-akademik.program-studi.edit', $program);
        $this->actingAs($admin)->from($edit)->put(route('dashboard.admin-akademik.program-studi.update', $program), [
            'kode' => 'TI', 'nama' => '', 'jenjang' => 'S1',
        ])->assertSessionHasErrors('nama');
        $html = $this->get($edit)->assertOk()->getContent();
        preg_match('/<input[^>]*type="checkbox"[^>]*name="aktif"[^>]*>/', $html, $match);
        $this->assertNotEmpty($match);
        $this->assertStringNotContainsString('checked', $match[0]);
        $this->assertTrue($program->fresh()->aktif);
    }

    public function test_admin_cannot_create_profiles_that_immediately_disappear_into_demo_filter(): void
    {
        $admin = User::factory()->create(['level' => 3]);
        $program = ProgramStudi::create(['kode' => 'IT', 'nama' => 'Teknologi Informasi', 'jenjang' => 'S1', 'aktif' => true]);
        foreach (['dosen', 'mahasiswa'] as $resource) {
            $this->actingAs($admin)->post(route('dashboard.admin-akademik.'.$resource.'.store'), [
                'nama' => $resource === 'dosen' ? 'Fajar Aditya Nugraha' : 'Alya Rahma Putri',
                'email' => $resource.'@demo.uninlar.test', 'nim' => '2026010001',
                'angkatan' => 2026, 'program_studi_id' => $program->id, 'status' => 'aktif',
            ])->assertSessionHasErrors('email');
            $this->assertDatabaseCount($resource, 0);
        }
    }

    public function test_credits_cannot_be_rewritten_after_course_is_used_in_krs(): void
    {
        $data = MysqlFixtures::make();
        MysqlFixtures::detail($data);
        $this->actingAs($data['admin'])->put(route('dashboard.admin-akademik.mata-kuliah.update', $data['course']), [
            'program_studi_id' => $data['program']->id, 'kode' => $data['course']->kode,
            'nama' => $data['course']->nama, 'sks' => 4,
        ])->assertSessionHasErrors('sks');
        $this->assertSame(3, $data['course']->fresh()->sks);
    }

    public function test_student_contact_edit_cannot_hide_their_operational_profile(): void
    {
        $d = MysqlFixtures::make();
        $this->actingAs($d['users'][0])->put(route('dashboard.mahasiswa.profil.update'), [
            'email' => 'alya@demo.uninlar.test',
        ])->assertSessionHasErrors('email');
        $this->assertNull($d['profiles'][0]->fresh()->email);
    }
}
