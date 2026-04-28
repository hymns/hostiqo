@extends('layouts.app')

@section('title', $database->name . ' - Database Details')
@section('page-title', $database->name)
@section('page-description', 'Database details and information')

@section('page-actions')
    <a href="{{ route('databases.' . ($type ?? 'mysql') . '.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <!-- Database Information -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-database"></i> Database Information</h5>
                    <hr class="mt-0 mb-3">

                    <div class="row mb-2">
                        <div class="col-md-4">Host</div>
                        <div class="col-md-8">{{ $database->host }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">Database Name</div>
                        <div class="col-md-8">{{ $database->name }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">Username</div>
                        <div class="col-md-8">{{ $database->username }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">Description</div>
                        <div class="col-md-8">{{ $database->description ?? 'No description provided' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">Created</div>
                        <div class="col-md-8">{{ $database->created_at->format('d M Y, h:i A') }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">Last Updated</div>
                        <div class="col-md-8">{{ $database->updated_at->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            </div>

            <!-- Connection String -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-link-45deg"></i> Connection Information</h5>
                    <hr class="mt-0 mb-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Connection String (Laravel .env format)</label>
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control form-control-sm font-monospace bg-light"
                                readonly
                                value="DB_CONNECTION=mysql&#10;DB_HOST={{ $database->host }}&#10;DB_PORT=3306&#10;DB_DATABASE={{ $database->name }}&#10;DB_USERNAME={{ $database->username }}&#10;DB_PASSWORD=your_password_here"
                                id="connectionString"
                            >
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyToClipboard('DB_CONNECTION=mysql\nDB_HOST={{ $database->host }}\nDB_PORT=3306\nDB_DATABASE={{ $database->name }}\nDB_USERNAME={{ $database->username }}\nDB_PASSWORD=your_password_here', this)">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">MySQL Command Line</label>
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control form-control-sm font-monospace bg-light"
                                readonly
                                value="mysql -h {{ $database->host }} -u {{ $database->username }} -p {{ $database->name }}"
                                id="mysqlCommand"
                            >
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyToClipboard('mysql -h {{ $database->host }} -u {{ $database->username }} -p {{ $database->name }}', this)">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <!-- Compact Icon Grid -->
                    <div class="d-flex gap-2 flex-wrap justify-content-center mb-3">
                        <!-- Change Password Button -->
                        <a href="{{ route('databases.' . ($type ?? 'mysql') . '.change-password', $database) }}" class="action-btn warning" title="Change Password">
                            <i class="bi bi-lock-fill"></i>
                        </a>

                        <!-- Edit Button -->
                        <a href="{{ route('databases.' . ($type ?? 'mysql') . '.edit', $database) }}" class="action-btn primary" title="Edit Description">
                            <i class="bi bi-pencil"></i>
                        </a>

                        <!-- Delete Button -->
                        <form action="{{ route('databases.' . ($type ?? 'mysql') . '.destroy', $database) }}" method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this database? This action cannot be undone!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-btn danger" title="Delete Database">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Action Labels -->
                    <div class="text-center">
                        <small class="text-muted" style="font-size: 0.75rem;">Hover for action details</small>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-activity me-2"></i>Status
                        <span class="status-dot {{ $database->exists_in_mysql ? 'active' : 'inactive' }} ms-2"></span>
                    </h5>
                    <hr class="mt-0 mb-3">

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <span class="text-muted">Status</span>
                        </div>
                        <div class="col-md-6 text-end">
                            @if($database->exists_in_mysql)
                                <span class="badge badge-md badge-pastel-green">Active</span>
                            @else
                                <span class="badge badge-md badge-pastel-red">Not Found</span>
                            @endif
                        </div>
                    </div>

                    @if($database->exists_in_mysql)
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <span class="text-muted">Database Size</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>{{ $database->size_mb }} MB</strong>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <span class="text-muted">Tables</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>{{ $database->table_count }}</strong>
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Status Legend -->
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-2"></i><strong>Status Tips</strong>
                            <div class="mb-0 mt-1">
                                <span class="status-dot active"></span>
                                <span class="ms-2">Database exists and is operational</span>
                            </div>
                            <div>
                                <span class="status-dot inactive"></span>
                                <span class="ms-2">Database not found or unavailable</span>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <strong>Database not found in MySQL</strong>
                            <p class="mb-0 mt-2 small">The database may have been deleted manually from MySQL.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
