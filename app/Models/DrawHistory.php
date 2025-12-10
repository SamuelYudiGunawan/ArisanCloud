<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'draw_history';

    protected $fillable = [
        'group_id',
        'period_id',
        'winner_user_id',
        'draw_date',
        'total_pot_amount',
    ];

    protected $casts = [
        'total_pot_amount' => 'integer',
        'draw_date' => 'datetime',
    ];

    /**
     * Get the group this draw belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ArisanGroup::class, 'group_id');
    }

    /**
     * Get the period this draw belongs to.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(ArisanPeriod::class, 'period_id');
    }

    /**
     * Get the winner of this draw.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }
}

