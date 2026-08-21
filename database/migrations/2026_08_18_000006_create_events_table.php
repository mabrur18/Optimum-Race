<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignUuid('discipline_id')->constrained('disciplines')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique(); // dipakai untuk generate kode kelas race
            $table->string('location');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('eo_name'); // nama EO pelaksana lapangan (bisa beda dari organizer akun)
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
