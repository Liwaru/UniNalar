<?php

namespace App\Services;

use App\Models\AnalisisRisiko;
use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\KrsPengajuanRiwayat;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Explicit, one-time initializer for a NEW, dedicated synthetic school-demo database. */
final class HostingDemoProvisioner
{
    public const ACCOUNTS = [
        'mahasiswa' => ['username' => 'demo_mahasiswa', 'name' => 'Mahasiswa Simulasi', 'level' => 1],
        'dosen' => ['username' => 'demo_dosen', 'name' => 'Dosen Simulasi', 'level' => 2],
        'admin' => ['username' => 'demo_admin', 'name' => 'Admin Simulasi', 'level' => 3],
        'pimpinan' => ['username' => 'demo_pimpinan', 'name' => 'Pimpinan Simulasi', 'level' => 4],
    ];

    public function assertTarget(string $confirmedDatabase): void
    {
        if (config('hosting_demo.enabled') !== true || config('hosting_demo.mode') !== 'production-demo'
            || $confirmedDatabase === '' || $confirmedDatabase !== config('hosting_demo.database')
            || config('services.groq.live_enabled') !== false || config('services.groq.demo.enabled') !== false) {
            throw new DomainException('Provisioning demo memerlukan opt-in lengkap, konfirmasi database, dan seluruh mode Groq OFF.');
        }
        $db = DB::connection();
        if (app()->environment('testing')) {
            if ($db->getDriverName() !== 'sqlite' || $db->getDatabaseName() !== ':memory:' || $confirmedDatabase !== ':memory:') {
                throw new DomainException('QA provisioning hanya menerima SQLite in-memory; QA MySQL harus menguji policy production.');
            }
        } elseif (! app()->environment('production') || config('app.debug') !== false
            || ! str_starts_with((string) config('app.url'), 'https://')
            || ! in_array($db->getDriverName(), ['mysql', 'mariadb'], true)
            || config('database.connections.'.config('database.default').'.url')
            || config('database.connections.'.config('database.default').'.database') !== $confirmedDatabase
            || $db->selectOne('SELECT DATABASE() AS name')->name !== $confirmedDatabase) {
            throw new DomainException('Identitas database/environment demo ditolak. Development WSL tidak boleh diprovision ulang.');
        }
        foreach (['users', 'program_studi', 'dosen', 'mahasiswa', 'semester', 'mata_kuliah', 'kelas_kuliah',
            'krs', 'krs_detail', 'krs_pengajuan_riwayat', 'nilai', 'kehadiran', 'analisis_risiko',
            'faktor_risiko', 'rekomendasi_intervensi', 'tindak_lanjut', 'groq_demo_budgets', 'groq_demo_user_budgets'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new DomainException('Schema demo belum lengkap. Jalankan migration pada database baru yang telah disetujui.');
            }
        }
    }

