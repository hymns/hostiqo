@extends('layouts.app')

@section('title', 'Artisan Commands - Hostiqo')
@section('page-title', 'Artisan Commands')
@section('page-description', 'Execute common Laravel artisan commands for optimization and cache management')

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

<div class="row">
    <!-- Cache Management -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-database me-2"></i> Cache Management
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('artisan.cache-clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Clear application cache?')">
                            <i class="bi bi-trash me-1"></i> Clear Application Cache
                        </button>
                    </form>

                    <form action="{{ route('artisan.config-clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Clear config cache?')">
                            <i class="bi bi-trash me-1"></i> Clear Config Cache
                        </button>
                    </form>

                    <form action="{{ route('artisan.config-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success w-100">
                            <i class="bi bi-lightning-charge me-1"></i> Cache Config
                        </button>
                    </form>

                    <form action="{{ route('artisan.route-clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Clear route cache?')">
                            <i class="bi bi-trash me-1"></i> Clear Route Cache
                        </button>
                    </form>

                    <form action="{{ route('artisan.route-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success w-100">
                            <i class="bi bi-lightning-charge me-1"></i> Cache Routes
                        </button>
                    </form>

                    <form action="{{ route('artisan.view-clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Clear view cache?')">
                            <i class="bi bi-trash me-1"></i> Clear View Cache
                        </button>
                    </form>

                    <form action="{{ route('artisan.view-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success w-100">
                            <i class="bi bi-lightning-charge me-1"></i> Cache Views
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Optimization -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <i class="bi bi-speedometer2 me-2"></i> Optimization
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('artisan.optimize') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-rocket-takeoff me-1"></i> Optimize Application
                        </button>
                    </form>

                    <form action="{{ route('artisan.optimize-production') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-gear-wide-connected me-1"></i> Optimize for Production
                        </button>
                    </form>

                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Production optimization runs: config:cache, route:cache, view:cache, and optimize
                    </small>

                    <hr>

                    <form action="{{ route('artisan.clear-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Clear all caches? This will remove all cached data.')">
                            <i class="bi bi-x-octagon me-1"></i> Clear All Caches
                        </button>
                    </form>

                    <small class="text-muted">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Clears all caches: application, config, routes, and views
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Reference -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-book me-2"></i> Command Reference
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Cache Commands</h6>
                        <ul class="small">
                            <li><code>cache:clear</code> - Clear application cache</li>
                            <li><code>config:clear</code> - Clear configuration cache</li>
                            <li><code>config:cache</code> - Cache configuration files</li>
                            <li><code>route:clear</code> - Clear route cache</li>
                            <li><code>route:cache</code> - Cache routes for faster routing</li>
                            <li><code>view:clear</code> - Clear compiled view files</li>
                            <li><code>view:cache</code> - Compile all view files</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Optimization</h6>
                        <ul class="small">
                            <li><code>optimize</code> - Cache framework bootstrap files</li>
                            <li>Production optimization runs multiple cache commands for maximum performance</li>
                        </ul>
                        
                        <h6 class="fw-bold mt-3">When to Use</h6>
                        <ul class="small">
                            <li><strong>Development:</strong> Clear caches when config/routes change</li>
                            <li><strong>Production:</strong> Cache everything after deployment</li>
                            <li><strong>Debugging:</strong> Clear all caches if issues occur</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
