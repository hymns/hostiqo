<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Models\Deployment;
use App\Models\Webhook;
use App\Models\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $totalWebhooks = Webhook::count();
        $activeWebhooks = Webhook::active()->count();
        $totalDeployments = Deployment::count();
        $recentDeployments = Deployment::with('webhook')
            ->latest()
            ->take(10)
            ->get();

        $webhooks = Webhook::withCount('deployments')
            ->with(['latestDeployment', 'website'])
            ->latest()
            ->get();

        // Website statistics
        try {
            $totalPhpWebsites = Website::where('project_type', 'php')->count();
            $totalNodeWebsites = Website::where('project_type', 'node')->count();
        } catch (\Exception $e) {
            $totalPhpWebsites = 0;
            $totalNodeWebsites = 0;
        }

        // Database statistics
        try {
            $totalDatabases = Database::count();
        } catch (\Exception $e) {
            $totalDatabases = 0;
        }

        // Pending queue jobs
        try {
            $queueDriver = config('queue.default');
            
            if ($queueDriver === 'redis') {
                $queueName = config('queue.connections.redis.queue', 'default');
                $pendingQueues = Redis::llen('queues:' . $queueName);
            } elseif ($queueDriver === 'database') {
                $pendingQueues = DB::table('jobs')->count();
            } else {
                $pendingQueues = 0;
            }
        } catch (\Exception $e) {
            $pendingQueues = 0;
        }

        return view('dashboard', compact(
            'totalWebhooks',
            'activeWebhooks',
            'totalDeployments',
            'recentDeployments',
            'webhooks',
            'totalPhpWebsites',
            'totalNodeWebsites',
            'totalDatabases',
            'pendingQueues'
        ));
    }
}
