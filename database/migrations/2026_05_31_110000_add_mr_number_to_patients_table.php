<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('mr_number')->nullable()->unique()->after('id');
            $table->index('mr_number');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['mr_number']);
            $table->dropUnique(['mr_number']);
            $table->dropColumn('mr_number');
        });
    }
};
