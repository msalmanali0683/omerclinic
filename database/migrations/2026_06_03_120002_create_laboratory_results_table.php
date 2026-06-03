<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->cascadeOnDelete();
            $table->foreignId('laboratory_test_template_id')->nullable()->constrained('laboratory_test_templates')->nullOnDelete();
            $table->string('test_name')->nullable();
            $table->string('test_code')->nullable();
            $table->foreignId('lab_operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('result_date');
            $table->time('result_time')->nullable();
            $table->string('status')->default('completed');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id', 'lab_results_patient_id_idx');
            $table->index('patient_visit_id', 'lab_results_visit_id_idx');
            $table->index('laboratory_test_template_id', 'lab_results_tpl_id_idx');
            $table->index('lab_operator_id', 'lab_results_operator_id_idx');
            $table->index('result_date', 'lab_results_result_date_idx');
            $table->index('status', 'lab_results_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_results');
    }
};
