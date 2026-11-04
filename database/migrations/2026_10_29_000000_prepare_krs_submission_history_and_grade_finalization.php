<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semester', function (Blueprint $table) {
            // These are local registration-window wall times (Asia/Jakarta), not semester dates.
            // Application code must compare them with a clock explicitly converted to Asia/Jakarta.
            $table->dateTime('krs_mulai_pada')->nullable()->after('tanggal_selesai');
            $table->dateTime('krs_selesai_pada')->nullable()->after('krs_mulai_pada');
        });

        Schema::table('krs', function (Blueprint $table) {
            $table->foreignId('diputuskan_oleh_user_id')
                ->nullable()
                ->after('diajukan_pada')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('diputuskan_pada')->nullable()->after('diputuskan_oleh_user_id');
            $table->text('catatan_keputusan')->nullable()->after('diputuskan_pada');
        });

        /*
         * Application status set is shared by krs and its history:
         * draft -> menunggu -> disetujui|ditolak. A history row is created only
         * when submitted, so its initial state is menunggu. A rejected KRS may
         * be edited and resubmitted as the next version; prior rows are retained.
         *
         * Version allocation, snapshot creation, and history insertion must run
         * in one transaction after SELECT ... FOR UPDATE on the parent KRS row.
         * This serializes retries before calculating MAX(versi) + 1 and keeps the
         * snapshot tied to the submitted detail rows. Snapshot JSON shape:
         * {"kelas":[{"kelas_kuliah_id":...,"mata_kuliah_id":...,
         * "kode_mata_kuliah":"...","nama_mata_kuliah":"...",
         * "kode_kelas":"...","sks":...}]}. The application must validate
         * these fields and must not update a snapshot after insertion.
         */
        Schema::create('krs_pengajuan_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krs_id')->constrained('krs')->restrictOnDelete();
            $table->unsignedSmallInteger('versi');
            $table->timestamp('diajukan_pada');
            $table->foreignId('diajukan_oleh_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('menunggu');
            $table->foreignId('diputuskan_oleh_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->text('catatan_keputusan')->nullable();
            $table->json('snapshot_kelas');
            $table->timestamps();

            $table->unique(['krs_id', 'versi']);
            $table->index(['status', 'diajukan_pada']);
        });

        Schema::table('nilai', function (Blueprint $table) {
            // Audit event instants use TIMESTAMP like diajukan_pada; app timezone is UTC.
            $table->timestamp('final_at')->nullable()->after('nilai_mutu');
            $table->foreignId('difinalkan_oleh_user_id')
                ->nullable()
                ->after('final_at')
                ->constrained('users')
                ->restrictOnDelete();
            $table->index('final_at');
        });
    }

    public function down(): void
    {
        // Destructive after use: this removes submission history and finalization audit metadata.
        // Prefer a forward migration once operational audit rows have been written.
        Schema::dropIfExists('krs_pengajuan_riwayat');

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropForeign(['difinalkan_oleh_user_id']);
            $table->dropIndex(['final_at']);
            $table->dropColumn(['final_at', 'difinalkan_oleh_user_id']);
        });

        Schema::table('krs', function (Blueprint $table) {
            $table->dropForeign(['diputuskan_oleh_user_id']);
            $table->dropColumn(['diputuskan_oleh_user_id', 'diputuskan_pada', 'catatan_keputusan']);
        });

        Schema::table('semester', function (Blueprint $table) {
            $table->dropColumn(['krs_mulai_pada', 'krs_selesai_pada']);
        });
    }
};
