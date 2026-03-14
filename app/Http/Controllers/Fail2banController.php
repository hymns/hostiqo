<?php

namespace App\Http\Controllers;

use App\Services\Fail2banService;
use Illuminate\Http\Request;

class Fail2banController extends Controller
{
    protected Fail2banService $fail2banService;

    public function __construct(Fail2banService $fail2banService)
    {
        $this->fail2banService = $fail2banService;
    }

    /**
     * Display fail2ban dashboard
     */
    public function index()
    {
        $serviceStatus = $this->fail2banService->getServiceStatus();
        
        // Get jails status once and reuse for summary
        $jails = $this->fail2banService->getAllJailsStatus();
        
        // Build summary from already-fetched jail data
        $totalBanned = 0;
        foreach ($jails as $jail) {
            $totalBanned += $jail['currently_banned'] ?? 0;
        }
        
        $summary = [
            'running' => !empty($jails),
            'total_jails' => count($jails),
            'total_banned' => $totalBanned,
            'jails' => array_map(function($jail) {
                return [
                    'name' => $jail['name'],
                    'currently_banned' => $jail['currently_banned'] ?? 0,
                    'total_banned' => $jail['total_banned'] ?? 0,
                ];
            }, $jails),
        ];
        
        return view('fail2ban.index', compact('serviceStatus', 'summary', 'jails'));
    }

    /**
     * Show banned IPs
     */
    public function banned()
    {
        $bannedIps = $this->fail2banService->getAllBannedIps();
        $status = $this->fail2banService->getStatus();
        
        return view('fail2ban.banned', compact('bannedIps', 'status'));
    }

    /**
     * Show jail details
     */
    public function showJail(string $jail)
    {
        $jailStatus = $this->fail2banService->getJailStatus($jail);
        $whitelist = $this->fail2banService->getWhitelist($jail);
        
        return view('fail2ban.jail', compact('jailStatus', 'whitelist', 'jail'));
    }

    /**
     * Ban an IP
     */
    public function banIp(Request $request)
    {
        $request->validate([
            'jail' => 'required|string',
            'ip' => 'required|ip',
        ]);
        
        $result = $this->fail2banService->banIp($request->jail, $request->ip);
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Unban an IP
     */
    public function unbanIp(Request $request)
    {
        $request->validate([
            'jail' => 'required|string',
            'ip' => 'required|ip',
        ]);
        
        $result = $this->fail2banService->unbanIp($request->jail, $request->ip);
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Start a jail
     */
    public function startJail(Request $request)
    {
        $request->validate(['jail' => 'required|string']);
        
        $result = $this->fail2banService->startJail($request->jail);
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Stop a jail
     */
    public function stopJail(Request $request)
    {
        $request->validate(['jail' => 'required|string']);
        
        $result = $this->fail2banService->stopJail($request->jail);
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Reload fail2ban
     */
    public function reload()
    {
        $result = $this->fail2banService->reload();
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Start fail2ban service
     */
    public function startService()
    {
        $result = $this->fail2banService->startService();
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Stop fail2ban service
     */
    public function stopService()
    {
        $result = $this->fail2banService->stopService();
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * Restart fail2ban service
     */
    public function restartService()
    {
        $result = $this->fail2banService->restartService();
        
        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message']);
    }

    /**
     * View fail2ban logs
     */
    public function logs(Request $request)
    {
        $lines = $request->get('lines', 100);
        $log = $this->fail2banService->getLog($lines);
        
        return view('fail2ban.logs', compact('log', 'lines'));
    }
}
