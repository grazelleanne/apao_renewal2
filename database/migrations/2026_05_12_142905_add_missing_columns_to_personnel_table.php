<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {

            if (!Schema::hasColumn('personnel', 'unit')) {
                $table->string('unit')->nullable()->after('qty_ammo');
            }

            if (!Schema::hasColumn('personnel', 'afos_mos')) {
                $table->string('afos_mos')->nullable()->after('afp_serial_number');
            }

            if (!Schema::hasColumn('personnel', 'branch')) {
                $table->string('branch')->nullable()->after('afos_mos');
            }

            if (!Schema::hasColumn('personnel', 'email')) {
                $table->string('email')->nullable()->after('branch');
            }

            if (!Schema::hasColumn('personnel', 'status')) {
                $table->string('status')->default('active')->after('approved_status');
            }

            if (!Schema::hasColumn('personnel', 'par_number')) {
                $table->string('par_number')->nullable()->after('pistol_serial_number');
            }

            if (!Schema::hasColumn('personnel', 'last_renewed_at')) {
                $table->date('last_renewed_at')->nullable()->after('par_number');
            }
        });

        // Fix approved_status column to include all values used in the app
        // Drop and re-create with proper enum values
        DB::statement("ALTER TABLE personnel MODIFY approved_status ENUM('new','pending','within','within_renewal','renewed','expired','valid') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->dropColumn([
                'unit', 'afos_mos', 'branch', 'email',
                'status', 'par_number', 'last_renewed_at',
            ]);
        });
    }
};