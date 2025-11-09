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
        Schema::create('call_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('call_sid')->unique();
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('set null');
            $table->string('phone_number');
            $table->enum('status', ['greeting', 'identifying', 'booking', 'confirming', 'completed', 'failed'])->default('greeting');
            $table->json('conversation_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('call_sid');
            $table->index('phone_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
    }
};
