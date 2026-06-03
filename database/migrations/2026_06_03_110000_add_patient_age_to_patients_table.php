<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'patient_age')) {
                $table->unsignedSmallInteger('patient_age')->nullable()->after('patient_gender');
            }

            if (! Schema::hasColumn('patients', 'patient_age_unit')) {
                $table->string('patient_age_unit')->nullable()->default('years')->after('patient_age');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'patient_age_unit')) {
                $table->dropColumn('patient_age_unit');
            }

            if (Schema::hasColumn('patients', 'patient_age')) {
                $table->dropColumn('patient_age');
            }
        });
    }
};
