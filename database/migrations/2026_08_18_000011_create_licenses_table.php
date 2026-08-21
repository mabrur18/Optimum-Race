<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rider_id')->constrained('riders')->cascadeOnDelete();
            $table->enum('type', ['kta_iof', 'kis_imi', 'sim']);
            $table->string('number');
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['rider_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
