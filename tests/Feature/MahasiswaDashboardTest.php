<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\FaktorRisiko;
use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Models\User;
use Database\Seeders\AcademicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_synthetic_demo_profile_is_not_used_as_operational_academic_data(): void
    {
        $this->seed(AcademicDemoSeeder::class);
        $studentUser = User::query()->where('username', 'mahasiswa')->firstOrFail();
        $syntheticProfile = Mahasiswa::query()->where('nim', 'DEMO00001')->firstOrFail();

        $response = $this->actingAs($studentUser)->get(route('dashboard.mahasiswa'));

        $response->assertOk()
            ->assertViewIs('dashboard.mahasiswa.index')
            ->assertSee('Profil Mahasiswa Belum Tersedia')
            ->assertDontSee('Akun demo')
            ->assertSee('Belum tersedia')
            ->assertSee('Grafik tersedia setelah nilai angka tercatat')
            ->assertDontSee('DATA DEMO')
            ->assertDontSee($syntheticProfile->nama)
            ->assertDontSee('DEMO00001')
            ->assertDontSee('Informatika (Demo)')
            ->assertDontSee('DEMO-2026-1')
            ->assertDontSee('71.00')
            ->assertDontSee('100.0%')
            ->assertDontSee('0.4200')
            ->assertDontSee('Konsultasi akademik demo');

        foreach ($this->studentFeatureRoutes() as $route) {
            $featureResponse = $this->actingAs($studentUser)->get(route($route));
            $featureResponse->assertOk()
                ->assertDontSee('Akun demo')
                ->assertDontSee($syntheticProfile->nama)
                ->assertDontSee('DEMO00001')
                ->assertDontSee('Informatika (Demo)')
                ->assertDontSee('Konsultasi akademik demo');
        }
    }

    public function test_real_linked_profile_and_only_its_academic_records_drive_dashboard(): void
    {
        $studentUser = User::factory()->create(['name' => 'Nama Akun Berbeda', 'level' => User::ROLE_MAHASISWA]);
        $program = ProgramStudi::query()->create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak (RPL)', 'jenjang' => 'S1']);
        $student = Mahasiswa::query()->create([
            'user_id' => $studentUser->id,
            'nim' => 'RPL-TEST-001',
            'nama' => 'Mahasiswa',
            'angkatan' => 2024,
            'program_studi_id' => $program->id,
            'status' => 'aktif',
        ]);
        $pastSemester = Semester::query()->create(['kode' => '2025-GENAP', 'tahun_ajaran' => '2025/2026', 'periode' => 2, 'aktif' => false]);
        $activeSemester = Semester::query()->create(['kode' => '2026-GANJIL', 'tahun_ajaran' => '2026/2027', 'periode' => 1, 'aktif' => true]);
        $course = MataKuliah::query()->create(['program_studi_id' => $program->id, 'kode' => 'RPL101', 'nama' => 'Pemrograman Dasar', 'sks' => 3]);

        $ownDetails = collect([$pastSemester, $activeSemester])->map(function (Semester $semester) use ($student, $course): KrsDetail {
            $class = KelasKuliah::query()->create(['mata_kuliah_id' => $course->id, 'semester_id' => $semester->id, 'kode_kelas' => 'A']);
            $krs = Krs::query()->create(['mahasiswa_id' => $student->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);

            return KrsDetail::query()->create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id, 'status' => 'diambil']);
        });

        Nilai::query()->create(['krs_detail_id' => $ownDetails[0]->id, 'nilai_angka' => 80, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => now()]);
        Nilai::query()->create(['krs_detail_id' => $ownDetails[1]->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        foreach ($ownDetails as $index => $detail) {
            foreach (['hadir', $index === 0 ? 'hadir' : 'alpa'] as $meeting => $status) {
                Kehadiran::query()->create([
                    'krs_detail_id' => $detail->id,
                    'pertemuan_ke' => $meeting + 1,
                    'tanggal' => now()->subDays($meeting)->toDateString(),
                    'status' => $status,
                ]);
            }
        }
        Kehadiran::query()->create([
            'krs_detail_id' => $ownDetails[1]->id,
            'pertemuan_ke' => 3,
            'tanggal' => now()->toDateString(),
            'status' => 'status-baru-belum-dipetakan',
        ]);

        $demoSemester = Semester::query()->create(['kode' => 'DEMO-TEST', 'tahun_ajaran' => '2026/2027', 'periode' => 2, 'aktif' => false]);
        $demoCourse = MataKuliah::query()->create(['program_studi_id' => $program->id, 'kode' => 'DEMO-MK-01', 'nama' => 'Mata Kuliah Demo', 'sks' => 3]);
        $demoClass = KelasKuliah::query()->create(['mata_kuliah_id' => $demoCourse->id, 'semester_id' => $demoSemester->id, 'kode_kelas' => 'A']);
        $demoKrs = Krs::query()->create(['mahasiswa_id' => $student->id, 'semester_id' => $demoSemester->id, 'status' => 'disetujui']);
        $demoDetail = KrsDetail::query()->create(['krs_id' => $demoKrs->id, 'kelas_kuliah_id' => $demoClass->id, 'status' => 'diambil']);
        Nilai::query()->create(['krs_detail_id' => $demoDetail->id, 'nilai_angka' => 1, 'nilai_huruf' => 'E', 'nilai_mutu' => 0]);
        Kehadiran::query()->create(['krs_detail_id' => $demoDetail->id, 'pertemuan_ke' => 1, 'tanggal' => now()->toDateString(), 'status' => 'alpa']);

        $analysis = AnalisisRisiko::query()->create([
            'mahasiswa_id' => $student->id,
            'semester_id' => $activeSemester->id,
            'skor' => 0.25,
            'kategori' => 'rendah',
            'ringkasan' => [],
            'dianalisis_pada' => now(),
        ]);
        $factor = FaktorRisiko::query()->create([
            'analisis_risiko_id' => $analysis->id,
            'kode_faktor' => 'KEHADIRAN',
            'nama' => 'Kehadiran tercatat',
            'kontribusi' => 0.1,
            'penjelasan' => 'Ringkasan faktor dari data uji.',
        ]);
        $recommendation = RekomendasiIntervensi::query()->create([
            'analisis_risiko_id' => $analysis->id,
            'faktor_risiko_id' => $factor->id,
            'judul' => 'Tinjau jadwal belajar',
            'deskripsi' => 'Diskusikan pengelolaan waktu dengan pembimbing.',
            'prioritas' => 'normal',
            'status' => 'direkomendasikan',
        ]);
        TindakLanjut::query()->create([
            'rekomendasi_intervensi_id' => $recommendation->id,
            'dilakukan_pada' => now(),
            'status' => 'direncanakan',
            'catatan' => 'PRIVATE INTERNAL NOTE',
        ]);

        $demoAnalysis = AnalisisRisiko::query()->create([
            'mahasiswa_id' => $student->id,
            'semester_id' => null,
            'skor' => 0.99,
            'kategori' => 'tinggi',
            'versi_model' => 'demo-v1',
            'ringkasan' => ['sumber' => 'data_dummy'],
            'dianalisis_pada' => now()->addMinute(),
        ]);
        $demoFactor = FaktorRisiko::query()->create([
            'analisis_risiko_id' => $demoAnalysis->id,
            'kode_faktor' => 'DEMO',
            'nama' => 'Faktor Demo Tidak Boleh Tampil',
            'kontribusi' => 0.99,
        ]);
        $demoRecommendation = RekomendasiIntervensi::query()->create([
            'analisis_risiko_id' => $demoAnalysis->id,
            'faktor_risiko_id' => $demoFactor->id,
            'judul' => 'Rekomendasi Demo Tidak Boleh Tampil',
            'prioritas' => 'tinggi',
            'status' => 'direkomendasikan',
        ]);
        TindakLanjut::query()->create([
            'rekomendasi_intervensi_id' => $demoRecommendation->id,
            'dilakukan_pada' => now()->addDay(),
            'status' => 'direncanakan',
        ]);

        $otherUser = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $other = Mahasiswa::query()->create([
            'user_id' => $otherUser->id,
            'nim' => 'RPL-TEST-OTHER',
            'nama' => 'Mahasiswa Lain',
            'angkatan' => 2024,
            'program_studi_id' => $program->id,
            'status' => 'aktif',
        ]);
        $otherKrs = Krs::query()->create(['mahasiswa_id' => $other->id, 'semester_id' => $activeSemester->id, 'status' => 'disetujui']);
        $otherClass = KelasKuliah::query()->create(['mata_kuliah_id' => $course->id, 'semester_id' => $activeSemester->id, 'kode_kelas' => 'B']);
        $otherDetail = KrsDetail::query()->create(['krs_id' => $otherKrs->id, 'kelas_kuliah_id' => $otherClass->id, 'status' => 'diambil']);
        Nilai::query()->create(['krs_detail_id' => $otherDetail->id, 'nilai_angka' => 99.99, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);

        $this->actingAs($studentUser)->get(route('dashboard.mahasiswa'))
            ->assertOk()
            ->assertSee('Mahasiswa')
            ->assertSee('RPL-TEST-001')
            ->assertSee('Rekayasa Perangkat Lunak (RPL)')
            ->assertSee('85.00')
            ->assertSee('Catatan kehadiran')
            ->assertSee('75.0%')
            ->assertSee('Dari 4 pertemuan tercatat dengan status yang dikenali.')
            ->assertSee('1 catatan dengan status yang belum dipetakan dikecualikan.')
            ->assertSee('Mata kuliah aktif')
            ->assertDontSee('Pemrograman Dasar')
            ->assertDontSee('0.2500')
            ->assertDontSee('Kehadiran tercatat')
            ->assertDontSee('Tinjau jadwal belajar')
            ->assertDontSee('Nama Akun Berbeda')
            ->assertDontSee('Mahasiswa Lain')
            ->assertDontSee('99.99');

        $this->get(route('dashboard.mahasiswa.krs'))
            ->assertOk()->assertSee('Pemrograman Dasar')->assertDontSee('Mata Kuliah Demo');
        $this->get(route('dashboard.mahasiswa.nilai'))
            ->assertOk()->assertSee('80.00')->assertSee('90.00')->assertDontSee('99.99')->assertDontSee('1.00');
        $this->get(route('dashboard.mahasiswa.kehadiran'))
            ->assertOk()->assertSee('Pemrograman Dasar')->assertDontSee('Mata Kuliah Demo');
        $this->get(route('dashboard.mahasiswa.analisis-risiko'))
            ->assertOk()->assertSee('0.2500')->assertSee('Kehadiran tercatat')
            ->assertDontSee('0.9900')->assertDontSee('Faktor Demo Tidak Boleh Tampil');
        $this->get(route('dashboard.mahasiswa.rekomendasi'))
            ->assertOk()->assertSee('Tinjau jadwal belajar')->assertDontSee('Rekomendasi Demo Tidak Boleh Tampil');
        $this->get(route('dashboard.mahasiswa.tindak-lanjut'))
            ->assertOk()->assertSee('Tinjau jadwal belajar')->assertDontSee('PRIVATE INTERNAL NOTE');
        $this->get(route('dashboard.mahasiswa.profil'))
            ->assertOk()->assertSee('RPL-TEST-001')->assertSee('Rekayasa Perangkat Lunak (RPL)')
            ->assertDontSee('Nama Akun Berbeda')->assertDontSee('Mahasiswa Lain');
    }

    public function test_student_route_requires_login_and_redirects_to_the_student_dashboard(): void
    {
        $this->get(route('dashboard.mahasiswa'))->assertRedirect(route('login'));

        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.mahasiswa'));
    }

    public function test_other_roles_cannot_open_the_student_dashboard(): void
    {
        foreach ([User::ROLE_DOSEN, User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI] as $level) {
            $user = User::factory()->create(['level' => $level]);
            $this->actingAs($user)->get(route('dashboard.mahasiswa'))->assertForbidden();
        }
    }

    public function test_dashboard_renders_informative_empty_states_without_academic_data(): void
    {
        $program = ProgramStudi::query()->create(['kode' => 'EMPTY', 'nama' => 'Program Kosong', 'jenjang' => 'S1']);
        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        Mahasiswa::query()->create([
            'user_id' => $student->id,
            'nim' => 'EMPTY-001',
            'nama' => 'Mahasiswa Tanpa Data',
            'angkatan' => 2026,
            'program_studi_id' => $program->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($student)->get(route('dashboard.mahasiswa'))
            ->assertOk()
            ->assertSee('Belum tersedia')
            ->assertSee('Data nilai angka belum tersedia')
            ->assertSee('Data kehadiran belum tersedia')
            ->assertSee('Grafik tersedia setelah nilai angka tercatat');
    }

    public function test_student_account_without_linked_profile_gets_a_helpful_empty_state(): void
    {
        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);

        $this->actingAs($student)->get(route('dashboard.mahasiswa'))
            ->assertOk()
            ->assertSee('Profil Mahasiswa Belum Tersedia');

        $this->get(route('dashboard.mahasiswa.profil'))
            ->assertOk()
            ->assertSee('Profil Mahasiswa Belum Tersedia')
            ->assertSee('Profil akademik belum terhubung ke akun ini. Silakan hubungi Admin Akademik.')
            ->assertDontSee('demo');
    }

    public function test_student_can_update_only_their_contact_fields(): void
    {
        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $otherStudent = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $program = ProgramStudi::query()->create(['kode' => 'CONTACT', 'nama' => 'Program Kontak', 'jenjang' => 'S1']);
        $profile = Mahasiswa::query()->create([
            'user_id' => $student->id,
            'nim' => 'CONTACT-001',
            'nama' => 'Mahasiswa Kontak',
            'email' => 'before@example.test',
            'no_telepon' => '081200000001',
            'angkatan' => 2025,
            'program_studi_id' => $program->id,
            'status' => 'aktif',
        ]);
        Mahasiswa::query()->create([
            'user_id' => $otherStudent->id,
            'nim' => 'CONTACT-002',
            'nama' => 'Mahasiswa Lain',
            'email' => 'other@example.test',
            'angkatan' => 2025,
            'program_studi_id' => $program->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($student)->from(route('dashboard.mahasiswa.profil'))
            ->put(route('dashboard.mahasiswa.profil.update'), [
                'email' => 'updated@example.test',
                'no_telepon' => '081299999999',
                'nim' => 'CHANGED-NIM',
                'nama' => 'CHANGED-NAME',
                'angkatan' => 1900,
                'program_studi_id' => 9999,
                'status' => 'nonaktif',
            ])
            ->assertRedirect(route('dashboard.mahasiswa.profil'))
            ->assertSessionHas('status', 'Informasi kontak berhasil diperbarui.');

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $profile->id,
            'nim' => 'CONTACT-001',
            'nama' => 'Mahasiswa Kontak',
            'angkatan' => 2025,
            'email' => 'updated@example.test',
            'no_telepon' => '081299999999',
            'program_studi_id' => $program->id,
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('mahasiswa', ['nim' => 'CONTACT-002', 'email' => 'other@example.test']);
        $this->get(route('dashboard.mahasiswa.profil'))->assertOk()
            ->assertSee('Informasi kontak berhasil diperbarui.')
            ->assertSee('value="updated@example.test"', false);

        $this->actingAs($student)->put(route('dashboard.mahasiswa.profil.update'), [
            'email' => 'other@example.test',
            'no_telepon' => '0812',
        ])->assertSessionHasErrors('email');
        $this->get(route('dashboard.mahasiswa.profil'))->assertOk()
            ->assertSee('Periksa kembali isian Anda.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="other@example.test"', false)
            ->assertSee('value="0812"', false);
    }

    public function test_demo_profiles_and_other_roles_cannot_update_student_contacts(): void
    {
        $this->seed(AcademicDemoSeeder::class);
        $demoStudent = User::query()->where('username', 'mahasiswa')->firstOrFail();
        $this->actingAs($demoStudent)->put(route('dashboard.mahasiswa.profil.update'), [
            'email' => 'changed@example.test', 'no_telepon' => '0812',
        ])->assertNotFound();

        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $this->actingAs($admin)->put(route('dashboard.mahasiswa.profil.update'), [
            'email' => 'changed@example.test', 'no_telepon' => '0812',
        ])->assertForbidden();
    }

    public function test_student_feature_routes_require_auth_and_have_single_route_active_navigation(): void
    {
        foreach ($this->studentFeatureRoutes() as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }

        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        foreach (['dashboard.mahasiswa', ...$this->studentFeatureRoutes()] as $route) {
            $response = $this->actingAs($student)->get(route($route));
            $response->assertOk()
                ->assertSee('href="'.route('dashboard').'"', false)
                ->assertDontSee('>Dashboard</a>', false);

            $activeLinks = substr_count($response->getContent(), 'aria-current="page"');
            $expectedActiveLinks = 1;
            $this->assertSame($expectedActiveLinks, $activeLinks, "Unexpected active navigation items on {$route}.");
        }

        foreach ([User::ROLE_DOSEN, User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI] as $level) {
            $otherRole = User::factory()->create(['level' => $level]);
            foreach (['dashboard.mahasiswa', ...$this->studentFeatureRoutes()] as $route) {
                $this->actingAs($otherRole)->get(route($route))->assertForbidden();
            }
        }
    }

    private function studentFeatureRoutes(): array
    {
        return [
            'dashboard.mahasiswa.krs',
            'dashboard.mahasiswa.nilai',
            'dashboard.mahasiswa.kehadiran',
            'dashboard.mahasiswa.analisis-risiko',
            'dashboard.mahasiswa.rekomendasi',
            'dashboard.mahasiswa.tindak-lanjut',
            'dashboard.mahasiswa.profil',
        ];
    }
}
