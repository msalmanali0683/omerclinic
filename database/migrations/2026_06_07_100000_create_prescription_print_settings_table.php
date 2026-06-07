<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_print_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_paper_key')->default('A4');
            $table->json('paper_presets');
            $table->string('letterhead_height')->default('2.45in');
            $table->unsignedTinyInteger('font_size_base')->default(12);
            $table->unsignedTinyInteger('font_size_vitals')->default(12);
            $table->unsignedTinyInteger('font_size_clinical_scans')->default(12);
            $table->unsignedTinyInteger('font_size_medicines')->default(13);
            $table->unsignedTinyInteger('font_size_medicine_dose')->default(12);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_print_settings');
    }
};
