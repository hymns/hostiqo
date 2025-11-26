@extends('layouts.app')

@section('title', 'Dashboard - Git Webhook Manager')
@section('page-title', 'Dashboard')
@section('page-description', 'Overview of your webhooks and deployments')

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Webhooks</p>
                            <h3 class="mb-0">{{ $totalWebhooks }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="bi bi-cloud-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Active Webhooks</p>
                            <h3 class="mb-0">{{ $activeWebhooks }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Deployments</p>
                            <h3 class="mb-0">{{ $totalDeployments }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Last 24h</p>
                            <h3 class="mb-0">{{ $recentDeployments->where('created_at', '>=', now()->subDay())->count() }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Webhooks List -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Configured Webhooks</span>
                    <a href="{{ route('webhooks.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> New Webhook
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($webhooks->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">No webhooks configured yet.</p>
                            <a href="{{ route('webhooks.create') }}" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle me-1"></i> Create Your First Webhook
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Deployments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($webhooks as $webhook)
                                        <tr>
                                            <td>
                                                <strong>{{ $webhook->name }}</strong>
                                                @if($webhook->domain)
                                                    <br><small class="text-muted">{{ $webhook->domain }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <i class="bi {{ $webhook->provider_icon }}"></i>
                                                {{ ucfirst($webhook->git_provider) }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $webhook->status_badge }}">
                                                    {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $webhook->deployments_count }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('webhooks.show', $webhook) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent Deployments</span>
                    <a href="{{ route('deployments.index') }}" class="btn btn-outline-primary btn-sm">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentDeployments->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">No deployments yet.</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentDeployments as $deployment)
                                <a href="{{ route('deployments.show', $deployment) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="bi {{ $deployment->status_icon }} me-2"></i>
                                                <strong>{{ $deployment->webhook->name }}</strong>
                                            </div>
                                            @if($deployment->commit_message)
                                                <small class="text-muted d-block">{{ Str::limit($deployment->commit_message, 50) }}</small>
                                            @endif
                                            @if($deployment->author)
                                                <small class="text-muted">by {{ $deployment->author }}</small>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-{{ $deployment->status_badge }}">
                                                {{ ucfirst($deployment->status) }}
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $deployment->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
