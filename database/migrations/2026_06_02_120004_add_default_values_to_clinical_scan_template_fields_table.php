<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_scan_template_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('clinical_scan_template_fields', 'default_values')) {
                $table->json('default_values')->nullable()->after('default_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinical_scan_template_fields', function (Blueprint $table) {
            $table->dropColumn('default_values');
        });
    }
};
