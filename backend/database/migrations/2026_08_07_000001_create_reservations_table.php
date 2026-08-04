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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules')->restrictOnDelete();
            $table->foreignId('start_station_id')->constrained('stations')->restrictOnDelete();
            $table->foreignId('leave_station_id')->constrained('stations')->restrictOnDelete();
            $table->foreignId('seat_id')->constrained('seats')->restrictOnDelete();
            $table->date('travel_date')->nullable()->index();
            $table->string('status')->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('schedule_id');
            $table->index('start_station_id');
            $table->index('leave_station_id');
            $table->index('seat_id');
            $table->index('status');
            $table->index(['schedule_id', 'seat_id']);
            $table->index(['schedule_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
