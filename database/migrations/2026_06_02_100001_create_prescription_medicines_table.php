<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->string('mdcn_type')->nullable();
            $table->string('mdcn_name');
            $table->string('mdcn_size')->nullable();
            $table->foreignId('mdcn_time_id')->nullable()->constrained('medicine_dose_times')->nullOnDelete();
            $table->foreignId('mdcn_dose_from_meal_id')->nullable()->constrained('medicine_dose_from_meals')->nullOnDelete();
            $table->string('dose_time_text')->nullable();
            $table->string('dose_from_meal_text')->nullable();
            $table->text('instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('prescription_id');
            $table->index('patient_id');
            $table->index('patient_visit_id');
            $table->index('medicine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_medicines');
    }
};
