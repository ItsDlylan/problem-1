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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_workflow_id')->constrained()->onDelete('cascade');
            $table->foreignId('step_template_id')->constrained()->onDelete('cascade');
            $table->integer('position');
            $table->integer('duration_minutes')->nullable();
            $table->string('location_type', 50)->nullable();
            $table->boolean('requires_preparation')->default(false);
            $table->boolean('can_be_skipped')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['service_workflow_id', 'position']);
            $table->index(['service_workflow_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
