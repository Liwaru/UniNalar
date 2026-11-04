<?php

return [
    'navigation' => [
        \App\Models\User::ROLE_MAHASISWA => [
            ['label' => 'KRS & Mata Kuliah', 'route' => 'dashboard.mahasiswa.krs', 'active' => 'dashboard.mahasiswa.krs', 'icon' => 'book'],
            ['label' => 'Nilai Akademik', 'route' => 'dashboard.mahasiswa.nilai', 'active' => 'dashboard.mahasiswa.nilai', 'icon' => 'chart'],
            ['label' => 'Kehadiran', 'route' => 'dashboard.mahasiswa.kehadiran', 'active' => 'dashboard.mahasiswa.kehadiran', 'icon' => 'calendar'],
            ['label' => 'Analisis Risiko', 'route' => 'dashboard.mahasiswa.analisis-risiko', 'active' => 'dashboard.mahasiswa.analisis-risiko', 'icon' => 'alert'],
            ['label' => 'Rekomendasi', 'route' => 'dashboard.mahasiswa.rekomendasi', 'active' => 'dashboard.mahasiswa.rekomendasi', 'icon' => 'sparkle'],
            ['label' => 'Tindak Lanjut', 'route' => 'dashboard.mahasiswa.tindak-lanjut', 'active' => 'dashboard.mahasiswa.tindak-lanjut', 'icon' => 'message'],
            ['label' => 'Profil Saya', 'route' => 'dashboard.mahasiswa.profil', 'active' => 'dashboard.mahasiswa.profil', 'icon' => 'user'],
        ],
        \App\Models\User::ROLE_DOSEN => [
            ['label' => 'Dashboard', 'route' => 'dashboard.dosen', 'active' => 'dashboard.dosen', 'icon' => 'dashboard'],
        ],
        \App\Models\User::ROLE_ADMIN_AKADEMIK => [
            ['label' => 'Dashboard', 'route' => 'dashboard.admin-akademik', 'active' => 'dashboard.admin-akademik', 'icon' => 'dashboard'],
            ['label' => 'Program Studi', 'route' => 'dashboard.admin-akademik.program-studi.index', 'active' => 'dashboard.admin-akademik.program-studi.*', 'icon' => 'building'],
            ['label' => 'Semester', 'route' => 'dashboard.admin-akademik.semester.index', 'active' => 'dashboard.admin-akademik.semester.*', 'icon' => 'calendar'],
            ['label' => 'Mata Kuliah', 'route' => 'dashboard.admin-akademik.mata-kuliah.index', 'active' => 'dashboard.admin-akademik.mata-kuliah.*', 'icon' => 'book'],
            ['label' => 'Dosen', 'route' => 'dashboard.admin-akademik.dosen.index', 'active' => 'dashboard.admin-akademik.dosen.*', 'icon' => 'teacher'],
            ['label' => 'Mahasiswa', 'route' => 'dashboard.admin-akademik.mahasiswa.index', 'active' => 'dashboard.admin-akademik.mahasiswa.*', 'icon' => 'student'],
        ],
        \App\Models\User::ROLE_KAPRODI => [
            ['label' => 'Dashboard', 'route' => 'dashboard.owner', 'active' => 'dashboard.owner', 'icon' => 'dashboard'],
        ],
    ],
];
