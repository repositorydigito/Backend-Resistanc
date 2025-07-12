<?php

namespace App\Jobs;

use App\Models\PasswordResetCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanExpiredPasswordResetCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of days to keep expired codes.
     *
     * @var int
     */
    public $days;

    /**
     * Create a new job instance.
     *
     * @param  int  $days
     * @return void
     */
    public function __construct(int $days = 1)
    {
        $this->days = $days;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffDate = now()->subDays($this->days);

        $deletedCount = PasswordResetCode::where('expires_at', '<', $cutoffDate)->delete();

        Log::info("Cleaned {$deletedCount} expired password reset codes older than {$this->days} days.");
    }
}
