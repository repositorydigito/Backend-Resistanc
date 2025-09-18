<?php

namespace App\Console\Commands;

use App\Models\Studio;
use Illuminate\Console\Command;

class TestSeatAddressing extends Command
{
    protected $signature = 'test:seat-addressing {studio_id}';
    protected $description = 'Test seat addressing patterns for a studio';

    public function handle()
    {
        $studioId = $this->argument('studio_id');
        $studio = Studio::find($studioId);

        if (!$studio) {
            $this->error("Studio with ID {$studioId} not found.");
            return 1;
        }

        $this->info("Testing seat addressing for Studio: {$studio->name}");
        $this->info("Columns: {$studio->column}");
        $this->info("Addressing: {$studio->addressing}");
        $this->line('');

        $preview = $studio->getAddressingPreview();

        $this->table(
            ['Order', 'Position', 'Description'],
            array_map(fn($item) => [$item['order'], $item['position'], $item['label']], $preview)
        );

        // Test all three addressing modes
        $this->line('');
        $this->info('Testing all addressing modes:');
        
        foreach (['left_to_right', 'right_to_left', 'center'] as $addressing) {
            $this->line('');
            $this->info("=== {$addressing} ===");
            
            $tempStudio = clone $studio;
            $tempStudio->addressing = $addressing;
            $tempPreview = $tempStudio->getAddressingPreview();
            
            $positions = array_map(fn($item) => $item['position'], $tempPreview);
            $this->line('Positions: ' . implode(', ', $positions));
        }

        return 0;
    }
}
