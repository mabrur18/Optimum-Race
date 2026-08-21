<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rider_id')->constrained('riders')->restrictOnDelete();
            $table->foreignUuid('event_id')->constrained('events')->restrictOnDelete();
            $table->enum('status', [
                'draft',
                'pending_payment',
                'payment_review',
                'rejected',
                'approved',
                'entry_list',
                'cancelled',
            ])->default('draft');
            $table->timestamps();

            $table->unique(['rider_id', 'event_id']); // 1 rider = 1 transaksi registrasi per event (bisa multi-kelas di dalamnya)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
