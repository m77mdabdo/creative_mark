<?php

namespace Database\Seeders;

use App\Models\NotificationRecipient;
use Illuminate\Database\Seeder;

/**
 * First recipient of booking emails: the company mailbox (MAIL_FROM_ADDRESS). Runs only on an empty list,
 * so addresses managed in the dashboard are never overwritten.
 */
class NotificationRecipientSeeder extends Seeder
{
    public function run(): void
    {
        $address = config('mail.from.address');
        if (NotificationRecipient::query()->exists() || ! filter_var($address, FILTER_VALIDATE_EMAIL) || str_ends_with($address, '@example.com')) {
            return;
        }
        NotificationRecipient::create(['name' => 'Creative Mark', 'email' => $address, 'active' => true]);
    }
}
