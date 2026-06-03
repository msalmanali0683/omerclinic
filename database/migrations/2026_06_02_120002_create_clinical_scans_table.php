<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->cascadeOnDelete();
            $table->foreignId('clinical_scan_template_id')->nullable()->constrained('clinical_scan_templates')->nullOnDelete();
            $table->string('scan_template_name')->nullable();
            $table->foreignId('scan_operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scan_date');
            $table->time('scan_time')->nullable();
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->text('impression')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('patient_visit_id');
            $table->index('clinical_scan_template_id');
            $table->index('scan_operator_id');
            $table->index('scan_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_scans');
    }
};
