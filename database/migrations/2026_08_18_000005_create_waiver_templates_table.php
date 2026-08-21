<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiver_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // null organizer_id = template default platform, dipakai kalau EO belum bikin sendiri
            $table->foreignUuid('organizer_id')->nullable()->constrained('organizers')->cascadeOnDelete();
            $table->foreignUuid('discipline_id')->nullable()->constrained('disciplines')->nullOnDelete();
            $table->longText('content'); // isi pernyataan kesanggupan & klausul
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiver_templates');
    }
};
