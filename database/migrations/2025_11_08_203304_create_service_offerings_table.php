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
        Schema::create('service_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->integer('default_duration_minutes')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['service_id', 'doctor_id', 'facility_id']);
            $table->index(['facility_id', 'doctor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_offerings');
    }
};
