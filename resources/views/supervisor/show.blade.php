@extends('layouts.app')

@section('title', $supervisorProgram->name . ' - Hostiqo')
@section('page-title', $supervisorProgram->name)
@section('page-description', $supervisorProgram->description ?? 'Supervisor Program Details')

@section('page-actions')
    <a href="{{ route('supervisor.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Program Details -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i> Program Details
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Status:</strong></div>
                    <div class="col-md-9">
                        @if($supervisorProgram->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Command:</strong></div>
                    <div class="col-md-9"><code>{{ $supervisorProgram->command }}</code></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Working Directory:</strong></div>
                    <div class="col-md-9"><code>{{ $supervisorProgram->directory }}</code></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Number of Processes:</strong></div>
                    <div class="col-md-9">{{ $supervisorProgram->numprocs }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Run as User:</strong></div>
                    <div class="col-md-9"><code>{{ $supervisorProgram->user }}</code></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Auto Start:</strong></div>
                    <div class="col-md-9">
                        @if($supervisorProgram->autostart)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Auto Restart:</strong></div>
                    <div class="col-md-9">
                        @if($supervisorProgram->autorestart)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3"><strong>Log File:</strong></div>
                    <div class="col-md-9"><code>{{ $supervisorProgram->getLogFilePath() }}</code></div>
                </div>

                <div class="row">
                    <div class="col-md-3"><strong>Created:</strong></div>
                    <div class="col-md-9">{{ $supervisorProgram->created_at->format('d M Y, h:i A') }}</div>
                </div>
            </div>
        </div>

        <!-- Process Status -->
        @if($status['success'] && !empty($status['processes']))
            <div class="card mt-4">
                <div class="card-header">
                    <i class="bi bi-activity me-2"></i> Process Status
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Process Name</th>
                                    <th>Status</th>
                                    <th>Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($status['processes'] as $process)
                                    <tr>
                                        <td><code class="small">{{ $process['name'] }}</code></td>
                                        <td>
                                            @if($process['status'] === 'RUNNING')
                                                <span class="badge bg-success">{{ $process['status'] }}</span>
                                            @elseif($process['status'] === 'STOPPED')
                                                <span class="badge bg-secondary">{{ $process['status'] }}</span>
                                            @elseif($process['status'] === 'FATAL')
                                                <span class="badge bg-danger">{{ $process['status'] }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ $process['status'] }}</span>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $process['info'] }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Logs -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-file-text me-2"></i> Recent Logs (Last 100 lines)
            </div>
            <div class="card-body">
                @if($logs)
                    <pre class="bg-dark text-light p-3 rounded" style="max-height: 500px; overflow-y: auto; font-size: 0.875rem;"><code>{{ $logs }}</code></pre>
                @else
                    <p class="text-muted">No logs available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column - Actions -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear me-2"></i> Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('supervisor.start', $supervisorProgram) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-play-circle me-1"></i> Start
                        </button>
                    </form>

                    <form action="{{ route('supervisor.stop', $supervisorProgram) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-stop-circle me-1"></i> Stop
                        </button>
                    </form>

                    <form action="{{ route('supervisor.restart', $supervisorProgram) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-repeat me-1"></i> Restart
                        </button>
                    </form>

                    <hr>

                    <form action="{{ route('supervisor.deploy', $supervisorProgram) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-cloud-upload me-1"></i> Redeploy Config
                        </button>
                    </form>

                    <a href="{{ route('supervisor.edit', $supervisorProgram) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-pencil me-1"></i> Edit Program
                    </a>

                    <form action="{{ route('supervisor.destroy', $supervisorProgram) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this program? This will stop all processes and remove the configuration.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-1"></i> Delete Program
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Config Info -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-file-earmark-code me-2"></i> Configuration
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Config File:</strong></p>
                <code class="small d-block bg-light p-2 rounded">{{ $supervisorProgram->getConfigFilePath() }}</code>
            </div>
        </div>
    </div>
</div>

@endsection
