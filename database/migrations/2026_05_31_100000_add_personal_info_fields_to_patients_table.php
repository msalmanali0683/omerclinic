<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('patient_name')->nullable()->after('id');
            $table->string('patient_father_name')->nullable()->after('patient_name');
            $table->string('patient_cell', 30)->nullable()->after('patient_father_name');
            $table->text('patient_address')->nullable()->after('patient_cell');
            $table->string('patient_cnic', 20)->nullable()->unique()->after('patient_address');
            $table->foreignId('created_by')->nullable()->after('limited_info')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('patient_name');
            $table->index('patient_cell');
        });

        // Backfill personal info from legacy columns where present
        if (Schema::hasColumn('patients', 'name')) {
            DB::table('patients')->whereNull('patient_name')->update([
                'patient_name' => DB::raw('name'),
            ]);
        }
        if (Schema::hasColumn('patients', 'phone')) {
            DB::table('patients')->whereNull('patient_cell')->update([
                'patient_cell' => DB::raw('phone'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropUnique(['patient_cnic']);
            $table->dropIndex(['patient_name']);
            $table->dropIndex(['patient_cell']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'patient_name',
                'patient_father_name',
                'patient_cell',
                'patient_address',
                'patient_cnic',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
