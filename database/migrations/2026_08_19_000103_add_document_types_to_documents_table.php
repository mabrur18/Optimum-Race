<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE documents MODIFY COLUMN type ENUM(
                'registration_form',
                'receipt',
                'kis',
                'kta_iof',
                'surat_rekomendasi'
            ) NOT NULL DEFAULT 'registration_form'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE documents MODIFY COLUMN type ENUM(
                'registration_form',
                'receipt'
            ) NOT NULL DEFAULT 'registration_form'"
        );
    }
};
