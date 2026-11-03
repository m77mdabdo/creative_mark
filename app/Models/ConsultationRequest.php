<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class ConsultationRequest extends Model
{
    use Notifiable;

    protected $fillable = [
        'reference', 'status', 'name', 'email', 'phone', 'company', 'country', 'service', 'region', 'stage',
        'meeting_mode', 'preferred_date', 'preferred_slot', 'message', 'locale', 'assigned_to', 'internal_notes',
        'consent_at', 'ip_hash', 'user_agent', 'notified_at', 'confirmed_at', 'mail_error',
    ];

    protected $attributes = ['status' => 'new'];

    protected $hidden = ['ip_hash', 'user_agent'];

    protected function casts(): array
    {
        return [
            'status' => ConsultationStatus::class, 'preferred_date' => 'date', 'consent_at' => 'datetime',
            'notified_at' => 'datetime', 'confirmed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ConsultationReply::class)->latest();
    }

    /** Bookings that still hold their time slot. */
    public function scopeHoldingSlot(Builder $query): Builder
    {
        return $query->whereIn('status', ConsultationStatus::holdingSlot());
    }

    /** Sales agents and consultants see only what is assigned to them (PermissionMatrix "own" scope). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->can('leads.assign') ? $query : $query->where('assigned_to', $user->id);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
