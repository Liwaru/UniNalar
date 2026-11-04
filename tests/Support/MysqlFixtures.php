<?php

namespace Tests\Support;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;

final class MysqlFixtures
{
    public static function make(int $students = 1, ?int $capacity = 30): array
    {
        $tag = strtoupper(bin2hex(random_bytes(4)));
        $program = ProgramStudi::create(['kode' => 'QA'.$tag, 'nama' => 'Teknologi Informasi', 'jenjang' => 'S1', 'aktif' => true]);
        $semester = Semester::firstOrCreate(['tahun_ajaran' => '2026/2027', 'periode' => 1], ['kode' => '2026-GANJIL', 'aktif' => true,
            'krs_mulai_pada' => now('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'), 'krs_selesai_pada' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s')]);
        // A prior QA down/up removes window values but retains the semester row.
        $semester->forceFill(['aktif' => true,
            'krs_mulai_pada' => now('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'),
            'krs_selesai_pada' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s'),
        ])->save();
        $lecturerUser = User::factory()->create(['name' => 'Fajar Aditya Nugraha', 'level' => 2]);
        $lecturer = Dosen::create(['user_id' => $lecturerUser->id, 'nama' => 'Fajar Aditya Nugraha', 'program_studi_id' => $program->id, 'aktif' => true]);
        $course = MataKuliah::create(['program_studi_id' => $program->id, 'kode' => 'QA'.$tag, 'nama' => 'Algoritma dan Pemrograman', 'sks' => 3]);
        $class = KelasKuliah::create(['mata_kuliah_id' => $course->id, 'semester_id' => $semester->id, 'dosen_id' => $lecturer->id, 'kode_kelas' => 'A', 'kapasitas' => $capacity]);
        $admin = User::factory()->create(['name' => 'Nadira Wulandari', 'level' => 3]);
        $users = collect();
        $profiles = collect();
        foreach (range(1, $students) as $number) {
            $name = ['Alya Rahma Putri', 'Bima Satya Pradana'][($number - 1) % 2];
            $user = User::factory()->create(['name' => $name, 'level' => 1]);
            $users->push($user);
            $profiles->push(Mahasiswa::create(['user_id' => $user->id, 'nim' => '202601'.str_pad((string) (Mahasiswa::max('id') + 1), 6, '0', STR_PAD_LEFT), 'nama' => $name, 'angkatan' => 2026, 'program_studi_id' => $program->id]));
        }

        return compact('program', 'semester', 'lecturerUser', 'lecturer', 'course', 'class', 'admin', 'users', 'profiles');
    }

    public static function detail(array $data, int $student = 0, string $status = 'disetujui'): KrsDetail
    {
        $krs = Krs::create(['mahasiswa_id' => $data['profiles'][$student]->id, 'semester_id' => $data['semester']->id, 'status' => $status]);

        return KrsDetail::create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $data['class']->id]);
    }
}
