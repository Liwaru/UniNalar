<?php

use App\Http\Controllers\AdminAcademicController;
use App\Http\Controllers\AdminKelasKuliahController;
use App\Http\Controllers\AdminKrsReviewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DosenDashboardController;
use App\Http\Controllers\DosenNilaiController;
use App\Http\Controllers\MahasiswaDashboardController;
use App\Http\Controllers\MahasiswaKrsController;
use App\Http\Controllers\OwnerDashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', function () {
        $user = Auth::user();
        $destination = match ((int) $user->level) {
            User::ROLE_MAHASISWA => 'dashboard.mahasiswa',
            User::ROLE_DOSEN => 'dashboard.dosen',
            User::ROLE_ADMIN_AKADEMIK => 'dashboard.admin-akademik',
            User::ROLE_KAPRODI => 'dashboard.owner',
            default => abort(403),
        };

        return redirect()->route($destination);
    })->name('dashboard');

    Route::get('/dashboard/mahasiswa', [MahasiswaDashboardController::class, 'index'])
        ->middleware('level:1')
        ->name('dashboard.mahasiswa');

    Route::middleware('level:1')->prefix('/dashboard/mahasiswa')->name('dashboard.mahasiswa.')->group(function () {
        Route::get('/krs', [MahasiswaKrsController::class, 'index'])->name('krs');
        Route::post('/krs/kelas', [MahasiswaKrsController::class, 'add'])->middleware('throttle:30,1')->name('krs.kelas.store');
        Route::delete('/krs/detail/{detail}', [MahasiswaKrsController::class, 'remove'])->name('krs.detail.destroy');
        Route::post('/krs/ajukan', [MahasiswaKrsController::class, 'submit'])->middleware('throttle:10,1')->name('krs.submit');
        Route::get('/nilai', [MahasiswaDashboardController::class, 'nilai'])->name('nilai');
        Route::get('/kehadiran', [MahasiswaDashboardController::class, 'kehadiran'])->name('kehadiran');
        Route::get('/analisis-risiko', [MahasiswaDashboardController::class, 'analisisRisiko'])->name('analisis-risiko');
        Route::get('/rekomendasi', [MahasiswaDashboardController::class, 'rekomendasi'])->name('rekomendasi');
        Route::get('/tindak-lanjut', [MahasiswaDashboardController::class, 'tindakLanjut'])->name('tindak-lanjut');
        Route::get('/profil', [MahasiswaDashboardController::class, 'profil'])->name('profil');
        Route::put('/profil/kontak', [MahasiswaDashboardController::class, 'updateProfil'])->name('profil.update');
    });

    Route::get('/dashboard/dosen', [DosenDashboardController::class, 'index'])->middleware('level:2')->name('dashboard.dosen');
    Route::middleware('level:2')->prefix('/dashboard/dosen')->name('dashboard.dosen.')->group(function () {
        Route::get('/nilai', [DosenNilaiController::class, 'index'])->name('nilai');
        Route::put('/nilai/detail/{detail}', [DosenNilaiController::class, 'save'])->whereNumber('detail')->middleware('throttle:30,1')->name('nilai.save');
        Route::post('/nilai/{nilai}/finalisasi', [DosenNilaiController::class, 'finalize'])->whereNumber('nilai')->middleware('throttle:30,1')->name('nilai.finalize');
    });

    Route::get('/dashboard/admin-akademik', fn () => view('dashboard.admin-akademik.index', [
        'role' => User::ROLE_LABELS[User::ROLE_ADMIN_AKADEMIK],
        'isDemoAccount' => str_ends_with(strtolower((string) Auth::user()->email), '@demo.uninlar.test'),
    ]))->middleware('level:3')->name('dashboard.admin-akademik');

    Route::prefix('/dashboard/admin-akademik')->name('dashboard.admin-akademik.')->middleware('level:3')->group(function () {
        Route::resource('kelas-kuliah', AdminKelasKuliahController::class)->parameters(['kelas-kuliah' => 'kelas_kuliah'])->whereNumber('kelas_kuliah');
        Route::get('/krs-pengajuan', [AdminKrsReviewController::class, 'index'])->name('krs-pengajuan.index');
        Route::post('/krs-pengajuan/{history}/{decision}', [AdminKrsReviewController::class, 'decide'])
            ->whereNumber('history')->whereIn('decision', ['disetujui', 'ditolak'])->middleware('throttle:30,1')->name('krs-pengajuan.decision');
        foreach (['program-studi', 'semester', 'mata-kuliah', 'dosen', 'mahasiswa'] as $resource) {
            Route::resource($resource, AdminAcademicController::class)
                ->parameters([$resource => 'record'])
                ->except(['show'])
                ->names($resource);
        }
    });

    Route::get('/dashboard/owner', [OwnerDashboardController::class, 'index'])->middleware('level:4')->name('dashboard.owner');

    Route::get('/area/mahasiswa', fn () => response()->json(['area' => 'mahasiswa']))
        ->middleware('level:1,3,4')->name('area.mahasiswa');
    Route::get('/area/dosen', fn () => response()->json(['area' => 'dosen']))
        ->middleware('level:2,3,4')->name('area.dosen');
    Route::get('/area/admin-akademik', fn () => response()->json(['area' => 'admin-akademik']))
        ->middleware('level:3,4')->name('area.admin-akademik');
    Route::get('/area/kaprodi', fn () => response()->json(['area' => 'kaprodi']))
        ->middleware('level:4')->name('area.kaprodi');
});
