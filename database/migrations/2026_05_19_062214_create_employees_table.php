<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->unique();
            $table->string('job_title', 100);
            $table->string('country', 100);
            $table->string('department', 100);
            $table->decimal('salary', 12, 2);
            $table->date('hire_date');
            $table->timestamps();

            // Compound index for country-specific & job title aggregation
            $table->index(['country', 'job_title', 'salary'], 'idx_country_job_title_salary');
            // Index for sorting and salary band filter queries
            $table->index('salary', 'idx_salary');
            // Index for department-wise budgeting insights
            $table->index('department', 'idx_department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
