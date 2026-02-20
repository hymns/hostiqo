@extends('layouts.app')

@section('title', 'Fail2ban Logs - Hostiqo')
@section('page-title', 'Fail2ban Logs')
@section('page-description', 'View fail2ban activity logs')

@section('content')

<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('fail2ban.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Fail2ban
    </a>
    <form method="GET" action="{{ route('fail2ban.logs') }}" class="d-flex gap-2">
        <select name="lines" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            <option value="50" {{ $lines == 50 ? 'selected' : '' }}>Last 50 lines</option>
            <option value="100" {{ $lines == 100 ? 'selected' : '' }}>Last 100 lines</option>
            <option value="200" {{ $lines == 200 ? 'selected' : '' }}>Last 200 lines</option>
            <option value="500" {{ $lines == 500 ? 'selected' : '' }}>Last 500 lines</option>
        </select>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-file-text me-2"></i> /var/log/fail2ban.log
    </div>
    <div class="card-body p-0">
        @if(!empty($log))
            <pre class="mb-0 p-3" style="max-height: 600px; overflow-y: auto; font-size: 0.8rem; background: #1e1e1e; color: #d4d4d4;">{{ $log }}</pre>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-x display-4"></i>
                <p class="mt-2">No log entries found or unable to read log file.</p>
            </div>
        @endif
    </div>
</div>

@endsection
