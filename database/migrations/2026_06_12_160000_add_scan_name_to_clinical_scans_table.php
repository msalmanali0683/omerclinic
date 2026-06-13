<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_scans', function (Blueprint $table) {
            if (! Schema::hasColumn('clinical_scans', 'scan_name')) {
                $table->string('scan_name')->nullable()->after('scan_template_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinical_scans', function (Blueprint $table) {
            if (Schema::hasColumn('clinical_scans', 'scan_name')) {
                $table->dropColumn('scan_name');
            }
        });
    }
};
