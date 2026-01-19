@extends('layouts.app')

@section('title', 'PM2 Process Manager')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-hdd-rack me-2"></i>PM2 Process Manager
            </h1>
            <p class="text-muted mb-0">Manage all Node.js applications running with PM2</p>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#startAllModal">
                <i class="bi bi-play-circle me-1"></i> Start All
            </button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#restartAllModal">
                <i class="bi bi-arrow-clockwise me-1"></i> Restart All
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#stopAllModal">
                <i class="bi bi-stop-circle me-1"></i> Stop All
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Total Apps</h6>
                            <h2 class="mb-0">{{ $totalApps }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-layers"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Running</h6>
                            <h2 class="mb-0">{{ $runningApps }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-play-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Stopped</h6>
                            <h2 class="mb-0">{{ $stoppedApps }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-stop-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Errors</h6>
                            <h2 class="mb-0">{{ $errorApps }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PM2 Applications Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>PM2 Applications
            </h5>
        </div>
        <div class="card-body">
            @if(count($apps) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>PID</th>
                                <th>Uptime</th>
                                <th>Restarts</th>
                                <th>CPU</th>
                                <th>Memory</th>
                                <th>Mode</th>
                                <th>Website</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apps as $app)
                                <tr>
                                    <td><code>{{ $app['pm_id'] }}</code></td>
                                    <td>
                                        <strong>{{ $app['name'] }}</strong>
                                        @if($app['website'])
                                            <br>
                                            <small class="text-muted">{{ $app['website']->domain }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusBadge = match($app['status']) {
                                                'online' => 'success',
                                                'stopped' => 'secondary',
                                                'stopping' => 'warning',
                                                'errored', 'error' => 'danger',
                                                'launching' => 'info',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusBadge }}">
                                            {{ ucfirst($app['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($app['pid'])
                                            <code>{{ $app['pid'] }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app['uptime'])
                                            {{ \Carbon\Carbon::createFromTimestamp($app['uptime'] / 1000)->diffForHumans(null, true) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $app['restarts'] > 10 ? 'warning' : 'info' }}">
                                            {{ $app['restarts'] }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($app['cpu'], 1) }}%</td>
                                    <td>{{ round($app['memory'] / 1024 / 1024, 1) }} MB</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $app['exec_mode'] }}
                                        </span>
                                        @if($app['instances'] > 1)
                                            <span class="badge bg-secondary">x{{ $app['instances'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app['website'])
                                            <a href="{{ route('websites.show', $app['website']) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('pm2.logs', $app['name']) }}" class="btn btn-outline-secondary" title="View Logs">
                                                <i class="bi bi-file-text"></i>
                                            </a>
                                            
                                            @if($app['status'] !== 'online')
                                                <form action="{{ route('pm2.start', $app['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Start">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            @if($app['status'] === 'online')
                                                <form action="{{ route('pm2.restart', $app['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning" title="Restart">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                </form>
                                                
                                                <form action="{{ route('pm2.stop', $app['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Stop">
                                                        <i class="bi bi-stop-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <button type="button" class="btn btn-outline-danger" title="Delete" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal{{ $app['pm_id'] }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal{{ $app['pm_id'] }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete <strong>{{ $app['name'] }}</strong> from PM2?</p>
                                                        <p class="text-danger mb-0">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            This will remove the process from PM2 but won't delete the website or files.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="{{ route('pm2.delete', $app['name']) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">No PM2 applications found</p>
                    <p class="text-muted">Deploy a Node.js website to see PM2 processes here</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Help Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>About PM2 Process Manager
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>What is PM2?</h6>
                    <p class="small">PM2 is a production process manager for Node.js applications with built-in load balancer, auto-restart, and monitoring capabilities.</p>
                    
                    <h6 class="mt-3">Features:</h6>
                    <ul class="small">
                        <li>Automatic application restart on crashes</li>
                        <li>Cluster mode for load balancing</li>
                        <li>Process monitoring (CPU, memory)</li>
                        <li>Log management</li>
                        <li>Zero-downtime reloads</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Status Indicators:</h6>
                    <ul class="small">
                        <li><span class="badge bg-success">Online</span> - Application is running</li>
                        <li><span class="badge bg-secondary">Stopped</span> - Application is stopped</li>
                        <li><span class="badge bg-danger">Errored</span> - Application crashed</li>
                        <li><span class="badge bg-info">Launching</span> - Application is starting</li>
                    </ul>
                    
                    <h6 class="mt-3">Configuration Files:</h6>
                    <p class="small mb-0">PM2 ecosystem configs are stored in:</p>
                    <code class="small">/etc/pm2/ecosystem.[app-name].config.js</code>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Start All Modal -->
<div class="modal fade" id="startAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start All Applications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to start all PM2 applications?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('pm2.start-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Start All</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Restart All Modal -->
<div class="modal fade" id="restartAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restart All Applications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restart all PM2 applications?</p>
                <p class="text-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This will cause brief downtime for all Node.js applications.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('pm2.restart-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">Restart All</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Stop All Modal -->
<div class="modal fade" id="stopAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stop All Applications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to stop all PM2 applications?</p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This will stop all Node.js applications and make them unavailable.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('pm2.stop-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Stop All</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
