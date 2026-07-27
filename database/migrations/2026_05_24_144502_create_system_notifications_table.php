<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_notifications', function (Blueprint $table) {
            $table->id();

            // The staff user who gets the bell notification
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->string('title');
            $table->text('message');

            // Type controls the icon + color in the bell dropdown:
            // 'renewed'       → green
            // 'expired'       → red
            // 'within_renewal'→ yellow
            $table->enum('type', ['renewed', 'expired', 'within_renewal'])
                  ->default('renewed');

            // false = unread (shows red badge count on bell icon)
            $table->boolean('read')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_notifications');
    }
};