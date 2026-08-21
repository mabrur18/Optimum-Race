<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('class_eligibility_rules', 'event_id')) {
                $table->foreignUuid('event_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('events')
                    ->cascadeOnDelete();
            } else {
                $foreignName = 'class_eligibility_rules_event_id_foreign';
                $existingForeigns = DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'class_eligibility_rules'
                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                       AND CONSTRAINT_NAME = ?",
                    [$foreignName]
                );
                if (count($existingForeigns) === 0) {
                    try {
                        $table->foreign('event_id')
                            ->references('id')
                            ->on('events')
                            ->cascadeOnDelete();
                    } catch (\Throwable $e) {
                    }
                }
            }
        });

        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            $newIndexName = 'uniq_event_class_eligibility_pair';
            $existingNew = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'class_eligibility_rules'
                   AND INDEX_NAME = ?
                 LIMIT 1",
                [$newIndexName]
            );

            if (count($existingNew) === 0) {
                try {
                    $table->unique(
                        ['event_id', 'race_class_id', 'allowed_class_id'],
                        $newIndexName
                    );
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            $idxRace = 'class_eligibility_rules_race_class_id_index';
            $idxAllowed = 'class_eligibility_rules_allowed_class_id_index';
            foreach ([$idxRace => 'race_class_id', $idxAllowed => 'allowed_class_id'] as $idxName => $colName) {
                $exists = DB::select(
                    "SELECT INDEX_NAME FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'class_eligibility_rules'
                       AND INDEX_NAME = ?
                     LIMIT 1",
                    [$idxName]
                );
                if (count($exists) === 0) {
                    try {
                        $table->index($colName, $idxName);
                    } catch (\Throwable $e) {
                    }
                }
            }
        });

        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            $oldIndexName = 'uniq_class_eligibility_pair';
            $existingOld = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'class_eligibility_rules'
                   AND INDEX_NAME = ?
                 LIMIT 1",
                [$oldIndexName]
            );

            if (count($existingOld) > 0) {
                try {
                    DB::statement("ALTER TABLE `class_eligibility_rules` DROP INDEX `{$oldIndexName}`");
                } catch (\Throwable $e) {
                    try {
                        $table->dropUnique($oldIndexName);
                    } catch (\Throwable $e2) {
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            $oldIndexName = 'uniq_class_eligibility_pair';
            $existingOld = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'class_eligibility_rules'
                   AND INDEX_NAME = ?
                 LIMIT 1",
                [$oldIndexName]
            );
            if (count($existingOld) === 0) {
                try {
                    $table->unique(['race_class_id', 'allowed_class_id'], $oldIndexName);
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            $newIndexName = 'uniq_event_class_eligibility_pair';
            $existingNew = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'class_eligibility_rules'
                   AND INDEX_NAME = ?
                 LIMIT 1",
                [$newIndexName]
            );
            if (count($existingNew) > 0) {
                try {
                    $table->dropUnique($newIndexName);
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            foreach (['class_eligibility_rules_race_class_id_index', 'class_eligibility_rules_allowed_class_id_index'] as $idxName) {
                $exists = DB::select(
                    "SELECT INDEX_NAME FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'class_eligibility_rules'
                       AND INDEX_NAME = ?
                     LIMIT 1",
                    [$idxName]
                );
                if (count($exists) > 0) {
                    try {
                        $table->dropIndex($idxName);
                    } catch (\Throwable $e) {
                    }
                }
            }
        });

        Schema::table('class_eligibility_rules', function (Blueprint $table) {
            if (Schema::hasColumn('class_eligibility_rules', 'event_id')) {
                try {
                    $table->dropConstrainedForeignId('event_id');
                } catch (\Throwable $e) {
                    try { $table->dropForeign(['event_id']); } catch (\Throwable $e2) {}
                    try { $table->dropColumn('event_id'); } catch (\Throwable $e3) {}
                }
            }
        });
    }
};
