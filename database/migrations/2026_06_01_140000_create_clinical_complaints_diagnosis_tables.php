<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_masters', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_name')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('diagnosis_masters', function (Blueprint $table) {
            $table->id();
            $table->string('diagnosis_name')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('patient_visit_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->cascadeOnDelete();
            $table->foreignId('complaint_master_id')->constrained('complaint_masters')->restrictOnDelete();
            $table->string('complaint_text')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('patient_visit_id');
            $table->index('complaint_master_id');
        });

        Schema::create('patient_visit_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->cascadeOnDelete();
            $table->foreignId('diagnosis_master_id')->constrained('diagnosis_masters')->restrictOnDelete();
            $table->string('diagnosis_text')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('patient_visit_id');
            $table->index('diagnosis_master_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_visit_diagnoses');
        Schema::dropIfExists('patient_visit_complaints');
        Schema::dropIfExists('diagnosis_masters');
        Schema::dropIfExists('complaint_masters');
    }
};
