<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add facility_user_id to doctors table to link Doctor records to FacilityUser for authentication.
     */
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('facility_user_id')->nullable()->after('id')->constrained('facility_users')->onDelete('set null');
            $table->index('facility_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['facility_user_id']);
            $table->dropIndex(['facility_user_id']);
            $table->dropColumn('facility_user_id');
        });
    }
};
