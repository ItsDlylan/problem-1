<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a meta JSON field to appointments table for storing reminder tracking and other metadata.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};

