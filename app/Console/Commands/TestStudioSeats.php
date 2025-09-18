<?php

namespace App\Console\Commands;

use App\Models\Studio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestStudioSeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:studio-seats {studio-id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test studio seat generation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $studioId = $this->argument('studio-id');
        
        if ($studioId) {
            $studio = Studio::find($studioId);
            if (!$studio) {
                $this->error("Studio with ID {$studioId} not found");
                return self::FAILURE;
            }
            $studios = collect([$studio]);
        } else {
            $studios = Studio::all();
        }

        $this->info("Testing studio seat generation...");

        foreach ($studios as $studio) {
            $this->info("\n=== Studio: {$studio->name} (ID: {$studio->id}) ===");
            
            // Show current configuration
            $this->table(['Field', 'Value'], [
                ['ID', $studio->id],
                ['Name', $studio->name],
                ['Row', $studio->row ?? 'NULL'],
                ['Column', $studio->column ?? 'NULL'],
                ['Capacity per seat', $studio->capacity_per_seat ?? 'NULL'],
                ['Addressing', $studio->addressing ?? 'NULL'],
                ['Current seats', $studio->seats()->count()],
            ]);

            // Test if configuration is valid
            $seatCapacity = (int) $studio->capacity_per_seat;
            $rows = (int) $studio->row;
            $columns = (int) $studio->column;
            
            if ($seatCapacity <= 0 || $rows <= 0 || $columns <= 0) {
                $this->warn("❌ Invalid configuration - cannot generate seats");
                $this->warn("Required: capacity_per_seat > 0, row > 0, column > 0");
                continue;
            }

            $this->info("✅ Configuration is valid");

            // Test manual generation
            if ($this->confirm("Test manual seat generation for {$studio->name}?", true)) {
                try {
                    $beforeCount = $studio->seats()->count();
                    $this->info("Seats before: {$beforeCount}");
                    
                    $studio->generateSeats();
                    
                    $afterCount = $studio->seats()->count();
                    $this->info("Seats after: {$afterCount}");
                    
                    if ($afterCount > 0) {
                        $this->info("✅ Manual generation successful!");
                        
                        // Show some sample seats
                        $sampleSeats = $studio->seats()->limit(5)->get();
                        $this->table(['ID', 'Row', 'Column', 'Active'], 
                            $sampleSeats->map(fn($seat) => [
                                $seat->id,
                                $seat->row,
                                $seat->column,
                                $seat->is_active ? 'Yes' : 'No'
                            ])->toArray()
                        );
                    } else {
                        $this->error("❌ Manual generation failed - no seats created");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error during manual generation: " . $e->getMessage());
                }
            }

            // Test event-based generation by creating a new studio
            if ($this->confirm("Test event-based generation by creating a test studio?", false)) {
                try {
                    $testStudio = Studio::create([
                        'name' => 'Test Studio ' . now()->format('H:i:s'),
                        'location' => 'Test Location',
                        'max_capacity' => 20,
                        'studio_type' => 'multipurpose',
                        'is_active' => true,
                        'capacity_per_seat' => 10,
                        'addressing' => 'left_to_right',
                        'row' => 3,
                        'column' => 4,
                    ]);

                    $this->info("Created test studio: {$testStudio->name} (ID: {$testStudio->id})");
                    
                    // Wait a moment for events to process
                    sleep(1);
                    
                    $testStudio->refresh();
                    $seatsCount = $testStudio->seats()->count();
                    
                    if ($seatsCount > 0) {
                        $this->info("✅ Event-based generation successful! Created {$seatsCount} seats");
                    } else {
                        $this->error("❌ Event-based generation failed - no seats created");
                        $this->warn("Check logs for Studio::created event");
                    }

                    if ($this->confirm("Delete test studio?", true)) {
                        $testStudio->delete();
                        $this->info("Test studio deleted");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error creating test studio: " . $e->getMessage());
                }
            }
        }

        $this->info("\nTest completed. Check logs for detailed information.");
        return self::SUCCESS;
    }
}
