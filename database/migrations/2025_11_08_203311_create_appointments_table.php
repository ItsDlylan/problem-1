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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_offering_id')->constrained()->onDelete('cascade');
            $table->foreignId('availability_slot_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_workflow_id')->constrained()->onDelete('cascade');
            $table->datetime('start_at');
            $table->datetime('end_at');
            $table->enum('status', ['scheduled', 'checked_in', 'in_progress', 'completed', 'no_show', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('locale', 10)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('patient_id');
            $table->index(['doctor_id', 'start_at']);
            $table->index(['facility_id', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
