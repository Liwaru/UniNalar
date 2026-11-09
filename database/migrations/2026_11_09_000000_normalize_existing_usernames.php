<?php

use App\Services\UsernameNormalization;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        UsernameNormalization::apply();
    }

    public function down(): void
    {
        // Canonical usernames cannot recover prior casing. No accounts are deleted.
    }
};
