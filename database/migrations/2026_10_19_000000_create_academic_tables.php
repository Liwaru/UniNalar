<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('jenjang', 10);
            $table->string('fakultas')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nidn', 20)->nullable()->unique();
            $table->string('nama');
            $table->string('email')->nullable()->unique();
            $table->string('no_telepon', 30)->nullable();
            $table->foreignId('program_studi_id')->nullable()->constrained('program_studi')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nim', 30)->unique();
            $table->string('nama');
            $table->string('email')->nullable()->unique();
            $table->string('no_telepon', 30)->nullable();
            $table->unsignedSmallInteger('angkatan');
            $table->foreignId('program_studi_id')->constrained('program_studi')->restrictOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
            $table->index(['program_studi_id', 'angkatan']);
        });

        Schema::create('semester', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('tahun_ajaran', 9);
            $table->unsignedTinyInteger('periode');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->unique(['tahun_ajaran', 'periode']);
            $table->index(['aktif', 'tanggal_mulai']);
        });

        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_studi_id')->constrained('program_studi')->restrictOnDelete();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->unsignedTinyInteger('sks');
            $table->unsignedTinyInteger('semester_rekomendasi')->nullable();
            $table->timestamps();
            $table->index(['program_studi_id', 'semester_rekomendasi']);
        });

        Schema::create('kelas_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->restrictOnDelete();
            $table->foreignId('semester_id')->constrained('semester')->restrictOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('kode_kelas', 20);
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->string('ruang', 50)->nullable();
            $table->timestamps();
            $table->unique(['mata_kuliah_id', 'semester_id', 'kode_kelas']);
            $table->index(['semester_id', 'dosen_id']);
        });

        Schema::create('krs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->restrictOnDelete();
            $table->foreignId('semester_id')->constrained('semester')->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('diajukan_pada')->nullable();
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'semester_id']);
            $table->index(['semester_id', 'status']);
        });

        Schema::create('krs_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krs_id')->constrained('krs')->cascadeOnDelete();
            $table->foreignId('kelas_kuliah_id')->constrained('kelas_kuliah')->restrictOnDelete();
            $table->string('status', 20)->default('diambil');
            $table->timestamps();
            $table->unique(['krs_id', 'kelas_kuliah_id']);
            $table->index('kelas_kuliah_id');
        });

        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krs_detail_id')->unique()->constrained('krs_detail')->cascadeOnDelete();
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_huruf', 2)->nullable();
            $table->decimal('nilai_mutu', 4, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('kehadiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krs_detail_id')->constrained('krs_detail')->cascadeOnDelete();
            $table->foreignId('dicatat_oleh_dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->unsignedSmallInteger('pertemuan_ke');
            $table->date('tanggal');
            $table->string('status', 20);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['krs_detail_id', 'pertemuan_ke']);
            $table->index(['tanggal', 'status']);
        });

        Schema::create('analisis_risiko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->restrictOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semester')->nullOnDelete();
            $table->foreignId('dianalisis_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('skor', 7, 4);
            $table->string('kategori', 30);
            $table->string('versi_model', 50)->nullable();
            $table->json('ringkasan')->nullable();
            $table->timestamp('dianalisis_pada');
            $table->timestamps();
            $table->index(['mahasiswa_id', 'dianalisis_pada']);
            $table->index(['semester_id', 'kategori']);
        });

        Schema::create('faktor_risiko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_risiko_id')->constrained('analisis_risiko')->cascadeOnDelete();
            $table->string('kode_faktor', 50);
            $table->string('nama');
            $table->decimal('nilai_faktor', 10, 4)->nullable();
            $table->decimal('bobot', 8, 4)->nullable();
            $table->decimal('kontribusi', 10, 4)->nullable();
            $table->text('penjelasan')->nullable();
            $table->timestamps();
            $table->unique(['analisis_risiko_id', 'kode_faktor']);
        });

        Schema::create('rekomendasi_intervensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_risiko_id')->constrained('analisis_risiko')->cascadeOnDelete();
            $table->foreignId('faktor_risiko_id')->nullable()->constrained('faktor_risiko')->nullOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('prioritas', 20)->default('normal');
            $table->string('status', 20)->default('direkomendasikan');
            $table->date('tenggat_pada')->nullable();
            $table->timestamps();
            $table->index(['analisis_risiko_id', 'status']);
        });

        Schema::create('tindak_lanjut', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekomendasi_intervensi_id')->constrained('rekomendasi_intervensi')->cascadeOnDelete();
            $table->foreignId('dilakukan_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dilakukan_pada')->nullable();
            $table->string('status', 20)->default('direncanakan');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['rekomendasi_intervensi_id', 'dilakukan_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut');
        Schema::dropIfExists('rekomendasi_intervensi');
        Schema::dropIfExists('faktor_risiko');
        Schema::dropIfExists('analisis_risiko');
        Schema::dropIfExists('kehadiran');
        Schema::dropIfExists('nilai');
        Schema::dropIfExists('krs_detail');
        Schema::dropIfExists('krs');
        Schema::dropIfExists('kelas_kuliah');
        Schema::dropIfExists('mata_kuliah');
        Schema::dropIfExists('semester');
        Schema::dropIfExists('mahasiswa');
        Schema::dropIfExists('dosen');
        Schema::dropIfExists('program_studi');
    }
};
