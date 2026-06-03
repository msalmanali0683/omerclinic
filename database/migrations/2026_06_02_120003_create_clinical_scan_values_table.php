<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_scan_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_scan_id')->constrained('clinical_scans')->cascadeOnDelete();
            $table->foreignId('clinical_scan_template_field_id')->nullable()->constrained('clinical_scan_template_fields')->nullOnDelete();
            $table->string('field_label');
            $table->string('field_key');
            $table->string('field_type')->default('text');
            $table->longText('field_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('clinical_scan_id');
            $table->index('clinical_scan_template_field_id');
            $table->index('field_key');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_scan_values');
    }
};
