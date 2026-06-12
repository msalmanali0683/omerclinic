<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_scan_template_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('clinical_scan_template_fields', 'print_in_box')) {
                $table->boolean('print_in_box')->default(false)->after('is_required');
            }
        });

        Schema::table('clinical_scan_values', function (Blueprint $table) {
            if (! Schema::hasColumn('clinical_scan_values', 'print_in_box')) {
                $table->boolean('print_in_box')->default(false)->after('field_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinical_scan_template_fields', function (Blueprint $table) {
            if (Schema::hasColumn('clinical_scan_template_fields', 'print_in_box')) {
                $table->dropColumn('print_in_box');
            }
        });

        Schema::table('clinical_scan_values', function (Blueprint $table) {
            if (Schema::hasColumn('clinical_scan_values', 'print_in_box')) {
                $table->dropColumn('print_in_box');
            }
        });
    }
};
