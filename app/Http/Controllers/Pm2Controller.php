<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\Pm2Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Pm2Controller extends Controller
{
    protected Pm2Service $pm2Service;

    public function __construct(Pm2Service $pm2Service)
    {
        $this->pm2Service = $pm2Service;
    }

    /**
     * Display PM2 process manager dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $result = $this->pm2Service->listAllApps();
        
        $apps = $result['apps'] ?? [];
        $error = $result['error'] ?? null;

        // Ensure $apps is always an array
        if (!is_array($apps)) {
            $apps = [];
        }

        // Get associated websites for each PM2 app
        $websites = Website::where('project_type', 'reverse-proxy')
            ->where('runtime', 'Node.js')
            ->get()
            ->keyBy(function ($website) {
                return str_replace('.', '-', $website->domain);
            });

        // Enrich apps with website data
        foreach ($apps as &$app) {
            $app['website'] = $websites->get($app['name']);
        }

        return view('pm2.index', [
            'apps' => $apps,
            'error' => $error,
        ]);
    }

    /**
     * Show logs for a specific PM2 application.
     *
     * @param string $appName The application name
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function logs(string $appName, Request $request)
    {
        $lines = $request->input('lines', 100);
        $result = $this->pm2Service->getLogs($appName, $lines);

        $logs = $result['logs'] ?? '';
        $error = $result['error'] ?? null;

        // Find associated website
        $website = Website::where('project_type', 'reverse-proxy')
            ->where('runtime', 'Node.js')
            ->get()
            ->first(function ($site) use ($appName) {
                return str_replace('.', '-', $site->domain) === $appName;
            });

        return view('pm2.logs', [
            'appName' => $appName,
            'logs' => $logs,
            'error' => $error,
            'lines' => $lines,
            'website' => $website,
        ]);
    }

    /**
     * Start a specific PM2 application.
     *
     * @param string $appName The application name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function start(string $appName)
    {
        // Find website by app name
        $website = Website::where('project_type', 'reverse-proxy')
            ->where('runtime', 'Node.js')
            ->get()
            ->first(function ($site) use ($appName) {
                return str_replace('.', '-', $site->domain) === $appName;
            });

        if (!$website) {
            return redirect()
                ->route('pm2.index')
                ->with('error', 'Website not found for this PM2 application');
        }

        $result = $this->pm2Service->startApp($website);

        if ($result['success']) {
            return redirect()
                ->route('pm2.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('pm2.index')
            ->with('error', $result['error']);
    }

    /**
     * Stop a specific PM2 application.
     *
     * @param string $appName The application name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stop(string $appName)
    {
        $website = Website::where('project_type', 'reverse-proxy')
            ->where('runtime', 'Node.js')
            ->get()
            ->first(function ($site) use ($appName) {
                return str_replace('.', '-', $site->domain) === $appName;
            });

        if (!$website) {
            return redirect()
                ->route('pm2.index')
                ->with('error', 'Website not found for this PM2 application');
        }

        $result = $this->pm2Service->stopApp($website);

        if ($result['success']) {
            return redirect()
                ->route('pm2.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('pm2.index')
            ->with('error', $result['error']);
    }

    /**
     * Restart a specific PM2 application.
     *
     * @param string $appName The application name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restart(string $appName)
    {
        $website = Website::where('project_type', 'reverse-proxy')
            ->where('runtime', 'Node.js')
            ->get()
            ->first(function ($site) use ($appName) {
                return str_replace('.', '-', $site->domain) === $appName;
            });

        if (!$website) {
            return redirect()
                ->route('pm2.index')
                ->with('error', 'Website not found for this PM2 application');
        }

        $result = $this->pm2Service->restartApp($website);

        if ($result['success']) {
            return redirect()
                ->route('pm2.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('pm2.index')
            ->with('error', $result['error']);
    }

    /**
     * Delete a specific PM2 application.
     *
     * @param string $appName The application name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(string $appName)
    {
        $result = $this->pm2Service->deleteApp($appName);

        if ($result['success']) {
            return redirect()
                ->route('pm2.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('pm2.index')
            ->with('error', $result['error']);
    }

}
