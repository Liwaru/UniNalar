<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_risiko', function (Blueprint $table) {
            $table->decimal('skor', 7, 4)->nullable()->change();
            $table->string('kategori', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('analisis_risiko')->whereNull('skor')->orWhereNull('kategori')->exists()) {
            throw new RuntimeException('Descriptive analyses must be reviewed before restoring mandatory classification.');
        }
        Schema::table('analisis_risiko', function (Blueprint $table) {
            $table->decimal('skor', 7, 4)->nullable(false)->change();
            $table->string('kategori', 30)->nullable(false)->change();
        });
    }
};
