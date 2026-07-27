<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('inspections', function (Blueprint $table) {
        $cols = [
            'front_sight_screw','frame','magazine_catch_spring','magazine_catch',
            'slide_lock','slide_cover_plate','connector','trigger_mechanism_housing',
            'trigger','trigger_spring','trigger_with_trigger_bar','slide_stop_lever',
            'trigger_pin','trigger_housing_pin','locking_block_pin',
        ];
        foreach ($cols as $col) {
            if (!Schema::hasColumn('inspections', $col)) {
                $table->string($col)->default('serviceable')->nullable();
            }
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            //
        });
    }
};
