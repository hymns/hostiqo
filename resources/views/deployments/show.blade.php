@extends('layouts.app')

@section('title', 'Deployment Details - Hostiqo')
@section('page-title', 'Deployment Details')
@section('page-description', 'Deployment #' . $deployment->id)

@section('page-actions')
    <a href="{{ route('webhooks.show', $deployment->webhook) }}" class="btn btn-outline-primary">
        <i class="bi bi-webhook me-1"></i> View Webhook
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <!-- Deployment Information -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i> Deployment Information
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Status:</strong></div>
                        <div class="col-md-9">
                            <span class="badge bg-{{ $deployment->status_badge }}">
                                <i class="bi {{ $deployment->status_icon }} me-1"></i>
                                {{ ucfirst($deployment->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Webhook:</strong></div>
                        <div class="col-md-9">
                            <a href="{{ route('webhooks.show', $deployment->webhook) }}">
                                {{ $deployment->webhook->name }}
                            </a>
                        </div>
                    </div>

                    @if($deployment->commit_hash)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Commit Hash:</strong></div>
                            <div class="col-md-9">
                                <code>{{ $deployment->commit_hash }}</code>
                            </div>
                        </div>
                    @endif

                    @if($deployment->commit_message)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Commit Message:</strong></div>
                            <div class="col-md-9">{{ $deployment->commit_message }}</div>
                        </div>
                    @endif

                    @if($deployment->author)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Author:</strong></div>
                            <div class="col-md-9">{{ $deployment->author }}</div>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Started At:</strong></div>
                        <div class="col-md-9">
                            @if($deployment->started_at)
                                {{ $deployment->started_at->format('d M Y, h:i:s A') }}
                            @else
                                <span class="text-muted">Not started</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Completed At:</strong></div>
                        <div class="col-md-9">
                            @if($deployment->completed_at)
                                {{ $deployment->completed_at->format('d M Y, h:i:s A') }}
                            @else
                                <span class="text-muted">Not completed</span>
                            @endif
                        </div>
                    </div>

                    @if($deployment->duration)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Duration:</strong></div>
                            <div class="col-md-9">
                                <span class="badge bg-info">{{ $deployment->duration }} seconds</span>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-3"><strong>Created:</strong></div>
                        <div class="col-md-9">{{ $deployment->created_at->format('d M Y, h:i:s A') }}</div>
                    </div>
                </div>
            </div>

            <!-- Deployment Output -->
            @if($deployment->output)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-terminal me-2"></i> Deployment Output</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard(document.getElementById('deployment-output').textContent, this)">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <pre class="mb-0" id="deployment-output">{{ $deployment->output }}</pre>
                    </div>
                </div>
            @endif

            <!-- Error Message -->
            @if($deployment->error_message)
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-exclamation-triangle me-2"></i> Error Message
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 text-danger">{{ $deployment->error_message }}</pre>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Repository Info -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-git me-2"></i> Repository Info
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Provider:</strong>
                        <div class="mt-1">
                            <i class="bi {{ $deployment->webhook->provider_icon }} me-1"></i>
                            {{ ucfirst($deployment->webhook->git_provider) }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Repository:</strong>
                        <div class="mt-1"><code class="small">{{ $deployment->webhook->repository_url }}</code></div>
                    </div>

                    <div class="mb-3">
                        <strong>Branch:</strong>
                        <div class="mt-1"><code>{{ $deployment->webhook->branch }}</code></div>
                    </div>

                    <div class="mb-0">
                        <strong>Local Path:</strong>
                        <div class="mt-1"><code class="small">{{ $deployment->webhook->local_path }}</code></div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i> Timeline
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="mb-3">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <strong>Created</strong>
                            <div class="text-muted small ms-4">{{ $deployment->created_at->format('h:i:s A') }}</div>
                        </div>

                        @if($deployment->started_at)
                            <div class="mb-3">
                                <i class="bi bi-play-circle text-info me-2"></i>
                                <strong>Started</strong>
                                <div class="text-muted small ms-4">{{ $deployment->started_at->format('h:i:s A') }}</div>
                            </div>
                        @endif

                        @if($deployment->completed_at)
                            <div class="mb-0">
                                <i class="bi bi-{{ $deployment->status === 'success' ? 'check-circle text-success' : 'x-circle text-danger' }} me-2"></i>
                                <strong>{{ ucfirst($deployment->status) }}</strong>
                                <div class="text-muted small ms-4">{{ $deployment->completed_at->format('h:i:s A') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning me-2"></i> Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('webhooks.show', $deployment->webhook) }}" class="btn btn-primary">
                            <i class="bi bi-webhook me-1"></i> View Webhook
                        </a>
                        
                        <a href="{{ route('deployments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> All Deployments
                        </a>

                        @if($deployment->status === 'failed')
                            <form action="{{ route('deployments.trigger', $deployment->webhook) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-arrow-repeat me-1"></i> Retry Deployment
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
