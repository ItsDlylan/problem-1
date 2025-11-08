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
        Schema::create('service_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offering_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_plan_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('total_estimated_minutes');
            $table->boolean('active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_workflows');
    }
};
