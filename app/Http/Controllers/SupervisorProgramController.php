<?php

namespace App\Http\Controllers;

use App\Models\SupervisorProgram;
use App\Services\SupervisorService;
use Illuminate\Http\Request;

class SupervisorProgramController extends Controller
{
    public function __construct(
        protected SupervisorService $supervisorService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = SupervisorProgram::latest()->get();
        
        // Get live status from supervisor
        $livePrograms = $this->supervisorService->getAllPrograms();
        
        return view('supervisor.index', compact('programs', 'livePrograms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('supervisor.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:supervisor_programs,name', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'description' => ['nullable', 'string'],
            'command' => ['required', 'string'],
            'directory' => ['required', 'string'],
            'numprocs' => ['required', 'integer', 'min:1', 'max:20'],
            'user' => ['required', 'string', 'max:255'],
            'autostart' => ['boolean'],
            'autorestart' => ['boolean'],
            'startsecs' => ['required', 'integer', 'min:0'],
            'stopwaitsecs' => ['required', 'integer', 'min:1'],
            'stdout_logfile' => ['nullable', 'string'],
            'stdout_logfile_maxbytes' => ['nullable', 'integer', 'min:0'],
            'stdout_logfile_backups' => ['nullable', 'integer', 'min:0'],
            'redirect_stderr' => ['nullable'],
            'stopasgroup' => ['nullable'],
            'killasgroup' => ['nullable'],
            'environment' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['autostart'] = $request->boolean('autostart', true);
        $validated['autorestart'] = $request->boolean('autorestart', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $validated['stdout_logfile_maxbytes'] = $request->input('stdout_logfile_maxbytes') ?? (50 * 1024 * 1024);
        $validated['stdout_logfile_backups'] = $request->input('stdout_logfile_backups') ?? 10;

        $validated['redirect_stderr'] = $request->boolean('redirect_stderr', true);
        $validated['stopasgroup'] = $request->boolean('stopasgroup', true);
        $validated['killasgroup'] = $request->boolean('killasgroup', true);

        $program = SupervisorProgram::create($validated);

        // Deploy to supervisor
        if ($program->is_active) {
            $result = $this->supervisorService->deploy($program);
            
            if (!$result['success']) {
                return redirect()
                    ->route('supervisor.index')
                    ->with('warning', "Program created but deployment failed: {$result['error']}");
            }
        }

        return redirect()
            ->route('supervisor.index')
            ->with('success', 'Supervisor program created and deployed successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(SupervisorProgram $supervisorProgram)
    {
        $status = $this->supervisorService->getProgramStatus($supervisorProgram->name);
        $logs = $this->supervisorService->getProgramLogs($supervisorProgram, 100);
        
        return view('supervisor.show', compact('supervisorProgram', 'status', 'logs'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SupervisorProgram $supervisorProgram)
    {
        return view('supervisor.edit', compact('supervisorProgram'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupervisorProgram $supervisorProgram)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:supervisor_programs,name,' . $supervisorProgram->id, 'regex:/^[a-zA-Z0-9_-]+$/'],
            'description' => ['nullable', 'string'],
            'command' => ['required', 'string'],
            'directory' => ['required', 'string'],
            'numprocs' => ['required', 'integer', 'min:1', 'max:20'],
            'user' => ['required', 'string', 'max:255'],
            'autostart' => ['boolean'],
            'autorestart' => ['boolean'],
            'startsecs' => ['required', 'integer', 'min:0'],
            'stopwaitsecs' => ['required', 'integer', 'min:1'],
            'stdout_logfile' => ['nullable', 'string'],
            'stdout_logfile_maxbytes' => ['nullable', 'integer', 'min:0'],
            'stdout_logfile_backups' => ['nullable', 'integer', 'min:0'],
            'redirect_stderr' => ['nullable'],
            'stopasgroup' => ['nullable'],
            'killasgroup' => ['nullable'],
            'environment' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['autostart'] = $request->boolean('autostart', $supervisorProgram->autostart);
        $validated['autorestart'] = $request->boolean('autorestart', $supervisorProgram->autorestart);
        $validated['is_active'] = $request->boolean('is_active', $supervisorProgram->is_active);

        $validated['stdout_logfile_maxbytes'] = $request->input('stdout_logfile_maxbytes') ?? ($supervisorProgram->stdout_logfile_maxbytes ?? (50 * 1024 * 1024));
        $validated['stdout_logfile_backups'] = $request->input('stdout_logfile_backups') ?? ($supervisorProgram->stdout_logfile_backups ?? 10);

        $validated['redirect_stderr'] = $request->boolean('redirect_stderr', true);
        $validated['stopasgroup'] = $request->boolean('stopasgroup', true);
        $validated['killasgroup'] = $request->boolean('killasgroup', true);

        $supervisorProgram->update($validated);

        // Redeploy to supervisor
        if ($supervisorProgram->is_active) {
            $result = $this->supervisorService->deploy($supervisorProgram);
            
            if (!$result['success']) {
                return redirect()
                    ->route('supervisor.index')
                    ->with('warning', "Program updated but deployment failed: {$result['error']}");
            }
        }

        return redirect()
            ->route('supervisor.index')
            ->with('success', 'Supervisor program updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupervisorProgram $supervisorProgram)
    {
        // Remove from supervisor
        $result = $this->supervisorService->remove($supervisorProgram);
        
        if (!$result['success']) {
            return redirect()
                ->route('supervisor.index')
                ->with('error', "Failed to remove program from supervisor: {$result['error']}");
        }

        $supervisorProgram->delete();

        return redirect()
            ->route('supervisor.index')
            ->with('success', 'Supervisor program removed successfully!');
    }

    /**
     * Start a supervisor program
     */
    public function start(SupervisorProgram $supervisorProgram)
    {
        $result = $this->supervisorService->startProgram($supervisorProgram->name);
        
        if ($result['success']) {
            return redirect()
                ->route('supervisor.show', $supervisorProgram)
                ->with('success', $result['message']);
        }
        
        return redirect()
            ->route('supervisor.show', $supervisorProgram)
            ->with('error', $result['message']);
    }

    /**
     * Stop a supervisor program
     */
    public function stop(SupervisorProgram $supervisorProgram)
    {
        $result = $this->supervisorService->stopProgram($supervisorProgram->name);
        
        if ($result['success']) {
            return redirect()
                ->route('supervisor.show', $supervisorProgram)
                ->with('success', $result['message']);
        }
        
        return redirect()
            ->route('supervisor.show', $supervisorProgram)
            ->with('error', $result['message']);
    }

    /**
     * Restart a supervisor program
     */
    public function restart(SupervisorProgram $supervisorProgram)
    {
        $result = $this->supervisorService->restartProgram($supervisorProgram->name);
        
        if ($result['success']) {
            return redirect()
                ->route('supervisor.show', $supervisorProgram)
                ->with('success', $result['message']);
        }
        
        return redirect()
            ->route('supervisor.show', $supervisorProgram)
            ->with('error', $result['message']);
    }

    /**
     * Redeploy supervisor configuration
     */
    public function deploy(SupervisorProgram $supervisorProgram)
    {
        $result = $this->supervisorService->deploy($supervisorProgram);
        
        if ($result['success']) {
            return redirect()
                ->route('supervisor.show', $supervisorProgram)
                ->with('success', $result['message']);
        }
        
        return redirect()
            ->route('supervisor.show', $supervisorProgram)
            ->with('error', $result['error']);
    }
}
