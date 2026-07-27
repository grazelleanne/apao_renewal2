<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_history', function (Blueprint $table) {
            $table->id();
            $table->integer('item_number');
            $table->string('action')->default('renewed');
            $table->date('date_of_validity')->nullable();
            $table->date('previous_validity')->nullable();
            $table->string('inspected_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_history');
    }
};