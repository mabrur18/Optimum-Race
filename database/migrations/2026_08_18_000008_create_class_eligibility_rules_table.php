<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_eligibility_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // kelas utama (kategori rider)
            $table->foreignUuid('race_class_id')->constrained('race_classes')->cascadeOnDelete();
            // kelas yang boleh diikuti sebagai kelas pilihan (termasuk dirinya sendiri)
            $table->foreignUuid('allowed_class_id')->constrained('race_classes')->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['race_class_id', 'allowed_class_id'], 'uniq_class_eligibility_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_eligibility_rules');
    }
};
