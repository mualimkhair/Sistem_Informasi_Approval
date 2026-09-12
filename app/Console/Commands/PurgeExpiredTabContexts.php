<?php

namespace App\Console\Commands;

use App\Models\TabContext;
use Illuminate\Console\Command;

class PurgeExpiredTabContexts extends Command
{
    protected $signature = 'tab-contexts:purge';
    protected $description = 'Remove expired tab contexts and unreferenced rows older than 24 hours';

    public function handle(): int
    {
        $expiredCount = TabContext::where('expires_at', '<', now())->delete();

        $orphanCount = TabContext::where('last_seen_at', '<', now()->subHours(24))
            ->where('expires_at', '>', now())
            ->delete();

        $total = $expiredCount + $orphanCount;
        $this->info("Purged {$expiredCount} expired and {$orphanCount} orphaned tab contexts ({$total} total).");

        return Command::SUCCESS;
    }
}
