<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=Dom, 1=Seg ... 6=Sáb
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_schedule_slots');
    }
};
