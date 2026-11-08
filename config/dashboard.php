<?php

use App\Models\User;

return [
    'navigation' => [
        User::ROLE_MAHASISWA => [
            ['label' => 'Ringkasan Akademik', 'route' => 'dashboard.mahasiswa', 'active' => 'dashboard.mahasiswa', 'icon' => 'chart'],
            ['label' => 'KRS & Mata Kuliah', 'route' => 'dashboard.mahasiswa.krs', 'active' => 'dashboard.mahasiswa.krs', 'icon' => 'book'],
            ['label' => 'Nilai Akademik', 'route' => 'dashboard.mahasiswa.nilai', 'active' => 'dashboard.mahasiswa.nilai', 'icon' => 'chart'],
            ['label' => 'Kehadiran', 'route' => 'dashboard.mahasiswa.kehadiran', 'active' => 'dashboard.mahasiswa.kehadiran', 'icon' => 'calendar'],
            ['label' => 'Analisis Risiko', 'route' => 'dashboard.mahasiswa.analisis-risiko', 'active' => 'dashboard.mahasiswa.analisis-risiko', 'icon' => 'alert'],
            ['label' => 'Rekomendasi', 'route' => 'dashboard.mahasiswa.rekomendasi', 'active' => 'dashboard.mahasiswa.rekomendasi', 'icon' => 'sparkle'],
            ['label' => 'Tindak Lanjut', 'route' => 'dashboard.mahasiswa.tindak-lanjut', 'active' => 'dashboard.mahasiswa.tindak-lanjut', 'icon' => 'message'],
            ['label' => 'Profil Saya', 'route' => 'dashboard.mahasiswa.profil', 'active' => 'dashboard.mahasiswa.profil', 'icon' => 'user'],
        ],
        User::ROLE_DOSEN => [
            ['label' => 'Ringkasan Akademik', 'route' => 'dashboard.dosen', 'active' => 'dashboard.dosen', 'icon' => 'chart'],
            ['label' => 'Kelas Diampu', 'route' => 'dashboard.dosen.kelas.index', 'active' => 'dashboard.dosen.kelas.*', 'icon' => 'book'],
            ['label' => 'Profil Saya', 'route' => 'dashboard.dosen.profil', 'active' => 'dashboard.dosen.profil*', 'icon' => 'user'],
            ['label' => 'Pengelolaan Nilai', 'route' => 'dashboard.dosen.nilai', 'active' => 'dashboard.dosen.nilai*', 'icon' => 'chart'],
        ],
        User::ROLE_ADMIN_AKADEMIK => [
            ['label' => 'Ringkasan Akademik', 'route' => 'dashboard.admin-akademik', 'active' => 'dashboard.admin-akademik', 'icon' => 'chart'],
            ['label' => 'Program Studi', 'route' => 'dashboard.admin-akademik.program-studi.index', 'active' => 'dashboard.admin-akademik.program-studi.*', 'icon' => 'building'],
            ['label' => 'Semester', 'route' => 'dashboard.admin-akademik.semester.index', 'active' => 'dashboard.admin-akademik.semester.*', 'icon' => 'calendar'],
            ['label' => 'Mata Kuliah', 'route' => 'dashboard.admin-akademik.mata-kuliah.index', 'active' => 'dashboard.admin-akademik.mata-kuliah.*', 'icon' => 'book'],
            ['label' => 'Kelas Kuliah', 'route' => 'dashboard.admin-akademik.kelas-kuliah.index', 'active' => 'dashboard.admin-akademik.kelas-kuliah.*', 'icon' => 'book'],
            ['label' => 'Pengajuan KRS', 'route' => 'dashboard.admin-akademik.krs-pengajuan.index', 'active' => 'dashboard.admin-akademik.krs-pengajuan.*', 'icon' => 'calendar'],
            ['label' => 'Dosen', 'route' => 'dashboard.admin-akademik.dosen.index', 'active' => 'dashboard.admin-akademik.dosen.*', 'icon' => 'teacher'],
            ['label' => 'Mahasiswa', 'route' => 'dashboard.admin-akademik.mahasiswa.index', 'active' => 'dashboard.admin-akademik.mahasiswa.*', 'icon' => 'student'],
        ],
        User::ROLE_KAPRODI => [
            ['label' => 'Ringkasan Akademik', 'route' => 'dashboard.owner', 'active' => 'dashboard.owner', 'icon' => 'chart'],
            ['label' => 'Mahasiswa', 'route' => 'dashboard.owner.mahasiswa.index', 'active' => 'dashboard.owner.mahasiswa.*', 'icon' => 'student'],
            ['label' => 'Dosen', 'route' => 'dashboard.owner.dosen.index', 'active' => 'dashboard.owner.dosen.*', 'icon' => 'teacher'],
            ['label' => 'Kelas', 'route' => 'dashboard.owner.kelas.index', 'active' => 'dashboard.owner.kelas.*', 'icon' => 'book'],
            ['label' => 'KRS', 'route' => 'dashboard.owner.krs.index', 'active' => 'dashboard.owner.krs.*', 'icon' => 'calendar'],
            ['label' => 'Nilai Final', 'route' => 'dashboard.owner.nilai.index', 'active' => 'dashboard.owner.nilai.*', 'icon' => 'chart'],
            ['label' => 'Kehadiran', 'route' => 'dashboard.owner.kehadiran.index', 'active' => 'dashboard.owner.kehadiran.*', 'icon' => 'calendar'],
            ['label' => 'Analisis Risiko', 'route' => 'dashboard.owner.analisis-risiko.index', 'active' => 'dashboard.owner.analisis-risiko.*', 'icon' => 'alert'],
            ['label' => 'Rekomendasi', 'route' => 'dashboard.owner.rekomendasi.index', 'active' => 'dashboard.owner.rekomendasi.*', 'icon' => 'book'],
            ['label' => 'Tindak Lanjut', 'route' => 'dashboard.owner.tindak-lanjut.index', 'active' => 'dashboard.owner.tindak-lanjut.*', 'icon' => 'message'],
            ['label' => 'Laporan Akademik', 'route' => 'dashboard.owner.laporan.index', 'active' => 'dashboard.owner.laporan.*', 'icon' => 'chart'],
        ],
    ],
];
