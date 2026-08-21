<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('full_name', 255)->nullable()->after('event_id');
            $table->string('jersey_name', 128)->nullable()->after('full_name');
            $table->string('id_number', 64)->nullable()->after('jersey_name');
            $table->string('phone', 32)->nullable()->after('id_number');
            $table->string('email', 255)->nullable()->after('phone');
            $table->string('regency', 128)->nullable()->after('email');
            $table->string('province', 128)->nullable()->after('regency');
            $table->string('team_name', 255)->nullable()->after('province');
            $table->string('start_number_main', 16)->nullable()->after('team_name');
            $table->boolean('agree_waiver')->default(false)->after('start_number_main');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('entry_list_published_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'full_name',
                'jersey_name',
                'id_number',
                'phone',
                'email',
                'regency',
                'province',
                'team_name',
                'start_number_main',
                'agree_waiver',
                'approved_at',
                'entry_list_published_at',
            ]);
        });
    }
};
