<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groq_demo_budgets', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('lease_token', 64)->nullable();
            $table->unsignedBigInteger('lease_until')->default(0);
        });
        Schema::create('groq_demo_user_budgets', function (Blueprint $table) {
            // No cascading FK: deleting an account must not refund its attempts.
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedBigInteger('window_start')->default(0);
            $table->unsignedInteger('window_attempts')->default(0);
        });
        DB::table('groq_demo_budgets')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('groq_demo_user_budgets');
        Schema::dropIfExists('groq_demo_budgets');
    }
};
