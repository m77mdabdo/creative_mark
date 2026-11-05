<?php

namespace App\Console\Commands;

use App\News\AnnouncePublishedStories;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Runs every minute from the scheduler (routes/console.php). */
#[Signature('cm:announce-stories')]
#[Description('Notify publishers about scheduled news stories that have gone live')]
class AnnounceStories extends Command
{
    public function handle(AnnouncePublishedStories $announce): int
    {
        $this->info($announce().' stories announced');

        return self::SUCCESS;
    }
}
