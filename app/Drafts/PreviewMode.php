<?php

namespace App\Drafts;

use Illuminate\Http\Request;

/**
 * Staff can look at the public site with every unpublished change in place. The mode lives in the session for an
 * hour and only for people allowed to edit; visitors never see drafts.
 */
final class PreviewMode
{
    private const SESSION_KEY = 'cm_preview_until';

    private const MINUTES = 60;

    public function start(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, now()->addMinutes(self::MINUTES)->getTimestamp());
    }

    public function stop(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function active(?Request $request = null): bool
    {
        $request ??= request();
        if (! $request->hasSession() || (int) $request->session()->get(self::SESSION_KEY, 0) < now()->getTimestamp()) {
            return false;
        }

        return self::allowed($request->user());
    }

    public static function allowed(mixed $user): bool
    {
        return (bool) ($user?->can('pages.edit') || $user?->can('content.edit'));
    }
}
