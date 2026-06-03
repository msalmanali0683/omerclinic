<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_medicine_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_master_id')->constrained('diagnosis_masters')->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->string('mdcn_type', 100)->nullable();
            $table->string('mdcn_name');
            $table->string('mdcn_size', 100)->nullable();
            $table->foreignId('mdcn_time_id')->nullable()->constrained('medicine_dose_times')->nullOnDelete();
            $table->foreignId('mdcn_dose_from_meal_id')->nullable()->constrained('medicine_dose_from_meals')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('diagnosis_master_id');
            $table->index('medicine_id');
            $table->index('mdcn_time_id');
            $table->index('mdcn_dose_from_meal_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_medicine_templates');
    }
};
