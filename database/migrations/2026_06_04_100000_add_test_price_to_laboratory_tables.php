<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_test_templates', function (Blueprint $table) {
            $table->decimal('test_price', 10, 2)->nullable()->default(0)->after('test_code');
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->decimal('test_price', 10, 2)->nullable()->after('test_code');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_test_templates', function (Blueprint $table) {
            $table->dropColumn('test_price');
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->dropColumn('test_price');
        });
    }
};
