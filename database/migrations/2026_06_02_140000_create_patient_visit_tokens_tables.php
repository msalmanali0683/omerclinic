<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_token_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('token_date')->unique();
            $table->unsignedInteger('last_token_number')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('token_date');
        });

        Schema::create('patient_visit_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->unique()->constrained('patient_visits')->cascadeOnDelete();
            $table->date('token_date');
            $table->unsignedInteger('token_number');
            $table->string('token_display')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('reprint_count')->default(0);
            $table->timestamp('last_reprinted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['token_date', 'token_number']);
            $table->index('patient_id');
            $table->index('token_date');
            $table->index('token_number');
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_visit_tokens');
        Schema::dropIfExists('daily_token_sequences');
    }
};
