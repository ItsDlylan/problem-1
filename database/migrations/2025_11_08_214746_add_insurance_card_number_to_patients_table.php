<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add insurance_card_number column to patients table.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'insurance_card_number')) {
                $table->string('insurance_card_number')->nullable()->after('default_insurance_plan_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     * Remove insurance_card_number column from patients table.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'insurance_card_number')) {
                $table->dropColumn('insurance_card_number');
            }
        });
    }
};
