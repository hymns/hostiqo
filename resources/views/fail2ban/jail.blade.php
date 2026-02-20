@extends('layouts.app')

@section('title', $jail . ' Jail - Fail2ban - Hostiqo')
@section('page-title', $jail . ' Jail')
@section('page-description', 'Jail details and banned IPs')

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

<div class="mb-3">
    <a href="{{ route('fail2ban.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Fail2ban
    </a>
</div>

<div class="row">
    <!-- Jail Info -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i> Jail Information
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th style="width: 40%">Name</th>
                        <td><code>{{ $jailStatus['name'] }}</code></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($jailStatus['enabled'] ?? false)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Ban Time</th>
                        <td>{{ $jailStatus['bantime'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Max Retry</th>
                        <td>{{ $jailStatus['maxretry'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Find Time</th>
                        <td>{{ $jailStatus['findtime'] ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i> Statistics
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-3">
                            <h4 class="mb-0 text-danger">{{ $jailStatus['currently_banned'] ?? 0 }}</h4>
                            <small class="text-muted">Currently Banned</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-3">
                            <h4 class="mb-0">{{ $jailStatus['total_banned'] ?? 0 }}</h4>
                            <small class="text-muted">Total Banned</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <h4 class="mb-0 text-warning">{{ $jailStatus['currently_failed'] ?? 0 }}</h4>
                            <small class="text-muted">Currently Failed</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <h4 class="mb-0">{{ $jailStatus['total_failed'] ?? 0 }}</h4>
                            <small class="text-muted">Total Failed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Ban -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-slash-circle me-2"></i> Ban IP in {{ $jail }}
    </div>
    <div class="card-body">
        <form action="{{ route('fail2ban.ban') }}" method="POST">
            @csrf
            <input type="hidden" name="jail" value="{{ $jail }}">
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="ip" class="form-control" placeholder="Enter IP address to ban" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-slash-circle"></i> Ban
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Banned IPs -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-list me-2"></i> Banned IPs
        @if(count($jailStatus['banned_ips'] ?? []) > 0)
            <span class="badge bg-danger ms-2">{{ count($jailStatus['banned_ips']) }}</span>
        @endif
    </div>
    <div class="card-body p-0">
        @if(count($jailStatus['banned_ips'] ?? []) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jailStatus['banned_ips'] as $ip)
                            <tr>
                                <td><code class="text-danger">{{ $ip }}</code></td>
                                <td class="text-end">
                                    <form action="{{ route('fail2ban.unban') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="jail" value="{{ $jail }}">
                                        <input type="hidden" name="ip" value="{{ $ip }}">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Unban {{ $ip }}?')">
                                            <i class="bi bi-unlock"></i> Unban
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="bi bi-check-circle text-success"></i> No IPs currently banned in this jail.
            </div>
        @endif
    </div>
</div>

<!-- Whitelist -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-shield-check me-2"></i> Whitelist (Ignored IPs)
    </div>
    <div class="card-body">
        @if(count($whitelist) > 0)
            <div class="d-flex flex-wrap gap-2">
                @foreach($whitelist as $ip)
                    <span class="badge bg-success fs-6">{{ $ip }}</span>
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">No whitelisted IPs configured for this jail.</p>
        @endif
        <hr>
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Whitelist is configured in <code>/etc/fail2ban/jail.local</code> using the <code>ignoreip</code> setting.
        </small>
    </div>
</div>

@endsection
