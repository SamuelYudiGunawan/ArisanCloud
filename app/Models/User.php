<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the arisan groups created by this user.
     */
    public function createdGroups(): HasMany
    {
        return $this->hasMany(ArisanGroup::class, 'creator_user_id');
    }

    /**
     * Get the arisan groups this user is a member of.
     */
    public function arisanGroups(): BelongsToMany
    {
        return $this->belongsToMany(ArisanGroup::class, 'group_members', 'user_id', 'group_id')
            ->withPivot('join_date')
            ->withTimestamps();
    }

    /**
     * Get the payment history for this user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class, 'user_id');
    }

    /**
     * Get the draws won by this user.
     */
    public function wins(): HasMany
    {
        return $this->hasMany(DrawHistory::class, 'winner_user_id');
    }

    /**
     * Check if user has won in a specific group.
     */
    public function hasWonInGroup(ArisanGroup $group): bool
    {
        return $this->wins()->where('group_id', $group->id)->exists();
    }
}
