@extends('layouts.app')

@section('title', 'Webhooks - Git Webhook Manager')
@section('page-title', 'Webhooks')
@section('page-description', 'Manage your Git webhooks')

@section('page-actions')
    <a href="{{ route('webhooks.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Create Webhook
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if($webhooks->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-4">No webhooks yet</h4>
                    <p class="text-muted">Create your first webhook to get started with automated deployments.</p>
                    <a href="{{ route('webhooks.create') }}" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i> Create Your First Webhook
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name / Domain</th>
                                <th>Repository</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Deployments</th>
                                <th>Last Deploy</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($webhooks as $webhook)
                                <tr>
                                    <td>
                                        <strong>{{ $webhook->name }}</strong>
                                        @if($webhook->domain)
                                            <br><small class="text-muted"><i class="bi bi-globe"></i> {{ $webhook->domain }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="font-monospace">{{ Str::limit($webhook->repository_url, 40) }}</small>
                                        <br><small class="text-muted"><i class="bi {{ $webhook->provider_icon }}"></i> {{ ucfirst($webhook->git_provider) }}</small>
                                    </td>
                                    <td>
                                        <code>{{ $webhook->branch }}</code>
                                    </td>
                                    <td>
                                        <form action="{{ route('webhooks.toggle', $webhook) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="badge bg-{{ $webhook->status_badge }} border-0" style="cursor: pointer;">
                                                {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $webhook->deployments_count }}</span>
                                        @if($webhook->latestDeployment)
                                            <span class="badge bg-{{ $webhook->latestDeployment->status_badge }}">
                                                {{ ucfirst($webhook->latestDeployment->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($webhook->last_deployed_at)
                                            <small class="text-muted">{{ $webhook->last_deployed_at->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Never</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('webhooks.show', $webhook) }}" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-search"></i>
                                            </a>
                                            <a href="{{ route('webhooks.edit', $webhook) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Delete" 
                                                    onclick="if(confirmDelete('Are you sure you want to delete this webhook?')) { document.getElementById('delete-form-{{ $webhook->id }}').submit(); }">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $webhook->id }}" action="{{ route('webhooks.destroy', $webhook) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $webhooks->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
