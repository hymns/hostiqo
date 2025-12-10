@extends('layouts.app')

@section('title', 'Service Logs')
@section('page-title', 'Service Logs')
@section('page-description')
    Viewing logs for: <strong>{{ $service }}</strong>
@endsection

@section('page-actions')
    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary me-2">
        <i class="bi bi-arrow-left me-2"></i> Back
    </a>
    <button class="btn btn-outline-primary" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise me-2"></i> Refresh
    </button>
@endsection

@section('content')
<div class="container-fluid py-4">

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-terminal me-2"></i> Journal Logs (Last 100 lines)</span>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('services.logs', ['service' => $service, 'lines' => 50]) }}" class="btn btn-sm btn-outline-light">50</a>
                <a href="{{ route('services.logs', ['service' => $service, 'lines' => 100]) }}" class="btn btn-sm btn-outline-light active">100</a>
                <a href="{{ route('services.logs', ['service' => $service, 'lines' => 200]) }}" class="btn btn-sm btn-outline-light">200</a>
                <a href="{{ route('services.logs', ['service' => $service, 'lines' => 500]) }}" class="btn btn-sm btn-outline-light">500</a>
            </div>
        </div>
        <div class="card-body p-0">
            <pre class="log-viewer mb-0"><code>{{ $logs }}</code></pre>
        </div>
    </div>
</div>
@endsection
