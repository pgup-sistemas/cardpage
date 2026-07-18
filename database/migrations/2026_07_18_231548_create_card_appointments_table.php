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
        Schema::create('card_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_name');
            $table->string('visitor_email');
            $table->string('visitor_phone', 20)->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['pending', 'confirmed', 'refused'])->default('pending');
            $table->string('token', 64)->unique();
            $table->text('notes')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->index(['card_schedule_id', 'appointment_date', 'appointment_time'], 'appts_schedule_date_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_appointments');
    }
};
