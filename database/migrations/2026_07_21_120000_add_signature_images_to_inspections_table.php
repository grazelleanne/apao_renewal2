<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->longText('inspected_by_sig')->nullable()->after('inspected_by_position');
            $table->longText('witnessed_by_sig')->nullable()->after('witnessed_by_position');
            $table->longText('approved_by_sig')->nullable()->after('approved_by_position');
            $table->longText('noted_by_sig')->nullable()->after('noted_by_position');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['inspected_by_sig', 'witnessed_by_sig', 'approved_by_sig', 'noted_by_sig']);
        });
    }
};
