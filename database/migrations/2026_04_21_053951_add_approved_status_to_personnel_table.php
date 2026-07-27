<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            if (!Schema::hasColumn('personnel', 'approved_status')) {
                $table->string('approved_status', 20)->default('pending')->after('qty_ammo');
            }
        });
    }
    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            if (Schema::hasColumn('personnel', 'approved_status')) {
                $table->dropColumn('approved_status');
            }
        });
    }
};