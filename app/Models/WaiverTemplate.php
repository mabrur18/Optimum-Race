<?php

namespace App\Models;

use App\Concerns\BelongsToOrganizer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiverTemplate extends Model
{
    use HasUuids, BelongsToOrganizer;

    protected $fillable = [
        'organizer_id',
        'discipline_id',
        'content',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }
}
