@extends('layouts.app')

@section('title', $database->name . ' - Database Details')
@section('page-title', $database->name)
@section('page-description', 'Database details and information')

@section('page-actions')
    <div class="btn-group">
        <a href="{{ route('databases.change-password', $database) }}" class="btn btn-warning">
            <i class="bi bi-key me-1"></i> Change Password
        </a>
        <a href="{{ route('databases.edit', $database) }}" class="btn btn-secondary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <form action="{{ route('databases.destroy', $database) }}" method="POST" class="d-inline" onsubmit="return confirmDelete('Are you sure you want to delete this database? This action cannot be undone!')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <!-- Database Information -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Database Information
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td width="200" class="text-muted">Database Name:</td>
                                <td><strong class="font-monospace">{{ $database->name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Username:</td>
                                <td><code>{{ $database->username }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Host:</td>
                                <td>{{ $database->host }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Description:</td>
                                <td>{{ $database->description ?? 'No description provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created:</td>
                                <td>{{ $database->created_at->format('F d, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Updated:</td>
                                <td>{{ $database->updated_at->format('F d, Y H:i:s') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Connection String -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-link-45deg me-2"></i>Connection Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Connection String (Laravel .env format)</label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                class="form-control font-monospace bg-light" 
                                readonly 
                                value="DB_CONNECTION=mysql&#10;DB_HOST={{ $database->host }}&#10;DB_PORT=3306&#10;DB_DATABASE={{ $database->name }}&#10;DB_USERNAME={{ $database->username }}&#10;DB_PASSWORD=your_password_here"
                                id="connectionString"
                            >
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('DB_CONNECTION=mysql\nDB_HOST={{ $database->host }}\nDB_PORT=3306\nDB_DATABASE={{ $database->name }}\nDB_USERNAME={{ $database->username }}\nDB_PASSWORD=your_password_here', this)">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">MySQL Command Line</label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                class="form-control font-monospace bg-light" 
                                readonly 
                                value="mysql -h {{ $database->host }} -u {{ $database->username }} -p {{ $database->name }}"
                                id="mysqlCommand"
                            >
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('mysql -h {{ $database->host }} -u {{ $database->username }} -p {{ $database->name }}', this)">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-activity me-2"></i>Status
                    </h5>
                </div>
                <div class="card-body">
                    @if($database->exists_in_mysql)
                        <div class="alert alert-success mb-3">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Database is active</strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Database Size:</span>
                            <strong>{{ $database->size_mb }} MB</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tables:</span>
                            <strong>{{ $database->table_count }}</strong>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <strong>Database not found in MySQL</strong>
                            <p class="mb-0 mt-2 small">The database may have been deleted manually from MySQL.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('databases.change-password', $database) }}" class="btn btn-warning">
                            <i class="bi bi-key me-2"></i>Change Password
                        </a>
                        <a href="{{ route('databases.edit', $database) }}" class="btn btn-secondary">
                            <i class="bi bi-pencil me-2"></i>Edit Description
                        </a>
                        <a href="{{ route('databases.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
