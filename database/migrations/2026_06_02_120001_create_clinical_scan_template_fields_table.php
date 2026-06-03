<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_scan_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_scan_template_id')->constrained('clinical_scan_templates')->cascadeOnDelete();
            $table->string('field_label');
            $table->string('field_key');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->text('default_value')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['clinical_scan_template_id', 'field_key'], 'clinical_scan_template_fields_template_key_unique');
            $table->index('clinical_scan_template_id');
            $table->index('field_key');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_scan_template_fields');
    }
};
