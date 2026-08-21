<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // akun login rider, opsional
            $table->string('full_name'); // sesuai KTP/ID
            $table->string('jersey_name')->nullable();
            $table->string('id_number')->unique(); // NIK/KTP — kunci identitas lintas event
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('regency'); // kabupaten
            $table->string('province');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
