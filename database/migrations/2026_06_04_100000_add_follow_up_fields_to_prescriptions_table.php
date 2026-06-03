<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unsignedSmallInteger('next_visit_days')->nullable()->after('prescription_date');
            $table->date('next_visit_date')->nullable()->after('next_visit_days');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['next_visit_days', 'next_visit_date']);
        });
    }
};
