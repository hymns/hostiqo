<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ArtisanController extends Controller
{
    /**
     * Display artisan commands interface
     */
    public function index()
    {
        return view('artisan.index');
    }

    /**
     * Run optimize command
     */
    public function optimize()
    {
        try {
            Artisan::call('optimize');
            $output = Artisan::output();
            
            Log::info('Artisan optimize executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Application optimized successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan optimize failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches
     */
    public function cacheClear()
    {
        try {
            Artisan::call('cache:clear');
            $output = Artisan::output();
            
            Log::info('Artisan cache:clear executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Application cache cleared successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan cache:clear failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Cache clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear config cache
     */
    public function configClear()
    {
        try {
            Artisan::call('config:clear');
            $output = Artisan::output();
            
            Log::info('Artisan config:clear executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Configuration cache cleared successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan config:clear failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Config clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Cache config
     */
    public function configCache()
    {
        try {
            Artisan::call('config:cache');
            $output = Artisan::output();
            
            Log::info('Artisan config:cache executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Configuration cached successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan config:cache failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Config cache failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear route cache
     */
    public function routeClear()
    {
        try {
            Artisan::call('route:clear');
            $output = Artisan::output();
            
            Log::info('Artisan route:clear executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Route cache cleared successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan route:clear failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Route clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Cache routes
     */
    public function routeCache()
    {
        try {
            Artisan::call('route:cache');
            $output = Artisan::output();
            
            Log::info('Artisan route:cache executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Routes cached successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan route:cache failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Route cache failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear view cache
     */
    public function viewClear()
    {
        try {
            Artisan::call('view:clear');
            $output = Artisan::output();
            
            Log::info('Artisan view:clear executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Compiled views cleared successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan view:clear failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'View clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Cache views
     */
    public function viewCache()
    {
        try {
            Artisan::call('view:cache');
            $output = Artisan::output();
            
            Log::info('Artisan view:cache executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Views cached successfully!')
                ->with('output', $output);
                
        } catch (\Exception $e) {
            Log::error('Artisan view:cache failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'View cache failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches at once
     */
    public function clearAll()
    {
        try {
            $commands = [
                'cache:clear',
                'config:clear',
                'route:clear',
                'view:clear',
            ];

            $outputs = [];
            foreach ($commands as $command) {
                Artisan::call($command);
                $outputs[] = $command . ': ' . trim(Artisan::output());
            }
            
            Log::info('Artisan clear all executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'All caches cleared successfully!')
                ->with('output', implode("\n", $outputs));
                
        } catch (\Exception $e) {
            Log::error('Artisan clear all failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Clear all failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize for production
     */
    public function optimizeProduction()
    {
        try {
            $commands = [
                'config:cache',
                'route:cache',
                'view:cache',
                'optimize',
            ];

            $outputs = [];
            foreach ($commands as $command) {
                Artisan::call($command);
                $outputs[] = $command . ': ' . trim(Artisan::output());
            }
            
            Log::info('Artisan optimize production executed');
            
            return redirect()->route('artisan.index')
                ->with('success', 'Application optimized for production!')
                ->with('output', implode("\n", $outputs));
                
        } catch (\Exception $e) {
            Log::error('Artisan optimize production failed', ['error' => $e->getMessage()]);
            return redirect()->route('artisan.index')
                ->with('error', 'Production optimization failed: ' . $e->getMessage());
        }
    }
}
