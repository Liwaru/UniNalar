<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(1)->after('password');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_level_range_check CHECK (level BETWEEN 1 AND 4)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users DROP CHECK users_level_range_check');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
