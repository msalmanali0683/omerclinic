<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('laboratory_test_template_fields');

        Schema::create('laboratory_test_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_test_template_id')
                ->constrained('laboratory_test_templates', 'id', 'lab_tpl_fields_tpl_id_fk')
                ->cascadeOnDelete();
            $table->string('field_label');
            $table->string('field_key');
            $table->string('field_type')->default('text');
            $table->string('unit')->nullable();
            $table->string('reference_range')->nullable();
            $table->json('options')->nullable();
            $table->text('default_value')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['laboratory_test_template_id', 'field_key'], 'lab_tpl_fields_tpl_key_uq');
            $table->index('laboratory_test_template_id', 'lab_tpl_fields_tpl_id_idx');
            $table->index('field_key', 'lab_tpl_fields_field_key_idx');
            $table->index('sort_order', 'lab_tpl_fields_sort_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_test_template_fields');
    }
};
