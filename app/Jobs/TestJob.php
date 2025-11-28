<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TestJob implements ShouldQueue
{
    use Queueable;

    public $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message = 'Test Job')
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Simulate some work
        sleep(2);
        \Log::info('TestJob executed: ' . $this->message);
    }
}
