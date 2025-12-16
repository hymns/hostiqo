<?php

namespace App\Http\Controllers;

use App\Models\CronJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class CronJobController extends Controller
{
    /**
     * Display all cron jobs
     */
    public function index()
    {
        $cronJobs = CronJob::orderBy('created_at', 'desc')->get();

        return view('cron-jobs.index', compact('cronJobs'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('cron-jobs.create');
    }

    /**
     * Store a new cron job
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'command' => 'required|string',
            'schedule' => 'required|string|max:255',
            'user' => 'required|string|max:50',
        ]);

        $cronJob = CronJob::create($validated);

        // Add to actual crontab
        $this->syncCrontab();

        return redirect()->route('cron-jobs.index')
            ->with('success', 'Cron job created successfully');
    }

    /**
     * Show edit form
     */
    public function edit(CronJob $cronJob)
    {
        return view('cron-jobs.edit', compact('cronJob'));
    }

    /**
     * Update cron job
     */
    public function update(Request $request, CronJob $cronJob)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'command' => 'required|string',
            'schedule' => 'required|string|max:255',
            'user' => 'required|string|max:50',
        ]);

        $cronJob->update($validated);

        // Sync to actual crontab
        $this->syncCrontab();

        return redirect()->route('cron-jobs.index')
            ->with('success', 'Cron job updated successfully');
    }

    /**
     * Delete cron job
     */
    public function destroy(CronJob $cronJob)
    {
        $cronJob->delete();

        // Sync to actual crontab
        $this->syncCrontab();

        return back()->with('success', 'Cron job deleted successfully');
    }

    /**
     * Toggle cron job active status
     */
    public function toggle(CronJob $cronJob)
    {
        $cronJob->update([
            'is_active' => !$cronJob->is_active
        ]);

        // Sync to actual crontab
        $this->syncCrontab();

        return back()->with('success', 'Cron job status updated');
    }

    /**
     * Sync database cron jobs to actual crontab
     */
    private function syncCrontab()
    {
        $activeCronJobs = CronJob::where('is_active', true)->get();

        $crontabContent = "# Managed by Hostiqo\n";
        $crontabContent .= "# Do not edit manually\n\n";

        foreach ($activeCronJobs as $job) {
            $crontabContent .= "# {$job->name}\n";
            $crontabContent .= "{$job->schedule} {$job->command}\n\n";
        }

        // Write to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'crontab');
        file_put_contents($tempFile, $crontabContent);

        // Install crontab for the user
        $user = $activeCronJobs->first()->user ?? 'www-data';
        Process::run("sudo crontab -u {$user} {$tempFile}");

        // Clean up
        unlink($tempFile);
    }
}
