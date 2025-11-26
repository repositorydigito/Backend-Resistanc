<?php

namespace App\Console\Commands;

use App\Mail\RecoverPasswordCode;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {--type=recover : Type of email to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test emails to preview email designs';

    /**
     * Available email types
     *
     * @var array
     */
    protected $availableTypes = [
        'recover' => 'Recover Password Code',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'aizencode@gmail.com';
        $type = $this->option('type');

        // Show available email types
        $this->info('Available email types:');
        foreach ($this->availableTypes as $key => $description) {
            $this->line("  - {$key}: {$description}");
        }

        // If type is not valid, show error
        if (!array_key_exists($type, $this->availableTypes)) {
            $this->error("Invalid email type '{$type}'. Available types: " . implode(', ', array_keys($this->availableTypes)));
            return Command::FAILURE;
        }

        $this->info("\nSending {$this->availableTypes[$type]} email to: {$email}");

        try {
            // Get or create a test user
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->warn("User with email '{$email}' not found. Creating a test user...");
                $user = User::create([
                    'name' => 'Test User',
                    'email' => $email,
                    'password' => bcrypt('password'),
                    'code' => 'TEST' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                ]);
                $this->info("Test user created with ID: {$user->id}");
            }

            // Send the appropriate email
            switch ($type) {
                case 'recover':
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Mail::to($email)->send(new RecoverPasswordCode($code, $user));
                    $this->info("Recover password code: {$code}");
                    break;
            }

            $this->info("✅ Test email sent successfully!");
            $this->line("Check your email inbox at: {$email}");

        } catch (\Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
