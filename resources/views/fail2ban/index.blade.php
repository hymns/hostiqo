@extends('layouts.app')

@section('title', 'Fail2ban - Hostiqo')
@section('page-title', 'Fail2ban')
@section('page-description', 'Intrusion prevention and IP ban management')

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

<!-- Service Status & Controls -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-shield-lock me-2"></i> Service Status
        </span>
        <div class="btn-group btn-group-sm">
            @if($serviceStatus['running'])
                <form action="{{ route('fail2ban.restart') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="bi bi-arrow-clockwise"></i> Restart
                    </button>
                </form>
                <form action="{{ route('fail2ban.stop') }}" method="POST" class="d-inline ms-1">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Stop fail2ban service?')">
                        <i class="bi bi-stop-circle"></i> Stop
                    </button>
                </form>
            @else
                <form action="{{ route('fail2ban.start') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-play-circle"></i> Start
                    </button>
                </form>
            @endif
            <form action="{{ route('fail2ban.reload') }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <h5 class="mb-1">
                        @if($serviceStatus['running'])
                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Running</span>
                        @else
                            <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Stopped</span>
                        @endif
                    </h5>
                    <small class="text-muted">Service Status</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <h5 class="mb-1">{{ $summary['total_jails'] ?? 0 }}</h5>
                    <small class="text-muted">Active Jails</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <h5 class="mb-1 text-danger">{{ $summary['total_banned'] ?? 0 }}</h5>
                    <small class="text-muted">Currently Banned</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <a href="{{ route('fail2ban.logs') }}" class="text-decoration-none">
                        <h5 class="mb-1"><i class="bi bi-file-text"></i></h5>
                        <small class="text-muted">View Logs</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-slash-circle me-2"></i> Quick Ban IP
            </div>
            <div class="card-body">
                <form action="{{ route('fail2ban.ban') }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-5">
                            <select name="jail" class="form-select" required>
                                <option value="">Select Jail</option>
                                @foreach($jails as $jail)
                                    <option value="{{ $jail['name'] }}">{{ $jail['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="ip" class="form-control" placeholder="IP Address" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger w-100">Ban</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-unlock me-2"></i> Quick Unban IP
            </div>
            <div class="card-body">
                <form action="{{ route('fail2ban.unban') }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-5">
                            <select name="jail" class="form-select" required>
                                <option value="">Select Jail</option>
                                @foreach($jails as $jail)
                                    <option value="{{ $jail['name'] }}">{{ $jail['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="ip" class="form-control" placeholder="IP Address" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Unban</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Jails List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i> Jails</span>
        <a href="{{ route('fail2ban.banned') }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-eye"></i> View All Banned IPs
        </a>
    </div>
    <div class="card-body p-0">
        @if(count($jails) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Jail</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Currently Banned</th>
                            <th class="text-center">Total Banned</th>
                            <th class="text-center">Failed</th>
                            <th class="text-center">Settings</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jails as $jail)
                            <tr>
                                <td>
                                    <a href="{{ route('fail2ban.jail', $jail['name']) }}" class="fw-bold text-decoration-none">
                                        {{ $jail['name'] }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($jail['enabled'] ?? false)
                                        <span class="badge bg-success">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(($jail['currently_banned'] ?? 0) > 0)
                                        <span class="badge bg-danger">{{ $jail['currently_banned'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $jail['total_banned'] ?? 0 }}</td>
                                <td class="text-center">
                                    <span class="text-warning">{{ $jail['currently_failed'] ?? 0 }}</span>
                                    / {{ $jail['total_failed'] ?? 0 }}
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        Ban: {{ $jail['bantime'] ?? '-' }} |
                                        Max: {{ $jail['maxretry'] ?? '-' }} |
                                        Find: {{ $jail['findtime'] ?? '-' }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('fail2ban.jail', $jail['name']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-x display-4"></i>
                <p class="mt-2">No jails found. Fail2ban may not be running.</p>
            </div>
        @endif
    </div>
</div>

@endsection
