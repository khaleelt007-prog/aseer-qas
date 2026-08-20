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
        Schema::create('quality_evaluation_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_evaluation_id')
                  ->constrained('quality_evaluations')
                  ->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            // Add indexes for better performance
            $table->index('quality_evaluation_id');
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_evaluation_photos');
    }
};
