<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An email address that receives new consultation bookings (managed in the dashboard). */
class NotificationRecipient extends Model
{
    protected $fillable = ['name', 'email', 'active', 'last_notified_at'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'last_notified_at' => 'datetime'];
    }
}
