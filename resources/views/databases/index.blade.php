@extends('layouts.app')

@section('title', 'Databases - Git Webhook Manager')
@section('page-title', 'Database Management')
@section('page-description', 'Manage MySQL databases and users')

@push('styles')
<style>
    details summary { cursor: pointer; }
</style>
@endpush

@section('page-actions')
    <div class="btn-group">
        @if($permissions['can_create'])
            <a href="{{ route('databases.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create Database
            </a>
        @else
            <button class="btn btn-primary" disabled title="Insufficient MySQL privileges">
                <i class="bi bi-plus-circle me-1"></i> Create Database
            </button>
        @endif
        
        @if(!$permissions['can_create'])
            <a href="{{ route('databases.recheck-permissions') }}" class="btn btn-outline-secondary" title="Recheck permissions">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if(!$permissions['can_create'])
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Insufficient Permissions:</strong> {{ $permissions['message'] }}
            <br>
            <small class="mt-2 d-block">
                <strong>Current User:</strong> <code>{{ $permissions['current_user'] ?? 'Unknown' }}</code><br>
                <strong>Missing Privileges:</strong>
                @foreach($permissions['missing_privileges'] ?? [] as $privilege)
                    <span class="badge bg-danger">{{ $privilege }}</span>
                @endforeach
            </small>
            @if(!empty($permissions['grants']))
                <details class="mt-2" open>
                    <summary class="cursor-pointer small"><strong>View Current Grants (Debug)</strong></summary>
                    <div class="mt-2 p-2 bg-light rounded">
                        @foreach($permissions['grants'] as $grant)
                            <code class="d-block small text-dark mb-1">{{ $grant }}</code>
                        @endforeach
                    </div>
                </details>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    {{-- Debug Info (only in local environment) --}}
    @if(config('app.env') === 'local' && !empty($permissions['grants']))
        <div class="alert alert-info">
            <strong>Debug Info (Local Only):</strong><br>
            <small>
                has_create_db: {{ $permissions['has_create_db'] ? 'true' : 'false' }}<br>
                has_create_user: {{ $permissions['has_create_user'] ? 'true' : 'false' }}<br>
                has_grant_option: {{ $permissions['has_grant_option'] ? 'true' : 'false' }}
            </small>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($databases->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-database text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-4">No databases yet</h4>
                    <p class="text-muted">Create your first database to get started.</p>
                    @if($permissions['can_create'])
                        <a href="{{ route('databases.create') }}" class="btn btn-primary mt-3">
                            <i class="bi bi-plus-circle me-1"></i> Create Your First Database
                        </a>
                    @else
                        <button class="btn btn-primary mt-3" disabled title="Insufficient MySQL privileges">
                            <i class="bi bi-plus-circle me-1"></i> Create Your First Database
                        </button>
                        <p class="text-danger mt-3 small">You don't have sufficient MySQL privileges to create databases.</p>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Database Name</th>
                                <th>Username</th>
                                <th>Host</th>
                                <th>Status</th>
                                <th>Size</th>
                                <th>Tables</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($databases as $database)
                                <tr>
                                    <td>
                                        <strong class="font-monospace">{{ $database->name }}</strong>
                                        @if($database->description)
                                            <br><small class="text-muted">{{ Str::limit($database->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $database->username }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $database->host }}</small>
                                    </td>
                                    <td>
                                        @if($database->exists_in_mysql)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Not Found</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($database->exists_in_mysql)
                                            <small class="text-muted">{{ $database->size_mb }} MB</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($database->exists_in_mysql)
                                            <span class="badge bg-info">{{ $database->table_count }}</span>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $database->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('databases.show', $database) }}" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-search"></i>
                                            </a>
                                            <a href="{{ route('databases.change-password', $database) }}" class="btn btn-outline-primary" title="Change Password">
                                                <i class="bi bi-lock-fill"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Delete" 
                                                    onclick="if(confirmDelete('Are you sure you want to delete this database? This action cannot be undone!')) { document.getElementById('delete-form-{{ $database->id }}').submit(); }">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $database->id }}" action="{{ route('databases.destroy', $database) }}" method="POST" class="d-none">
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
                    {{ $databases->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
