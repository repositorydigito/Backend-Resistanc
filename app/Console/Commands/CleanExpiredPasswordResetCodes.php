<?php

namespace App\Console\Commands;

use App\Models\PasswordResetCode;
use Illuminate\Console\Command;

class CleanExpiredPasswordResetCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-reset:clean-expired {--days=1 : Number of days to keep expired codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired password reset codes from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $deletedCount = PasswordResetCode::where('expires_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$deletedCount} expired password reset codes older than {$days} days.");

        return Command::SUCCESS;
    }
}
