@extends('layouts.app')

@section('title', 'Edit Database - ' . $database->name)
@section('page-title', 'Edit Database')
@section('page-description', 'Update database information')

@section('page-actions')
    <a href="{{ route('databases.' . ($type ?? 'mysql') . '.show', $database) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Database
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> You can only update the description here.
                Database name and username cannot be changed. To change the password,
                <a href="{{ route('databases.' . ($type ?? 'mysql') . '.change-password', $database) }}" class="alert-link">click here</a>.
            </div>

            <form action="{{ route('databases.' . ($type ?? 'mysql') . '.update', $database) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-database me-2"></i> Database Information (Read-only)
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Database Name</label>
                            <input
                                type="text"
                                class="form-control font-monospace bg-light"
                                value="{{ $database->name }}"
                                readonly
                            >
                            <div class="form-text">Database name cannot be changed</div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input
                                type="text"
                                class="form-control font-monospace bg-light"
                                value="{{ $database->username }}"
                                readonly
                            >
                            <div class="form-text">Username cannot be changed</div>
                        </div>

                        <div class="mb-3">
                            <label for="host" class="form-label">Host</label>
                            <input
                                type="text"
                                class="form-control bg-light"
                                value="{{ $database->host }}"
                                readonly
                            >
                            <div class="form-text">Host cannot be changed</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil me-2"></i> Editable Fields
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Optional description for this database"
                            >{{ old('description', $database->description) }}</textarea>
                            <div class="form-text">Add or update a description for this database</div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Update Database
                    </button>
                    <a href="{{ route('databases.' . ($type ?? 'mysql') . '.show', $database) }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-header">
                    <i class="bi bi-lightbulb me-2"></i> Quick Tips
                </div>
                <div class="card-body">
                    <h6>Edit Limitations</h6>
                    <p class="small">Only the description field can be updated. Database name, username, and host are permanent.</p>

                    <h6 class="mt-3">Change Password</h6>
                    <p class="small">To update the database password, use the <a href="{{ route('databases.' . ($type ?? 'mysql') . '.change-password', $database) }}">Change Password</a> page.</p>

                    <h6 class="mt-3">Database Name/User</h6>
                    <p class="small">To change the database name or username, you must create a new database and migrate your data.</p>

                    <h6 class="mt-3">Description Usage</h6>
                    <p class="small">Use the description to document:</p>
                    <ul class="small">
                        <li>Which application uses this database</li>
                        <li>Purpose (production, staging, testing)</li>
                        <li>Important notes or warnings</li>
                    </ul>

                    <h6 class="mt-3">Quick Actions</h6>
                    <p class="small">Return to the <a href="{{ route('databases.' . ($type ?? 'mysql') . '.show', $database) }}">database details</a> page for connection strings and other actions.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
