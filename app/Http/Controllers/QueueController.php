<?php

namespace App\Http\Controllers;

use App\Jobs\TestJob;
use App\Services\QueueService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {
    }

    /**
     * Display queue dashboard.
     */
    public function index()
    {
        $statistics = $this->queueService->getStatistics();
        $pendingJobs = $this->queueService->getPendingJobs(20);
        $failedJobs = $this->queueService->getFailedJobs(20);

        return view('queues.index', compact('statistics', 'pendingJobs', 'failedJobs'));
    }

    /**
     * Dispatch test jobs (for testing queue).
     */
    public function dispatchTest(Request $request)
    {
        $count = $request->input('count', 1);
        
        for ($i = 1; $i <= $count; $i++) {
            TestJob::dispatch("Test Job #{$i}");
        }

        return redirect()
            ->route('queues.index')
            ->with('success', "{$count} test job(s) dispatched to queue!");
    }

    /**
     * Show all pending jobs.
     */
    public function pending()
    {
        $jobs = $this->queueService->getPendingJobs(100);
        
        return view('queues.pending', compact('jobs'));
    }

    /**
     * Show all failed jobs.
     */
    public function failed()
    {
        $jobs = $this->queueService->getFailedJobs(100);
        
        return view('queues.failed', compact('jobs'));
    }

    /**
     * Show job details.
     */
    public function showJob(string $id)
    {
        $job = $this->queueService->getJobDetails($id);
        
        if (!$job) {
            return redirect()
                ->route('queues.index')
                ->withErrors(['error' => 'Job not found.']);
        }

        return view('queues.show-job', compact('job'));
    }

    /**
     * Show failed job details.
     */
    public function showFailedJob(string $uuid)
    {
        $job = $this->queueService->getFailedJobDetails($uuid);
        
        if (!$job) {
            return redirect()
                ->route('queues.index')
                ->withErrors(['error' => 'Failed job not found.']);
        }

        return view('queues.show-failed-job', compact('job'));
    }

    /**
     * Delete a pending job.
     */
    public function deleteJob(string $id)
    {
        if ($this->queueService->deleteJob($id)) {
            return redirect()
                ->back()
                ->with('success', 'Job deleted successfully!');
        }

        return redirect()
            ->back()
            ->withErrors(['error' => 'Failed to delete job.']);
    }

    /**
     * Delete a failed job.
     */
    public function deleteFailedJob(string $uuid)
    {
        if ($this->queueService->deleteFailedJob($uuid)) {
            return redirect()
                ->back()
                ->with('success', 'Failed job deleted successfully!');
        }

        return redirect()
            ->back()
            ->withErrors(['error' => 'Failed to delete job.']);
    }

    /**
     * Retry a failed job.
     */
    public function retryFailedJob(string $uuid)
    {
        if ($this->queueService->retryFailedJob($uuid)) {
            return redirect()
                ->back()
                ->with('success', 'Job queued for retry!');
        }

        return redirect()
            ->back()
            ->withErrors(['error' => 'Failed to retry job.']);
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAllFailed()
    {
        $count = $this->queueService->retryAllFailedJobs();

        return redirect()
            ->back()
            ->with('success', "{$count} failed jobs queued for retry!");
    }

    /**
     * Clear all failed jobs.
     */
    public function clearFailed()
    {
        $count = $this->queueService->clearFailedJobs();

        return redirect()
            ->back()
            ->with('success', "{$count} failed jobs cleared!");
    }
}
