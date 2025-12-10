<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payment_history';

    protected $fillable = [
        'group_id',
        'user_id',
        'period_id',
        'amount_paid',
        'payment_date',
        'status',
        'proof_image',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'integer',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the group this payment belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ArisanGroup::class, 'group_id');
    }

    /**
     * Get the user who made this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the period this payment belongs to.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(ArisanPeriod::class, 'period_id');
    }

    /**
     * Check if payment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

