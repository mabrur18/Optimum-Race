<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('discipline_id')->constrained('disciplines')->cascadeOnDelete();
            $table->string('name'); // MX1, Beginner, Com-A, Open, dst
            $table->string('slug');
            $table->unsignedInteger('level_order')->default(0); // dasar jenjang, bisa dioverride di race_classes
            $table->timestamps();

            $table->unique(['discipline_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_templates');
    }
};
