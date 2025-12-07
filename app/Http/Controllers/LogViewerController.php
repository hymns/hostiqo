<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    /**
     * Display log viewer
     */
    public function index(Request $request)
    {
        $logType = $request->get('type', 'laravel');
        $search = $request->get('search');

        $logs = [];
        $logFile = null;

        switch ($logType) {
            case 'laravel':
                $logFile = storage_path('logs/laravel.log');
                break;
            case 'nginx-access':
                $logFile = '/var/log/nginx/access.log';
                break;
            case 'nginx-error':
                $logFile = '/var/log/nginx/error.log';
                break;
            case 'system':
                $logFile = '/var/log/syslog';
                break;
        }

        if ($logFile && file_exists($logFile)) {
            // Read last 1000 lines
            $content = shell_exec("tail -n 1000 {$logFile}");
            
            if ($content) {
                $lines = explode("\n", $content);
                $lines = array_reverse($lines); // Latest first

                // Filter by search
                if ($search) {
                    $lines = array_filter($lines, function($line) use ($search) {
                        return stripos($line, $search) !== false;
                    });
                }

                $logs = array_slice($lines, 0, 500); // Limit to 500 lines
            }
        }

        return view('logs.index', compact('logs', 'logType', 'search'));
    }

    /**
     * Clear Laravel log
     */
    public function clear()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            File::put($logFile, '');
        }

        return back()->with('success', 'Laravel log cleared successfully');
    }
}
