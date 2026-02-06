@extends('layouts.app')

@section('title', 'Artisan Commands - Hostiqo')
@section('page-title', 'Artisan Commands')
@section('page-description', 'Execute Laravel artisan commands for optimization and cache management')

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

@if(session('output'))
    <div class="alert alert-info alert-dismissible fade show">
        <strong>Command Output:</strong>
        <pre class="mb-0 mt-2" style="font-size: 0.875rem;">{{ session('output') }}</pre>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Site Selection -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('artisan.index') }}" class="row align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold">
                    <i class="bi bi-globe me-1"></i> Laravel Site:
                </label>
            </div>
            <div class="col-md-4">
                <select name="site" class="form-select" onchange="this.form.submit()">
                    @foreach($laravelSites as $key => $site)
                        <option value="{{ $key }}" {{ $selectedSite == $key ? 'selected' : '' }}>
                            {{ $site['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <span class="text-muted small">
                    <i class="bi bi-folder me-1"></i>
                    {{ $laravelSites[$selectedSite]['path'] ?? 'Unknown' }}
                </span>
            </div>
        </form>
    </div>
</div>

<!-- Cache Management Grid -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-database me-2"></i> Cache Management
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 30%">Category</th>
                        <th style="width: 35%">Cache</th>
                        <th style="width: 35%">Clear</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Optimization -->
                    <tr>
                        <td class="align-middle fw-bold">
                            <i class="bi bi-rocket-takeoff text-success me-1"></i> Optimization
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="optimize">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-lightning-charge me-1"></i> Optimize
                                </button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="optimize:clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash me-1"></i> Clear Optimization
                                </button>
                            </form>
                        </td>
                    </tr>
                    <!-- Config -->
                    <tr>
                        <td class="align-middle fw-bold">
                            <i class="bi bi-gear text-primary me-1"></i> Config
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="config:cache">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-lightning-charge me-1"></i> Cache Config
                                </button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="config:clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash me-1"></i> Clear Config
                                </button>
                            </form>
                        </td>
                    </tr>
                    <!-- Routes -->
                    <tr>
                        <td class="align-middle fw-bold">
                            <i class="bi bi-signpost text-info me-1"></i> Routes
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="route:cache">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-lightning-charge me-1"></i> Cache Routes
                                </button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="route:clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash me-1"></i> Clear Routes
                                </button>
                            </form>
                        </td>
                    </tr>
                    <!-- Views -->
                    <tr>
                        <td class="align-middle fw-bold">
                            <i class="bi bi-eye text-warning me-1"></i> Views
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="view:cache">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-lightning-charge me-1"></i> Cache Views
                                </button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="view:clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash me-1"></i> Clear Views
                                </button>
                            </form>
                        </td>
                    </tr>
                    <!-- Application Cache -->
                    <tr>
                        <td class="align-middle fw-bold">
                            <i class="bi bi-database text-secondary me-1"></i> App Cache
                        </td>
                        <td class="text-muted text-center">-</td>
                        <td>
                            <form action="{{ route('artisan.execute') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="site" value="{{ $selectedSite }}">
                                <input type="hidden" name="command" value="cache:clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash me-1"></i> Clear App Cache
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100 border-success">
            <div class="card-header bg-success text-white">
                <i class="bi bi-rocket-takeoff me-2"></i> Production Optimization
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Runs: <code>config:cache</code>, <code>route:cache</code>, <code>view:cache</code>, <code>optimize</code>
                </p>
                <form action="{{ route('artisan.optimize-production') }}" method="POST">
                    @csrf
                    <input type="hidden" name="site" value="{{ $selectedSite }}">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-gear-wide-connected me-1"></i> Optimize for Production
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100 border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-x-octagon me-2"></i> Clear All Caches
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Clears: <code>cache</code>, <code>config</code>, <code>routes</code>, <code>views</code>
                </p>
                <form action="{{ route('artisan.clear-all') }}" method="POST">
                    @csrf
                    <input type="hidden" name="site" value="{{ $selectedSite }}">
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Clear all caches?')">
                        <i class="bi bi-trash me-1"></i> Clear All Caches
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
