<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('personnel', 'civil_status')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->string('civil_status', 30)->nullable()->after('citizenship');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personnel', 'civil_status')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->dropColumn('civil_status');
            });
        }
    }
};
