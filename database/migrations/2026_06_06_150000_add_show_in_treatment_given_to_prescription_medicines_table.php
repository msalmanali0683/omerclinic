<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_medicines', function (Blueprint $table) {
            $table->boolean('show_in_treatment_given')->default(false)->after('dose_from_meal_text');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_medicines', function (Blueprint $table) {
            $table->dropColumn('show_in_treatment_given');
        });
    }
};
