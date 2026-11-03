<?php

/*
 * Consultation booking. Times are Riyadh time; the team confirms every booking by email.
 */
return [
    'timezone' => 'Asia/Riyadh',

    // Saudi working week: Sunday (0) to Thursday (4).
    'working_days' => [0, 1, 2, 3, 4],

    'slots' => ['10:00', '11:30', '13:00', '14:30', '16:00'],

    // Bookable window: from tomorrow up to this many working days ahead.
    'days_ahead' => 15,

    'meeting_modes' => ['video', 'phone', 'office_riyadh', 'office_cairo'],

    'stages' => ['exploring', 'ready', 'operating'],

    // Anti-abuse: minimum seconds between opening the form and submitting it, and attempts per IP / email.
    'min_fill_seconds' => 4,
    'per_ip_per_hour' => 5,
    'per_email_per_day' => 3,

    // Language of the team's notification emails.
    'staff_locale' => env('CONSULTATION_STAFF_LOCALE', 'ar'),
];
