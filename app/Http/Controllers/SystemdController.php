<?php

namespace App\Http\Controllers;

use App\Models\SystemdService;
use App\Services\SystemdServiceManager;
use Illuminate\Http\Request;

class SystemdController extends Controller
{
    public function __construct(
        protected SystemdServiceManager $systemdManager
    ) {}

    public function index()
    {
        $services = SystemdService::latest()->get();
        
        foreach ($services as $service) {
            $status = $this->systemdManager->getServiceStatus($service);
            $service->update(['status' => $status['status'] ?? 'unknown']);
        }

        return view('systemd.index', compact('services'));
    }

    public function create()
    {
        return view('systemd.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:systemd_services,name', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'description' => ['required', 'string', 'max:255'],
            'exec_start' => ['required', 'string'],
            'working_directory' => ['required', 'string'],
            'user' => ['required', 'string'],
            'type' => ['required', 'in:simple,forking,oneshot,notify'],
            'restart' => ['required', 'in:no,always,on-failure,on-abnormal'],
            'restart_sec' => ['required', 'integer', 'min:0'],
            'environment' => ['nullable', 'string'],
            'standard_output' => ['required', 'string'],
            'standard_error' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $service = SystemdService::create($validated);

        if ($service->is_active) {
            $result = $this->systemdManager->deployService($service);
            
            if (!$result['success']) {
                return redirect()
                    ->route('systemd.index')
                    ->with('error', 'Service created but deployment failed: ' . $result['error']);
            }
        }

        return redirect()
            ->route('systemd.index')
            ->with('success', 'Systemd service created successfully!');
    }

    public function edit(SystemdService $systemd)
    {
        return view('systemd.edit', compact('systemd'));
    }

    public function update(Request $request, SystemdService $systemd)
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'exec_start' => ['required', 'string'],
            'working_directory' => ['required', 'string'],
            'user' => ['required', 'string'],
            'type' => ['required', 'in:simple,forking,oneshot,notify'],
            'restart' => ['required', 'in:no,always,on-failure,on-abnormal'],
            'restart_sec' => ['required', 'integer', 'min:0'],
            'environment' => ['nullable', 'string'],
            'standard_output' => ['required', 'string'],
            'standard_error' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $systemd->update($validated);

        if ($systemd->is_active) {
            $result = $this->systemdManager->deployService($systemd);
            
            if (!$result['success']) {
                return redirect()
                    ->route('systemd.index')
                    ->with('error', 'Service updated but deployment failed: ' . $result['error']);
            }
        }

        return redirect()
            ->route('systemd.index')
            ->with('success', 'Systemd service updated successfully!');
    }

    public function destroy(SystemdService $systemd)
    {
        $result = $this->systemdManager->undeployService($systemd);
        
        if (!$result['success']) {
            return redirect()
                ->route('systemd.index')
                ->with('error', 'Failed to undeploy service: ' . $result['error']);
        }

        $systemd->delete();

        return redirect()
            ->route('systemd.index')
            ->with('success', 'Systemd service deleted successfully!');
    }

    public function start(SystemdService $systemd)
    {
        $result = $this->systemdManager->startService($systemd);
        
        if ($result['success']) {
            return redirect()->back()->with('success', 'Service started successfully!');
        }

        return redirect()->back()->with('error', 'Failed to start service: ' . $result['error']);
    }

    public function stop(SystemdService $systemd)
    {
        $result = $this->systemdManager->stopService($systemd);
        
        if ($result['success']) {
            return redirect()->back()->with('success', 'Service stopped successfully!');
        }

        return redirect()->back()->with('error', 'Failed to stop service: ' . $result['error']);
    }

    public function restart(SystemdService $systemd)
    {
        $result = $this->systemdManager->restartService($systemd);
        
        if ($result['success']) {
            return redirect()->back()->with('success', 'Service restarted successfully!');
        }

        return redirect()->back()->with('error', 'Failed to restart service: ' . $result['error']);
    }
}
