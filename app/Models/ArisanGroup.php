<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArisanGroup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'arisan_groups';

    protected $fillable = [
        'name',
        'description',
        'creator_user_id',
        'rekening_transfer',
        'period_duration_weeks',
        'contribution_amount',
        'current_cycle',
    ];

    protected $casts = [
        'period_duration_weeks' => 'integer',
        'contribution_amount' => 'integer',
        'current_cycle' => 'integer',
    ];

    /**
     * Get the creator of the group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Get the members of the group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot('join_date')
            ->withTimestamps();
    }

    /**
     * Get the group member records.
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    /**
     * Get the periods for this group.
     */
    public function periods(): HasMany
    {
        return $this->hasMany(ArisanPeriod::class, 'group_id');
    }

    /**
     * Get the active period.
     */
    public function activePeriod()
    {
        return $this->periods()->where('status', 'active')->first();
    }

    /**
     * Get the payment history for this group.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class, 'group_id');
    }

    /**
     * Get the draw history for this group.
     */
    public function draws(): HasMany
    {
        return $this->hasMany(DrawHistory::class, 'group_id');
    }

    /**
     * Get users who have already won in the CURRENT cycle.
     */
    public function winners(): array
    {
        return $this->draws()
            ->where('cycle_number', $this->current_cycle)
            ->pluck('winner_user_id')
            ->toArray();
    }

    /**
     * Get all-time winners (for display purposes).
     */
    public function allTimeWinners(): array
    {
        return $this->draws()->pluck('winner_user_id')->toArray();
    }

    /**
     * Check if the current cycle is complete (all current members have won in this cycle).
     */
    public function isComplete(): bool
    {
        $memberCount = $this->members()->count();
        $currentCycleWinnerCount = $this->draws()
            ->where('cycle_number', $this->current_cycle)
            ->count();
        return $memberCount > 0 && $memberCount === $currentCycleWinnerCount;
    }

    /**
     * Start a new cycle (reset winners for a fresh round).
     */
    public function startNewCycle(): void
    {
        $this->increment('current_cycle');
    }

    /**
     * Get the number of winners in the current cycle.
     */
    public function currentCycleWinnerCount(): int
    {
        return $this->draws()
            ->where('cycle_number', $this->current_cycle)
            ->count();
    }

    /**
     * Check if a user is the creator of this group.
     */
    public function isCreator(User $user): bool
    {
        return $this->creator_user_id === $user->id;
    }

    /**
     * Check if a user is a member of this group.
     */
    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
}

