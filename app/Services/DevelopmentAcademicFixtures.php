<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Explicit synthetic development data; never called by DatabaseSeeder. */
final class DevelopmentAcademicFixtures
{
    public function create(User $admin, array $owned = []): array
    {
        if (app()->environment('testing')) {
            if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
                throw new RuntimeException('Fixture testing requires SQLite memory.');
            }
        } else {
            WslDevelopmentIdentity::verify();
        }
        if ($admin->level !== User::ROLE_ADMIN_AKADEMIK) {
            throw new RuntimeException('Fixture approval requires an academic admin.');
        }
        if ($owned) {
            $student = Mahasiswa::findOrFail($owned['student']);
            $lecturer = Dosen::findOrFail($owned['lecturer']);
            if ($student->nim !== '24161033' || ! in_array($student->user?->username, ['mahasiswa', 'hendrik'], true)
                || ! in_array($lecturer->user?->username, ['dosen', 'jovian'], true)
                || ! in_array($lecturer->nidn, [null, DevelopmentDemoAccounts::SYNTHETIC_NIDN], true)
                || $student->programStudi?->kode !== 'WSL-TI') {
                throw new RuntimeException('Owned fixture changed; refusing repair/overwrite.');
            }

            // Presentation-only refinement for the explicitly owned synthetic program.
            if ($student->programStudi->nama === 'Teknik Informatika (fixture sintetis)') {
                $student->programStudi->update(['nama' => 'Teknik Informatika']);
            }

            return $owned;
        }
        // No adoption or overwrite of an unrelated existing username, NIM or code.
        if (User::whereIn('username', ['mahasiswa', 'dosen', 'hendrik', 'jovian'])->exists()
            || Mahasiswa::where('nim', '24161033')->exists()
            || ProgramStudi::where('kode', 'WSL-TI')->exists()
            || MataKuliah::where('kode', 'like', 'WSL-%')->exists()) {
            throw new RuntimeException('Fixture identity collision; review without overwriting existing accounts.');
        }
        $previousClock = Carbon::getTestNow();
        $previousImmutableClock = CarbonImmutable::getTestNow();
        try {
            return DB::transaction(function () use ($admin) {
                $active = Semester::where('aktif', true)->pluck('id')->all();
                $program = ProgramStudi::create(['kode' => 'WSL-TI', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'aktif' => true]);
                $studentUser = User::create(['name' => 'Hendrik', 'username' => 'mahasiswa', 'password' => 'mahasiswa', 'email' => 'hendrik@fixtures.invalid', 'level' => 1]);
                $teacherUser = User::create(['name' => 'Jovian', 'username' => 'dosen', 'password' => 'dosen', 'email' => 'jovian@fixtures.invalid', 'level' => 2]);
                $student = Mahasiswa::create(['user_id' => $studentUser->id, 'nim' => '24161033', 'nama' => 'Hendrik', 'angkatan' => 2024, 'program_studi_id' => $program->id]);
                $teacher = Dosen::create(['user_id' => $teacherUser->id, 'nama' => 'Jovian', 'nidn' => DevelopmentDemoAccounts::SYNTHETIC_NIDN, 'program_studi_id' => $program->id, 'aktif' => true]);
                $terms = [
                    ['2025-GANJIL', '2025/2026', 1, '2025-08-18', '2025-12-19'],
                    ['2025-GENAP', '2025/2026', 2, '2026-02-09', '2026-06-19'],
                    ['2026-GANJIL', '2026/2027', 1, '2026-08-17', '2026-12-18'],
                ];
                $names = [
                    ['Algoritma dan Pemrograman', 'Matematika Diskrit', 'Sistem Digital', 'Bahasa Indonesia', 'Proyek Pemrograman Dasar'],
                    ['Struktur Data', 'Basis Data', 'Jaringan Komputer', 'Statistika', 'Proyek Basis Data'],
                    ['Rekayasa Perangkat Lunak', 'Pemrograman Web', 'Sistem Operasi', 'Interaksi Manusia dan Komputer', 'Proyek Aplikasi Web'],
                ];
                $letters = [['B', 'BC', 'C', 'B', 'AB'], ['B', 'AB', 'B', 'A', 'AB'], ['A', 'AB', 'B', 'A', 'B']];
                $numbers = [['B' => 76, 'BC' => 68, 'C' => 62, 'AB' => 83], ['B' => 78, 'AB' => 84, 'A' => 91], ['A' => 92, 'AB' => 85, 'B' => 79]];
                $classes = $grades = $semesters = [];
                foreach ($terms as $termIndex => [$code, $year, $period, $start, $end]) {
                    Carbon::setTestNow(Carbon::parse($start.' 09:00:00', 'Asia/Jakarta')->addDays(2));
                    CarbonImmutable::setTestNow(Carbon::getTestNow());
                    $term = Semester::firstOrCreate(['tahun_ajaran' => $year, 'periode' => $period], ['kode' => $code, 'tanggal_mulai' => $start, 'tanggal_selesai' => $end, 'aktif' => false]);
                    DB::table('semester')->where('id', $term->id)->update(['tanggal_mulai' => $term->tanggal_mulai?->toDateString() ?? $start, 'tanggal_selesai' => $term->tanggal_selesai?->toDateString() ?? $end]);
                    $original = $term->only(['aktif', 'krs_mulai_pada', 'krs_selesai_pada', 'updated_at']);
                    DB::table('semester')->update(['aktif' => false]);
                    $term->forceFill(['aktif' => true, 'krs_mulai_pada' => Carbon::now()->subDay(), 'krs_selesai_pada' => Carbon::now()->addDay()])->save();
                    $termClasses = [];
                    foreach ($names[$termIndex] as $i => $name) {
                        $course = MataKuliah::create(['program_studi_id' => $program->id, 'kode' => 'WSL-'.($termIndex + 1).($i + 1), 'nama' => $name, 'sks' => $i === 4 ? 4 : 3]);
                        $class = KelasKuliah::create(['mata_kuliah_id' => $course->id, 'semester_id' => $term->id, 'dosen_id' => $teacher->id, 'kode_kelas' => 'WSL-A', 'kapasitas' => 30, 'ruang' => 'LAB-SIMULASI']);
                        $termClasses[] = $class;
                        app(KrsSubmissionService::class)->addClass($studentUser, $class->id);
                        $classes[] = $class->id;
                    }
                    $submission = app(KrsSubmissionService::class)->submit($studentUser);
                    app(KrsDecisionService::class)->decide($submission->id, $admin, 'disetujui', 'Fixture sintetis development; bukan keputusan untuk mahasiswa nyata.');
                    $krs = Krs::where('mahasiswa_id', $student->id)->where('semester_id', $term->id)->firstOrFail();
                    foreach ($termClasses as $i => $class) {
                        $detail = $krs->details()->where('kelas_kuliah_id', $class->id)->firstOrFail();
                        for ($week = 1; $week <= 8; $week++) {
                            $date = Carbon::parse($start)->addWeeks($week);
                            Kehadiran::create(['krs_detail_id' => $detail->id, 'dicatat_oleh_dosen_id' => $teacher->id,
                                'pertemuan_ke' => $week, 'tanggal' => $date->toDateString(),
                                'status' => $week === 5 && $i === 2 ? 'izin' : ($week === 7 && $i === 0 ? 'alpha' : 'hadir')]);
                        }
                        Carbon::setTestNow(Carbon::parse($start.' 10:00:00', 'Asia/Jakarta')->addWeeks(9));
                        CarbonImmutable::setTestNow(Carbon::getTestNow());
                        $letter = $letters[$termIndex][$i];
                        $grade = app(GradeEntryService::class)->save($teacherUser, $detail->id, ['nilai_angka' => $numbers[$termIndex][$letter], 'nilai_huruf' => $letter]);
                        if (! ($termIndex === 2 && $i === 4)) {
                            $grade = app(GradeFinalizationService::class)->finalize($teacherUser, $grade->id);
                        }
                        $grades[] = $grade->id;
                    }
                    DB::table('semester')->where('id', $term->id)->update($original);
                    $semesters[] = $term->id;
                }
                DB::table('semester')->update(['aktif' => false]);
                DB::table('semester')->whereIn('id', $active)->update(['aktif' => true]);

                return ['student' => $student->id, 'lecturer' => $teacher->id, 'semesters' => $semesters, 'classes' => $classes, 'grades' => $grades];
            });
        } finally {
            Carbon::setTestNow($previousClock);
            CarbonImmutable::setTestNow($previousImmutableClock);
        }
    }
}
