<?php

use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use Carbon\CarbonImmutable;

$app = require __DIR__.'/mysql-bootstrap.php';
if (ProgramStudi::count() !== 1 || Mahasiswa::count() !== 1) {
    throw new RuntimeException('Populate only after the single-student browser journey');
}
$programNames = ['Rekayasa Perangkat Lunak', 'Cyber Security', 'Sistem Informasi', 'Teknik Elektro', 'Teknik Industri', 'Teknik Sipil', 'Arsitektur', 'Manajemen', 'Akuntansi', 'Ilmu Komunikasi', 'Matematika', 'Statistika', 'Desain Komunikasi Visual', 'Hukum', 'Psikologi'];
foreach ($programNames as $i => $name) {
    ProgramStudi::create(['kode' => ['RPL', 'CS'][$i] ?? 'PS'.str_pad($i + 1, 2, '0', STR_PAD_LEFT), 'nama' => $name, 'jenjang' => 'S1', 'aktif' => true]);
}
foreach (range(2019, 2026) as $year) {
    foreach ([1, 2] as $period) {
        Semester::firstOrCreate(['tahun_ajaran' => $year.'/'.($year + 1), 'periode' => $period], [
            'kode' => $year.'-'.($period === 1 ? 'GANJIL' : 'GENAP'), 'aktif' => false,
            'tanggal_mulai' => $period === 1 ? $year.'-08-03' : ($year + 1).'-02-02',
            'tanggal_selesai' => $period === 1 ? ($year + 1).'-01-16' : ($year + 1).'-06-26',
        ]);
    }
}
$names = ['Bima Satya Pradana', 'Citra Ayu Lestari', 'Dimas Arya Saputra', 'Elisa Putri Wardani', 'Farhan Dwi Pratama', 'Gita Maharani', 'Hana Safira', 'Ilham Fadli', 'Jihan Nur Aulia', 'Kevin Aditya', 'Laras Sekar Utami', 'Maya Kartika', 'Naufal Ramadhan', 'Olivia Kirana', 'Putra Mahendra', 'Rani Puspita', 'Satria Wirawan', 'Tania Kusuma', 'Umar Fariz', 'Vina Amalia', 'Wahyu Permana', 'Yasmin Zahra', 'Zaki Firmansyah'];
foreach ($names as $i => $name) {
    Mahasiswa::create(['nim' => '2026'.str_pad((string) (($i % 3) + 1), 2, '0', STR_PAD_LEFT).str_pad((string) ($i + 2), 4, '0', STR_PAD_LEFT), 'nama' => $name, 'email' => 'mahasiswa.'.($i + 2).'@qa.example.test', 'angkatan' => 2026, 'program_studi_id' => ($i % 3) + 1, 'status' => 'aktif']);
}
foreach (array_slice($names, 0, 16) as $i => $name) {
    Dosen::create(['user_id' => $i === 0 ? User::where('username', 'qa_lain')->value('id') : null, 'nidn' => '990000'.str_pad((string) ($i + 2), 4, '0', STR_PAD_LEFT), 'nama' => $name.', M.Kom.', 'email' => 'dosen.'.($i + 2).'@qa.example.test', 'program_studi_id' => ($i % 3) + 1, 'aktif' => true]);
}
$courses = ['Basis Data', 'Struktur Data', 'Sistem Operasi', 'Jaringan Komputer', 'Pemrograman Web', 'Analisis Algoritma', 'Interaksi Manusia dan Komputer', 'Rekayasa Kebutuhan', 'Pengujian Perangkat Lunak', 'Manajemen Proyek Perangkat Lunak', 'Kriptografi', 'Keamanan Jaringan', 'Forensik Digital', 'Audit Keamanan Informasi', 'Arsitektur Komputer', 'Komputasi Awan'];
foreach ($courses as $i => $name) {
    $course = MataKuliah::create(['program_studi_id' => ($i % 3) + 1, 'kode' => 'MK'.(201 + $i), 'nama' => $name, 'sks' => $i % 4 === 0 ? 2 : 3, 'semester_rekomendasi' => ($i % 4) + 2]);
    KelasKuliah::create(['mata_kuliah_id' => $course->id, 'semester_id' => 1, 'dosen_id' => Dosen::where('program_studi_id', $course->program_studi_id)->value('id'), 'kode_kelas' => 'A', 'kapasitas' => 30, 'ruang' => 'Ruang Kuliah '.(301 + $i)]);
}
foreach (['hadir', 'hadir', 'terlambat', 'hadir', 'izin', 'hadir', 'alpha', 'hadir'] as $i => $status) {
    Kehadiran::create(['krs_detail_id' => 1, 'dicatat_oleh_dosen_id' => 1, 'pertemuan_ke' => $i + 1, 'tanggal' => CarbonImmutable::parse('2026-08-03')->addWeeks($i)->toDateString(), 'status' => $status]);
}
// Deliberate demo sentinel: verifies relational filtering, not operational data.
$demo = ProgramStudi::create(['kode' => 'TI-DEMO', 'nama' => 'Informatika (Demo)', 'jenjang' => 'S1', 'aktif' => true]);
Mahasiswa::create(['nim' => 'DEMO-20260001', 'nama' => 'Sentinel Sintetis Terisolasi', 'angkatan' => 2026, 'program_studi_id' => $demo->id]);
echo "Realistic synthetic pagination and attendance fixtures created in QA only.\n";
