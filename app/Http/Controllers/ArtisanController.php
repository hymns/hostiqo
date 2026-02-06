<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ArtisanController extends Controller
{
    /**
     * Display artisan commands interface
     */
    public function index(Request $request)
    {
        // Get Laravel sites from websites table
        $laravelSites = $this->getLaravelSites();
        
        // Get selected site from session or default to 'hostiqo'
        $selectedSite = $request->get('site', session('artisan_selected_site', 'hostiqo'));
        session(['artisan_selected_site' => $selectedSite]);
        
        return view('artisan.index', compact('laravelSites', 'selectedSite'));
    }

    /**
     * Get list of Laravel sites with artisan file
     */
    protected function getLaravelSites(): array
    {
        $sites = [
            'hostiqo' => [
                'name' => 'Hostiqo (This Panel)',
                'path' => base_path(),
            ]
        ];
        
        // Scan websites from database
        $websites = Website::where('project_type', 'php')
            ->where('is_active', true)
            ->get();
        
        foreach ($websites as $website) {
            $fullPath = rtrim($website->root_path, '/');
            if ($website->working_directory && $website->working_directory !== '/') {
                $fullPath .= '/' . ltrim($website->working_directory, '/');
            }
            
            // Check if artisan file exists
            if (File::exists($fullPath . '/artisan')) {
                $sites[$website->id] = [
                    'name' => $website->domain,
                    'path' => $fullPath,
                ];
            }
        }
        
        return $sites;
    }

    /**
     * Run artisan command on selected site
     */
    protected function runArtisanCommand(string $command, string $siteKey): array
    {
        $sites = $this->getLaravelSites();
        
        if (!isset($sites[$siteKey])) {
            return ['success' => false, 'output' => 'Site not found'];
        }
        
        $sitePath = $sites[$siteKey]['path'];
        
        // If it's hostiqo, use native Artisan
        if ($siteKey === 'hostiqo') {
            try {
                $exitCode = Artisan::call($command);
                $output = Artisan::output();
                return ['success' => $exitCode === 0, 'output' => $output ?: 'Command executed successfully.'];
            } catch (\Exception $e) {
                return ['success' => false, 'output' => $e->getMessage()];
            }
        }
        
        // For external sites, use Process
        try {
            $result = Process::path($sitePath)
                ->timeout(60)
                ->run("php artisan {$command}");
            
            return [
                'success' => $result->successful(),
                'output' => $result->output() ?: $result->errorOutput() ?: 'Command executed.',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    /**
     * Handle artisan command execution
     */
    public function execute(Request $request)
    {
        $command = $request->input('command');
        $site = $request->input('site', 'hostiqo');
        
        $allowedCommands = [
            'optimize', 'optimize:clear',
            'cache:clear', 'config:clear', 'config:cache',
            'route:clear', 'route:cache',
            'view:clear', 'view:cache',
        ];
        
        if (!in_array($command, $allowedCommands)) {
            return redirect()->route('artisan.index')
                ->with('error', 'Command not allowed: ' . $command);
        }
        
        $result = $this->runArtisanCommand($command, $site);
        
        $sites = $this->getLaravelSites();
        $siteName = $sites[$site]['name'] ?? 'Unknown';
        
        Log::info("Artisan {$command} executed on {$siteName}", [
            'site' => $site,
            'success' => $result['success'],
        ]);
        
        if ($result['success']) {
            return redirect()->route('artisan.index', ['site' => $site])
                ->with('success', "Command '{$command}' executed on {$siteName}")
                ->with('output', $result['output']);
        }
        
        return redirect()->route('artisan.index', ['site' => $site])
            ->with('error', "Command failed: " . $result['output']);
    }

    /**
     * Clear all caches for selected site
     */
    public function clearAll(Request $request)
    {
        $site = $request->input('site', 'hostiqo');
        $commands = ['cache:clear', 'config:clear', 'route:clear', 'view:clear'];
        
        $outputs = [];
        $allSuccess = true;
        
        foreach ($commands as $command) {
            $result = $this->runArtisanCommand($command, $site);
            $outputs[] = "{$command}: " . ($result['success'] ? 'OK' : 'FAILED');
            if (!$result['success']) $allSuccess = false;
        }
        
        $sites = $this->getLaravelSites();
        $siteName = $sites[$site]['name'] ?? 'Unknown';
        
        if ($allSuccess) {
            return redirect()->route('artisan.index', ['site' => $site])
                ->with('success', "All caches cleared on {$siteName}")
                ->with('output', implode("\n", $outputs));
        }
        
        return redirect()->route('artisan.index', ['site' => $site])
            ->with('error', "Some commands failed on {$siteName}")
            ->with('output', implode("\n", $outputs));
    }

    /**
     * Optimize for production
     */
    public function optimizeProduction(Request $request)
    {
        $site = $request->input('site', 'hostiqo');
        $commands = ['config:cache', 'route:cache', 'view:cache', 'optimize'];
        
        $outputs = [];
        $allSuccess = true;
        
        foreach ($commands as $command) {
            $result = $this->runArtisanCommand($command, $site);
            $outputs[] = "{$command}: " . ($result['success'] ? 'OK' : 'FAILED');
            if (!$result['success']) $allSuccess = false;
        }
        
        $sites = $this->getLaravelSites();
        $siteName = $sites[$site]['name'] ?? 'Unknown';
        
        if ($allSuccess) {
            return redirect()->route('artisan.index', ['site' => $site])
                ->with('success', "Production optimization complete on {$siteName}")
                ->with('output', implode("\n", $outputs));
        }
        
        return redirect()->route('artisan.index', ['site' => $site])
            ->with('error', "Some commands failed on {$siteName}")
            ->with('output', implode("\n", $outputs));
    }

}
