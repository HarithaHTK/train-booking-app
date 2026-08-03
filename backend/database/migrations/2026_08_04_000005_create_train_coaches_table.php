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
        Schema::create('train_coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained('trains')->restrictOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['train_id', 'coach_id']);
            $table->unique(['train_id', 'position']);
            $table->index(['train_id', 'position']);
            $table->index('coach_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('train_coaches');
    }
};
