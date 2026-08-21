<?php

namespace App\Models;

use App\Concerns\BelongsToOrganizer;
use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Event extends Model
{
    use HasUuids, BelongsToOrganizer;

    protected $fillable = [
        'organizer_id',
        'discipline_id',
        'title',
        'slug',
        'location',
        'start_date',
        'end_date',
        'eo_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => EventStatus::class,
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function raceClasses(): HasMany
    {
        return $this->hasMany(RaceClass::class)->orderBy('level_order')->orderBy('name');
    }

    public function classEligibilities(): HasMany
    {
        return $this->hasMany(ClassEligibilityRule::class, 'event_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function waiverTemplates(): HasMany
    {
        return $this->hasMany(WaiverTemplate::class);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->whereIn('status', [EventStatus::Published, EventStatus::Closed]);
    }

    public function isOpenForRegistration(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function getTotalRegistrationsCount(): int
    {
        return $this->registrations()->count();
    }

    public function buildEligibilityMatrix(): array
    {
        $raceClasses = $this->raceClasses->values();
        $ids = $raceClasses->pluck('id')->all();

        $lookup = [];
        $this
            ->classEligibilities
            ->each(function (ClassEligibilityRule $r) use (&$lookup) {
                if ($r->race_class_id && $r->allowed_class_id) {
                    $lookup[$r->race_class_id . '__' . $r->allowed_class_id] = true;
                }
            });

        $rows = [];
        foreach ($ids as $fromId) {
            $cols = [];
            foreach ($ids as $toId) {
                $key = $fromId . '__' . $toId;
                $default = ($fromId === $toId);
                $cols[$toId] = $lookup[$key] ?? $default;
            }
            $rows[$fromId] = $cols;
        }

        return [
            'race_classes' => $raceClasses->map->only(['id', 'name', 'code', 'level_order']),
            'matrix' => $rows,
        ];
    }

    public function syncEligibilityMatrix(array $pairs): void
    {
        $raceClassIds = $this->raceClasses()->pluck('id');

        $clean = [];
        $raceClassIds->each(function ($id) use (&$clean) {
            $clean[$id] = true;
        });

        $insert = [];
        $now = now();

        foreach ($pairs as $pair) {
            $from = $pair['from'] ?? null;
            $to = $pair['to'] ?? null;
            if (! $from || ! $to || ! isset($clean[$from]) || ! isset($clean[$to])) {
                continue;
            }
            $insert[] = [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'event_id' => $this->id,
                'race_class_id' => $from,
                'allowed_class_id' => $to,
                'note' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->classEligibilities()->delete();

        if (count($insert) > 0) {
            ClassEligibilityRule::insert($insert);
        }
    }

    public function isEligibleCombination(array $classIds): bool
    {
        $ids = array_values(array_unique($classIds));
        if (count($ids) <= 1) {
            return true;
        }

        $matrix = $this->buildEligibilityMatrix()['matrix'] ?? [];
        foreach ($ids as $fromId) {
            foreach ($ids as $toId) {
                $row = $matrix[$fromId] ?? [];
                if ($fromId === $toId) {
                    continue;
                }
                $allowed = $row[$toId] ?? false;
                if (! $allowed) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getFirstInvalidEligibilityPair(array $classIds): ?array
    {
        $ids = array_values(array_unique($classIds));
        if (count($ids) <= 1) {
            return null;
        }

        $raceClasses = $this->raceClasses->keyBy('id');
        $matrix = $this->buildEligibilityMatrix()['matrix'] ?? [];

        foreach ($ids as $fromId) {
            foreach ($ids as $toId) {
                if ($fromId === $toId) {
                    continue;
                }
                $row = $matrix[$fromId] ?? [];
                $allowed = $row[$toId] ?? false;
                if (! $allowed) {
                    $fromName = $raceClasses[$fromId]?->name ?? $fromId;
                    $toName = $raceClasses[$toId]?->name ?? $toId;
                    return [
                        'from_id' => $fromId,
                        'to_id' => $toId,
                        'from_name' => $fromName,
                        'to_name' => $toName,
                        'message' => "Kombinasi kelas `{$fromName}` → `{$toName}` tidak diizinkan (tidak eligible lintas kelas).",
                    ];
                }
            }
        }

        return null;
    }

    public function getEligibilityAllowedMap(): array
    {
        $matrix = $this->buildEligibilityMatrix()['matrix'] ?? [];
        $map = [];
        foreach ($matrix as $fromId => $row) {
            $allowed = [];
            foreach ($row as $toId => $ok) {
                if ($ok) {
                    $allowed[] = $toId;
                }
            }
            $map[$fromId] = $allowed;
        }

        return $map;
    }
}

