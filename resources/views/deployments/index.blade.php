@extends('layouts.app')

@section('title', 'Deployments - Hostiqo')
@section('page-title', 'Deployments')
@section('page-description', 'View all deployment history')

@section('content')
    <div class="card">
        <div class="card-body">
            @if($deployments->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-4">No deployments yet</h4>
                    <p class="text-muted">Deployments will appear here when webhooks are triggered or manually deployed.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="120">Status</th>
                                <th>Webhook</th>
                                <th>Commit</th>
                                <th>Message</th>
                                <th>Author</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deployments as $deployment)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $deployment->status_badge }}">
                                            <i class="bi {{ $deployment->status_icon }} me-1"></i>
                                            {{ ucfirst($deployment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('webhooks.show', $deployment->webhook) }}" class="text-decoration-none">
                                            <strong>{{ $deployment->webhook->name }}</strong>
                                        </a>
                                        @if($deployment->webhook->domain)
                                            <br><small class="text-muted">{{ $deployment->webhook->domain }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($deployment->commit_hash)
                                            <code>{{ $deployment->short_commit_hash }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($deployment->commit_message)
                                            {{ Str::limit($deployment->commit_message, 50) }}
                                        @else
                                            <span class="text-muted">Manual deployment</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($deployment->author)
                                            <small>{{ $deployment->author }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($deployment->duration)
                                            <small>{{ $deployment->duration }}s</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $deployment->created_at->format('d M Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $deployment->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('deployments.show', $deployment) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-search"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $deployments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
