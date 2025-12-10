<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArisanPeriod extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'arisan_periods';

    protected $fillable = [
        'group_id',
        'period_number',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'period_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the group this period belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ArisanGroup::class, 'group_id');
    }

    /**
     * Get the payments for this period.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class, 'period_id');
    }

    /**
     * Get the draw for this period.
     */
    public function draw(): HasOne
    {
        return $this->hasOne(DrawHistory::class, 'period_id');
    }

    /**
     * Check if all members have paid for this period.
     */
    public function allMembersPaid(): bool
    {
        $memberCount = $this->group->members()->count();
        $paidCount = $this->payments()->where('status', 'approved')->count();
        return $memberCount > 0 && $memberCount === $paidCount;
    }

    /**
     * Get members who haven't paid yet.
     */
    public function unpaidMembers()
    {
        $paidUserIds = $this->payments()
            ->where('status', 'approved')
            ->pluck('user_id')
            ->toArray();

        return $this->group->members()
            ->whereNotIn('users.id', $paidUserIds)
            ->get();
    }

    /**
     * Get payment status for each member.
     */
    public function memberPaymentStatus(): array
    {
        $members = $this->group->members;
        $payments = $this->payments()->get()->keyBy('user_id');

        return $members->map(function ($member) use ($payments) {
            $payment = $payments->get($member->id);
            
            // Map status to Indonesian labels
            $statusLabel = match($payment?->status) {
                'approved' => 'Lunas',
                'pending' => 'Menunggu Verifikasi',
                'rejected' => 'Ditolak',
                default => 'Belum Bayar',
            };

            return [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'user_email' => $member->email,
                'status' => $payment?->status ?? 'not_paid',
                'status_label' => $statusLabel,
                'is_paid' => $payment?->status === 'approved',
                'payment_date' => $payment?->payment_date?->format('d/m/Y'),
                'payment_id' => $payment?->id,
                'proof_image' => $payment?->proof_image ? asset('storage/' . $payment->proof_image) : null,
            ];
        })->toArray();
    }
}

