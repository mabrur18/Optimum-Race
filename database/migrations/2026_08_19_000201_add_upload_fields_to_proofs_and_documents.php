<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_proofs', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('documents', 'uploaded_at')) {
                $table->timestamp('uploaded_at')->nullable()->after('file_name');
            }
            if (! Schema::hasColumn('documents', 'uploaded_by')) {
                $table->uuid('uploaded_by')->nullable()->after('uploaded_at');
            }
        });

        try {
            Schema::table('documents', function (Blueprint $table) {
                $hasIndex = collect(Schema::getForeignKeys('documents'))
                    ->contains(fn ($fk) => in_array('uploaded_by', $fk['columns'], true));
                if (! $hasIndex) {
                    $table->foreign('uploaded_by')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            if (Schema::hasColumn('payment_proofs', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'uploaded_by')) {
                try {
                    Schema::table('documents', function (Blueprint $t) {
                        $t->dropForeign(['uploaded_by']);
                    });
                } catch (\Throwable) {
                }
                $table->dropColumn('uploaded_by');
            }
            if (Schema::hasColumn('documents', 'uploaded_at')) {
                $table->dropColumn('uploaded_at');
            }
            if (Schema::hasColumn('documents', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};
