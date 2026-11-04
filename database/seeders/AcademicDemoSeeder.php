<?php

namespace Database\Seeders;

use App\Models\AnalisisRisiko;
use App\Models\Dosen;
use App\Models\FaktorRisiko;
use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('AcademicDemoSeeder berisi data sintetis dan hanya boleh dijalankan pada environment local atau testing.');
        }

        $studentCount = max(1, min(100, (int) env('UNINLAR_DUMMY_STUDENTS', 40)));
        $now = now();

        $demoUsers = collect([
            ['name' => 'Mahasiswa Demo', 'username' => 'mahasiswa', 'email' => 'mahasiswa@demo.uninlar.test', 'password' => 'mahasiswa', 'level' => User::ROLE_MAHASISWA],
            ['name' => 'Dosen Demo', 'username' => 'dosen', 'email' => 'dosen@demo.uninlar.test', 'password' => 'dosen', 'level' => User::ROLE_DOSEN],
            ['name' => 'Admin Akademik Demo', 'username' => 'admin', 'email' => 'admin@demo.uninlar.test', 'password' => 'admin', 'level' => User::ROLE_ADMIN_AKADEMIK],
            ['name' => 'Kepala Program Studi Demo', 'username' => 'owner', 'email' => 'owner@demo.uninlar.test', 'password' => 'owner', 'level' => User::ROLE_KAPRODI],
        ])->mapWithKeys(function (array $account): array {
            $user = User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => Hash::make($account['password']),
                    'level' => $account['level'],
                ],
            );

            return [$account['username'] => $user];
        });

        $program = ProgramStudi::firstOrCreate(
            ['kode' => 'TI-DEMO'],
            ['nama' => 'Informatika (Demo)', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Demo', 'aktif' => true],
        );

        $semester = Semester::firstOrCreate(
            ['kode' => 'DEMO-2026-1'],
            ['tahun_ajaran' => '2026/2027', 'periode' => 1, 'tanggal_mulai' => $now->copy()->startOfYear(), 'tanggal_selesai' => $now->copy()->endOfYear(), 'aktif' => true],
        );

        $lecturers = collect(range(1, 2))->map(fn (int $number) => Dosen::firstOrCreate(
            ['nidn' => 'DEMO'.str_pad((string) $number, 10, '0', STR_PAD_LEFT)],
            ['nama' => "Dosen Demo {$number}", 'email' => "dosen{$number}@uninlar.test", 'program_studi_id' => $program->id],
        ));

        $classes = collect(range(1, 6))->map(function (int $number) use ($program, $semester, $lecturers) {
            $course = MataKuliah::firstOrCreate(
                ['kode' => 'DEMO-MK-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT)],
                ['program_studi_id' => $program->id, 'nama' => "Mata Kuliah Demo {$number}", 'sks' => 3, 'semester_rekomendasi' => (($number - 1) % 8) + 1],
            );

            return KelasKuliah::firstOrCreate(
                ['mata_kuliah_id' => $course->id, 'semester_id' => $semester->id, 'kode_kelas' => 'A'],
                ['dosen_id' => $lecturers[($number - 1) % $lecturers->count()]->id, 'kapasitas' => 40, 'ruang' => 'Demo'],
            );
        });

        for ($number = 1; $number <= $studentCount; $number++) {
            $student = Mahasiswa::firstOrCreate(
                ['nim' => 'DEMO'.str_pad((string) $number, 5, '0', STR_PAD_LEFT)],
                [
                    'nama' => fake()->name(),
                    'email' => 'mahasiswa'.str_pad((string) $number, 5, '0', STR_PAD_LEFT).'@uninlar.test',
                    'angkatan' => (int) $now->format('Y'),
                    'program_studi_id' => $program->id,
                    'status' => 'aktif',
                ],
            );

            $krs = Krs::firstOrCreate(
                ['mahasiswa_id' => $student->id, 'semester_id' => $semester->id],
                ['status' => 'disetujui', 'diajukan_pada' => $now],
            );

            foreach ($classes->take(3) as $index => $class) {
                $detail = KrsDetail::firstOrCreate(
                    ['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id],
                    ['status' => 'diambil'],
                );

                Nilai::firstOrCreate(
                    ['krs_detail_id' => $detail->id],
                    ['nilai_angka' => 70 + ($number % 25), 'nilai_huruf' => 'B', 'nilai_mutu' => 3.0],
                );

                Kehadiran::firstOrCreate(
                    ['krs_detail_id' => $detail->id, 'pertemuan_ke' => 1],
                    ['dicatat_oleh_dosen_id' => $class->dosen_id, 'tanggal' => $now->toDateString(), 'status' => 'hadir'],
                );
            }

            foreach ([1, 2] as $snapshot) {
                $analyzedAt = $now->copy()->subMonths(2 - $snapshot)->startOfDay();
                $analysis = AnalisisRisiko::firstOrCreate(
                    ['mahasiswa_id' => $student->id, 'semester_id' => $semester->id, 'dianalisis_pada' => $analyzedAt],
                    ['skor' => $snapshot === 1 ? 0.68 : 0.42, 'kategori' => $snapshot === 1 ? 'tinggi' : 'sedang', 'versi_model' => 'demo-v1', 'ringkasan' => ['sumber' => 'data_dummy']],
                );

                $factor = FaktorRisiko::firstOrCreate(
                    ['analisis_risiko_id' => $analysis->id, 'kode_faktor' => 'KEHADIRAN'],
                    ['nama' => 'Kehadiran', 'nilai_faktor' => 0.35, 'bobot' => 0.4, 'kontribusi' => 0.14, 'penjelasan' => 'Faktor contoh untuk data dummy.'],
                );

                $recommendation = $analysis->rekomendasiIntervensi()->firstOrCreate(
                    ['judul' => 'Konsultasi akademik demo'],
                    ['faktor_risiko_id' => $factor->id, 'deskripsi' => 'Tindak lanjut contoh untuk pengujian alur.', 'prioritas' => 'normal', 'status' => 'direkomendasikan'],
                );

                TindakLanjut::firstOrCreate(
                    ['rekomendasi_intervensi_id' => $recommendation->id, 'status' => 'direncanakan'],
                    ['dilakukan_pada' => $analyzedAt->copy()->addWeek(), 'catatan' => 'Catatan tindak lanjut dummy.'],
                );
            }
        }

        $this->attachDemoProfile($demoUsers['mahasiswa'], Mahasiswa::where('nim', 'DEMO00001')->firstOrFail());
        $this->attachDemoProfile($demoUsers['dosen'], $lecturers->first());
    }

    private function attachDemoProfile(User $user, Mahasiswa|Dosen $profile): void
    {
        $linkedProfile = $profile::query()->where('user_id', $user->id)->first();

        if (($profile->user_id !== null && $profile->user_id !== $user->id)
            || ($linkedProfile !== null && $linkedProfile->isNot($profile))) {
            throw new \RuntimeException("Akun demo {$user->username} sudah terhubung dengan profil lain.");
        }

        if ($profile->user_id === null) {
            $profile->user()->associate($user);
            $profile->save();
        }
    }
}
