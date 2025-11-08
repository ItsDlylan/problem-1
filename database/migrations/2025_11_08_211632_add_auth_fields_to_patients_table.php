<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add authentication fields to patients table and remove user_id dependency.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Drop foreign key constraint and column for user_id since Patient is now directly authenticatable
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Add authentication fields - make password nullable initially to handle existing rows
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            
            // Add unique index on email for authentication
            $table->unique('email');
        });

        // Set temporary passwords for existing patients (they'll need to reset on first login)
        // Generate a unique temporary password for each patient
        $patients = DB::table('patients')->whereNull('password')->get();
        foreach ($patients as $patient) {
            DB::table('patients')
                ->where('id', $patient->id)
                ->update([
                    'password' => Hash::make('temp-password-' . $patient->id . '-' . uniqid()),
                ]);
        }

        // Now make password NOT NULL after setting values for existing rows
        // Using raw SQL for PostgreSQL compatibility
        DB::statement('ALTER TABLE patients ALTER COLUMN password SET NOT NULL');
    }

    /**
     * Reverse the migrations.
     * Restore user_id and remove authentication fields.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Remove authentication fields
            $table->dropUnique(['email']);
            $table->dropColumn(['email_verified_at', 'remember_token', 'password']);
            
            // Restore user_id column and foreign key
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
        });
    }
};
