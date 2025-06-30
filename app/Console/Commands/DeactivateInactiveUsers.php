<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DeactivateInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate users who have been inactive for 20 minutes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $deactivatedCount = User::query()
            ->where('is_active', true)
            ->where('last_activity_at', '<', now()->subMinutes(20))
            ->update(['is_active' => false]);
            
        if ($deactivatedCount > 0) {
            $this->info("Deactivated {$deactivatedCount} inactive users.");
        }
    }
}
