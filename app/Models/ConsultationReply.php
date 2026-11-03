<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationReply extends Model
{
    protected $fillable = ['user_id', 'subject', 'body', 'sent_at', 'error'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'consultation_request_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
