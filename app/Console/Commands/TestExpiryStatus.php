<?php

namespace App\Console\Commands;

use App\Models\UserPackage;
use Illuminate\Console\Command;

class TestExpiryStatus extends Command
{
    protected $signature = 'test:expiry-status';
    protected $description = 'Test the expiry status logic for UserPackage';

    public function handle()
    {
        $this->info('Testing UserPackage expiry status...');

        $packages = UserPackage::with('package')->take(10)->get();

        if ($packages->isEmpty()) {
            $this->warn('No packages found in database');
            return;
        }

        $this->table(
            ['Package Code', 'Expiry Date', 'Is Past', 'Days Remaining', 'Expiry Status'],
            $packages->map(function ($package) {
                return [
                    $package->package_code ?? 'N/A',
                    $package->expiry_date ? $package->expiry_date->toDateString() : 'N/A',
                    $package->expiry_date ? ($package->expiry_date->isPast() ? 'Yes' : 'No') : 'N/A',
                    $package->days_remaining,
                    $package->expiry_status,
                ];
            })->toArray()
        );
    }
}
