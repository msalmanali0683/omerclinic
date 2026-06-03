<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no')->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_visit_id')->nullable()->constrained('patient_visits')->nullOnDelete();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('patient_visit_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->foreignId('laboratory_bill_id')->nullable()->after('patient_visit_id')
                ->constrained('laboratory_bills')->nullOnDelete();
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->dropForeign(['patient_visit_id']);
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_visit_id')->nullable()->change();
            $table->foreign('patient_visit_id')->references('id')->on('patient_visits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->dropForeign(['laboratory_bill_id']);
            $table->dropColumn('laboratory_bill_id');
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->dropForeign(['patient_visit_id']);
        });

        Schema::table('laboratory_results', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_visit_id')->nullable(false)->change();
            $table->foreign('patient_visit_id')->references('id')->on('patient_visits')->cascadeOnDelete();
        });

        Schema::dropIfExists('laboratory_bills');
    }
};
