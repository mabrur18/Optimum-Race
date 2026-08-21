<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Asumsi tabel `users` bawaan Laravel sudah ada (dari starter kit).
    // Migration ini menambahkan kolom untuk multi-tenant + role akses.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('organizer_id')->nullable()->after('id')
                ->constrained('organizers')->nullOnDelete();
            // superadmin: organizer_id = null (platform owner)
            // admin/staff: organizer_id wajib diisi (milik satu EO)
            // rider: bisa login juga, organizer_id tetap null (peserta lintas EO)
            $table->enum('role', ['superadmin', 'admin', 'staff', 'rider'])
                ->default('rider')->after('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizer_id');
            $table->dropColumn('role');
        });
    }
};
