<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'frame', 'magazine', 'magazine_catch', 'magazine_catch_spring',
        'trigger', 'trigger_spring', 'trigger_bar', 'slide_stop_lever',
        'trigger_pin', 'trigger_mechanism_housing', 'trigger_housing_pin',
        'locking_block', 'locking_block_pin', 'slide_lock', 'slide_lock_spring',
        'connector', 'guide_rod',
    ];

    public function up(): void
    {
        foreach ($this->columns as $column) {
            if (!Schema::hasColumn('inspections', $column)) {
                Schema::table('inspections', function (Blueprint $table) use ($column) {
                    $table->string($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->columns) as $column) {
            if (Schema::hasColumn('inspections', $column)) {
                Schema::table('inspections', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