    public function provision(string $confirmedDatabase, array $passwords): array
    {
        $this->assertTarget($confirmedDatabase);
        if (array_diff(array_keys(self::ACCOUNTS), array_keys($passwords)) || count($passwords) !== 4) {
            throw new DomainException('Empat password demo harus lengkap dan berbeda.');
        }
        foreach (self::ACCOUNTS as $role => $account) {
            if (! is_string($passwords[$role]) || strlen($passwords[$role]) < 24 || strlen($passwords[$role]) > 128) {
                throw new DomainException('Setiap password demo harus berupa string acak minimal 24 karakter.');
            }
        }

        if (count(array_unique($passwords)) !== 4) {
            throw new DomainException('Empat password demo harus berbeda.');
        }

        return DB::transaction(function () use ($passwords) {
            $budget = DB::table('groq_demo_budgets')->where('id', 1)->lockForUpdate()->first();
            if (! $budget || $budget->attempts !== 0 || $budget->lease_token !== null) {
                throw new DomainException('Budget demo tidak kosong/siap. Tidak ada data yang ditimpa.');
            }
            foreach (['users', 'program_studi', 'dosen', 'mahasiswa', 'semester', 'mata_kuliah', 'kelas_kuliah',
                'krs', 'krs_detail', 'krs_pengajuan_riwayat', 'nilai', 'kehadiran', 'analisis_risiko',
                'faktor_risiko', 'rekomendasi_intervensi', 'tindak_lanjut', 'groq_demo_user_budgets'] as $table) {
                if (DB::table($table)->exists()) {
                    throw new DomainException('Database demo harus kosong. Data existing tidak diubah atau dihapus.');
                }
            }
            $users = [];
            foreach (self::ACCOUNTS as $role => $account) {
                $users[$role] = User::create($account + ['email' => $role.'@example.test', 'password' => $passwords[$role]]);
            }
            $program = ProgramStudi::create(['kode' => 'TI-SIM', 'nama' => 'Teknik Informatika (Simulasi)', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Simulasi', 'aktif' => true]);
            $lecturer = Dosen::create(['user_id' => $users['dosen']->id, 'nidn' => 'SIM-DOSEN-001', 'nama' => $users['dosen']->name, 'email' => 'dosen@example.test', 'program_studi_id' => $program->id, 'aktif' => true]);
            $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
            $year = (int) $today->year;
            $student = Mahasiswa::create(['user_id' => $users['mahasiswa']->id, 'nim' => $year.'00001', 'nama' => $users['mahasiswa']->name,
                'email' => 'mahasiswa@example.test', 'angkatan' => $year - 1, 'program_studi_id' => $program->id, 'status' => 'aktif']);
            $old = Semester::create(['kode' => 'SIM-HIST-'.$year, 'tahun_ajaran' => ($year - 1).'/'.$year, 'periode' => 2,
                'tanggal_mulai' => $today->subMonthsNoOverflow(6), 'tanggal_selesai' => $today->subMonthNoOverflow(), 'aktif' => false]);
            $current = Semester::create(['kode' => 'SIM-AKTIF-'.$year, 'tahun_ajaran' => $year.'/'.($year + 1), 'periode' => 1,
                'tanggal_mulai' => $today, 'tanggal_selesai' => $today->addMonthsNoOverflow(5), 'aktif' => true,
                'krs_mulai_pada' => $today->subDay()->format('Y-m-d H:i:s'), 'krs_selesai_pada' => $today->addDays(14)->endOfDay()->format('Y-m-d H:i:s')]);
            $historyKrs = Krs::create(['mahasiswa_id' => $student->id, 'semester_id' => $old->id, 'status' => 'disetujui',
                'diajukan_pada' => $today->subMonthsNoOverflow(6)->utc(), 'diputuskan_pada' => $today->subMonthsNoOverflow(6)->addDay()->utc(),
                'diputuskan_oleh_user_id' => $users['admin']->id]);
            $draft = Krs::create(['mahasiswa_id' => $student->id, 'semester_id' => $current->id, 'status' => 'draft']);
            $snapshot = [];
            $subjects = ['Matematika Diskrit', 'Algoritma dan Pemrograman', 'Pengantar Teknologi Informasi', 'Basis Data', 'Pemrograman Web', 'Rekayasa Perangkat Lunak'];
            foreach ($subjects as $index => $name) {
                $course = MataKuliah::create(['program_studi_id' => $program->id, 'kode' => 'SIM-'.(101 + $index), 'nama' => $name, 'sks' => 3, 'semester_rekomendasi' => $index < 3 ? 1 : 2]);
                $class = KelasKuliah::create(['mata_kuliah_id' => $course->id, 'semester_id' => $index < 3 ? $old->id : $current->id,
                    'dosen_id' => $lecturer->id, 'kode_kelas' => 'A-SIM', 'kapasitas' => 30, 'ruang' => 'Ruang Simulasi']);
                $detail = KrsDetail::create(['krs_id' => $index < 3 ? $historyKrs->id : $draft->id, 'kelas_kuliah_id' => $class->id, 'status' => 'diambil']);
                if ($index < 3) {
                    $letter = ['AB', 'B', 'A'][$index];
                    Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => [85, 78, 92][$index], 'nilai_huruf' => $letter,
                        'nilai_mutu' => config('academic.grade_points.'.$letter), 'final_at' => $today->subMonthNoOverflow()->utc(), 'difinalkan_oleh_user_id' => $users['dosen']->id]);
                    $snapshot[] = ['kelas_kuliah_id' => $class->id, 'mata_kuliah_id' => $course->id, 'kode_mata_kuliah' => $course->kode, 'nama_mata_kuliah' => $name, 'kode_kelas' => $class->kode_kelas, 'sks' => 3];
                    foreach (range(1, 4) as $meeting) {
                        Kehadiran::create(['krs_detail_id' => $detail->id, 'dicatat_oleh_dosen_id' => $lecturer->id, 'pertemuan_ke' => $meeting,
                            'tanggal' => $today->subMonthsNoOverflow(5)->addWeeks($meeting), 'status' => $index === 1 && $meeting === 4 ? 'izin' : 'hadir']);
                    }
                }
            }
            KrsPengajuanRiwayat::create(['krs_id' => $historyKrs->id, 'versi' => 1, 'diajukan_pada' => $historyKrs->diajukan_pada,
                'diajukan_oleh_user_id' => $users['mahasiswa']->id, 'status' => 'disetujui', 'diputuskan_oleh_user_id' => $users['admin']->id,
                'diputuskan_pada' => $historyKrs->diputuskan_pada, 'snapshot_kelas' => ['kelas' => $snapshot]]);
            $analysis = AnalisisRisiko::create(['mahasiswa_id' => $student->id, 'semester_id' => $old->id, 'dianalisis_oleh_user_id' => $users['admin']->id,
                'skor' => null, 'kategori' => null, 'versi_model' => 'hosting-fixture-v1', 'dianalisis_pada' => now(),
                'ringkasan' => ['sumber' => 'hosting_demo_fixture', 'transport' => 'mock', 'penjelasan' => 'Catatan simulasi untuk demo sekolah, bukan respons Groq. Nilai dan kehadiran adalah fixture sintetis.']]);
            $analysis->rekomendasiIntervensi()->create(['judul' => 'Contoh simulasi: tinjau materi semester lalu.', 'deskripsi' => 'Fixture demonstrasi, bukan rekomendasi AI nyata.']);

            return ['user_ids' => collect($users)->map(fn ($user) => $user->id)->all(), 'student_id' => $student->id,
                'active_semester_id' => $current->id, 'historical_semester_id' => $old->id];
        });
    }
}
