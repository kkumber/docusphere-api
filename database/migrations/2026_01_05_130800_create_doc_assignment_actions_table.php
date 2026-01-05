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
        Schema::create('doc_assignment_actions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_assignment_id')->constrained()->cascadeOnDelete();
        $table->string('action', 50); // acknowledged, approved, signed, completed, returned, etc.
        $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_assignment_actions');
    }
};
