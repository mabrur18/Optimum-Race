<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->enum('type', ['registration_form', 'receipt']);
            $table->uuid('document_uuid')->unique(); // dipakai di QR code untuk verifikasi scrutineering
            $table->string('file_path');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
