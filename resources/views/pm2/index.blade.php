@extends('layouts.app')

@section('title', 'PM2 Process Manager - Hostiqo')
@section('page-title', 'PM2 Process Manager')
@section('page-description', 'Manage Node.js applications with PM2')

@section('content')

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

    <div class="card">
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
                    <i class="bi bi-hdd-rack text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-4">No PM2 applications yet</h4>
                    <p class="text-muted">Deploy a Node.js website to see PM2 processes here</p>
                </div>
            @endif
        </div>
    </div>

@endsection
