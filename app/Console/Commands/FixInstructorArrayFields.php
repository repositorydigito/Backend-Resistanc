<?php

namespace App\Console\Commands;

use App\Models\Instructor;
use Illuminate\Console\Command;

class FixInstructorArrayFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:instructor-arrays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix instructor specialties and certifications fields to ensure they are proper JSON arrays';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix instructor array fields...');

        $instructors = Instructor::all();
        $fixed = 0;

        foreach ($instructors as $instructor) {
            $needsUpdate = false;
            $updates = [];

            // Fix specialties
            $specialties = $instructor->getRawOriginal('specialties');
            if (!is_null($specialties)) {
                if (is_string($specialties)) {
                    $decoded = json_decode($specialties, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                        // Not valid JSON, convert to array
                        $updates['specialties'] = json_encode([$specialties]);
                        $needsUpdate = true;
                    }
                }
            } else {
                $updates['specialties'] = json_encode([]);
                $needsUpdate = true;
            }

            // Fix certifications
            $certifications = $instructor->getRawOriginal('certifications');
            if (!is_null($certifications)) {
                if (is_string($certifications)) {
                    $decoded = json_decode($certifications, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                        // Not valid JSON, convert to array
                        $updates['certifications'] = json_encode([$certifications]);
                        $needsUpdate = true;
                    }
                }
            } else {
                $updates['certifications'] = json_encode([]);
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $instructor->updateQuietly($updates);
                $fixed++;
                $this->line("Fixed instructor ID {$instructor->id}: {$instructor->name}");
            }
        }

        $this->info("Fixed {$fixed} instructor records.");
        $this->info('Done!');

        return 0;
    }
}
