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
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_offering_id')->nullable()->constrained()->onDelete('cascade');
            $table->datetime('start_at');
            $table->datetime('end_at');
            $table->enum('status', ['open', 'reserved', 'booked', 'cancelled'])->default('open');
            $table->integer('capacity')->default(1);
            $table->datetime('reserved_until')->nullable();
            $table->foreignId('created_from_rule_id')->nullable()->constrained('availability_rules')->onDelete('set null');
            $table->timestamps();
            $table->index('start_at');
            $table->index(['doctor_id', 'start_at']);
            $table->index(['facility_id', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
    }
};
