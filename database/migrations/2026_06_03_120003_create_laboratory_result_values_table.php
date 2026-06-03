<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_result_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_result_id')->constrained('laboratory_results')->cascadeOnDelete();
            $table->foreignId('laboratory_test_template_field_id')
                ->nullable()
                ->constrained('laboratory_test_template_fields', 'id', 'lab_result_values_tpl_field_fk')
                ->nullOnDelete();
            $table->string('field_label');
            $table->string('field_key');
            $table->string('field_type')->default('text');
            $table->longText('field_value')->nullable();
            $table->string('unit')->nullable();
            $table->string('reference_range')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('laboratory_result_id', 'lab_result_values_result_id_idx');
            $table->index('laboratory_test_template_field_id', 'lab_result_values_tpl_field_idx');
            $table->index('field_key', 'lab_result_values_field_key_idx');
            $table->index('sort_order', 'lab_result_values_sort_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_result_values');
    }
};
