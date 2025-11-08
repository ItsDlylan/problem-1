<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create facility_users table for facility staff authentication (doctors, receptionists, admins).
     */
    public function up(): void
    {
        Schema::create('facility_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('facility_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('role', ['admin', 'receptionist', 'doctor'])->default('receptionist');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('facility_id');
            $table->index('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_users');
    }
};
