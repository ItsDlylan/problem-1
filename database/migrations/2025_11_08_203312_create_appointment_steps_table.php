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
        Schema::create('appointment_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained()->onDelete('cascade');
            $table->foreignId('step_template_id')->constrained()->onDelete('cascade');
            $table->integer('position');
            $table->datetime('scheduled_start_at');
            $table->datetime('scheduled_end_at');
            $table->enum('status', ['scheduled', 'completed', 'no_show', 'cancelled', 'in_progress'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->string('location', 255)->nullable();
            $table->timestamps();
            $table->index(['appointment_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_steps');
    }
};
