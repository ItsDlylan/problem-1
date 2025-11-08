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
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_provider_id')->constrained()->onDelete('cascade');
            $table->string('plan_code', 100);
            $table->string('name');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['insurance_provider_id', 'plan_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_plans');
    }
};
