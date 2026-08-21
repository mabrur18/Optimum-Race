<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignUuid('race_class_id')->constrained('race_classes')->restrictOnDelete();
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('start_number')->nullable(); // dikunci saat status = entry_list
            $table->boolean('is_main_class')->default(false); // true = kelas utama, false = kelas pilihan tambahan
            $table->timestamps();

            $table->unique(['registration_id', 'race_class_id']);
            $table->unique(['race_class_id', 'start_number']); // nomor start unik per kelas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_items');
    }
};
