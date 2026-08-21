<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('class_templates')->nullOnDelete();
            $table->string('code')->unique(); // contoh: GT-BKS0826-MX1, unik lintas event
            $table->string('name');
            $table->unsignedInteger('level_order')->default(0); // untuk histori & validasi jenjang
            $table->unsignedInteger('quota')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_classes');
    }
};
