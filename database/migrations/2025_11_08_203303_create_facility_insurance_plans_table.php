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
        Schema::create('facility_insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_plan_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->text('notes')->nullable();
            $table->date('accepted_since')->nullable();
            $table->timestamps();
            $table->unique(['facility_id', 'insurance_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_insurance_plans');
    }
};
