<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_test_templates', function (Blueprint $table) {
            $table->string('test_type', 20)->default('standard')->after('test_code');
        });

        Schema::create('laboratory_result_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('laboratory_result_value_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_result_attachments');

        Schema::table('laboratory_test_templates', function (Blueprint $table) {
            $table->dropColumn('test_type');
        });
    }
};
