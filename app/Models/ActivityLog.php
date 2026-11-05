<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of the activity log. Written by App\Audit\Audit; read-only everywhere else; pruned after a year. */
class ActivityLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'user_name', 'event', 'subject_type', 'subject_id', 'subject_label', 'fields', 'ip_hash', 'created_at'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subYear());
    }

    /** @return array<string, string> */
    public static function eventLabels(): array
    {
        return [
            'created' => __('Created'), 'updated' => __('Edited'), 'deleted' => __('Deleted'), 'published' => __('Published'),
            'login' => __('Signed in'), 'logout' => __('Signed out'), 'login_failed' => __('Failed sign-in'),
            'exported' => __('Exported'), 'invited' => __('Invited'), 'mfa_reset' => __('Two-step reset'), 'password_link' => __('Password link sent'),
        ];
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'Page' => __('Page'), 'Service' => __('Service'), 'Region' => __('Region'), 'Faq' => __('FAQ'), 'Stat' => __('Statistic'),
            'Testimonial' => __('Testimonial'), 'Country' => __('Country'), 'NewsArticle' => __('News article'), 'CaseStudy' => __('Case study'),
            'ConsultationRequest' => __('Consultation'), 'ConsultationReply' => __('Reply'), 'NotificationRecipient' => __('Booking email recipient'),
            'User' => __('User'), 'Setting' => __('Setting'), 'Media' => __('Image'),
        ];
    }
}
