<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('personnel', 'citizenship')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->string('citizenship', 100)->default('Filipino')->after('date_of_birth');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personnel', 'citizenship')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->dropColumn('citizenship');
            });
        }
    }
};
