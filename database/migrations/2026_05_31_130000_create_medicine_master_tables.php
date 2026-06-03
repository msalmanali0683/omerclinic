<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_dose_from_meals', function (Blueprint $table) {
            $table->id();
            $table->string('dose_from_meal')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('medicine_dose_times', function (Blueprint $table) {
            $table->id();
            $table->string('dose_time')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('mdcn_type');
            $table->string('mdcn_name');
            $table->string('mdcn_size')->nullable();
            $table->foreignId('mdcn_time_id')->nullable()->constrained('medicine_dose_times')->nullOnDelete();
            $table->foreignId('mdcn_dose_from_meal_id')->nullable()->constrained('medicine_dose_from_meals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('mdcn_type');
            $table->index('mdcn_name');
            $table->index('mdcn_time_id');
            $table->index('mdcn_dose_from_meal_id');
            $table->unique(['mdcn_type', 'mdcn_name', 'mdcn_size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('medicine_dose_times');
        Schema::dropIfExists('medicine_dose_from_meals');
    }
};
