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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_no')->unique();
            $table->string('title');
            $table->text('instructions');
            $table->string('category', 50); // Must be checked in request for validation
            $table->string('originating_office', 50);
            $table->string('request_type', 50); // Must be checked in request for validation
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('statuses'); // Create a lookup table
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
