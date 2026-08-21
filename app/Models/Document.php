<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'registration_id',
        'type',
        'document_uuid',
        'file_path',
        'file_name',
        'uploaded_at',
        'uploaded_by',
        'version',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'version' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function getVerificationUrl(): string
    {
        return route('documents.verify', $this->document_uuid);
    }
}
