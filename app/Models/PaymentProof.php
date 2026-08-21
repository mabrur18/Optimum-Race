<?php

namespace App\Models;

use App\Enums\PaymentVerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    use HasUuids;

    protected $table = 'payment_proofs';

    protected $fillable = [
        'registration_id',
        'file_path',
        'file_name',
        'uploaded_at',
        'verified_by',
        'verified_at',
        'verification_status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_status' => PaymentVerificationStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', PaymentVerificationStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', PaymentVerificationStatus::Approved);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === PaymentVerificationStatus::Approved;
    }
}
