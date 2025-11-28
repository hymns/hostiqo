@extends('layouts.app')

@section('title', 'Create Database - Git Webhook Manager')
@section('page-title', 'Create New Database')
@section('page-description', 'Create a new MySQL database and user')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Permission Status -->
            @if(isset($permissions) && $permissions['can_create'])
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Permissions Verified:</strong> You have all required privileges to create databases.
                    <br>
                    <small class="mt-1 d-block">
                        <strong>MySQL User:</strong> <code>{{ $permissions['current_user'] }}</code>
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('databases.store') }}" method="POST">
                        @csrf

                        <!-- Database Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Database Name <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control font-monospace @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}" 
                                required
                                placeholder="my_database"
                                pattern="[a-zA-Z0-9_]+"
                            >
                            <small class="form-text text-muted">
                                Only letters, numbers, and underscores allowed. No spaces or special characters.
                            </small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Database Username <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control font-monospace @error('username') is-invalid @enderror" 
                                id="username" 
                                name="username" 
                                value="{{ old('username') }}" 
                                required
                                placeholder="db_user"
                                pattern="[a-zA-Z0-9_]+"
                            >
                            <small class="form-text text-muted">
                                Username for the database user. Only letters, numbers, and underscores.
                            </small>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="password" 
                                class="form-control @error('password') is-invalid @enderror" 
                                id="password" 
                                name="password" 
                                required
                                minlength="8"
                            >
                            <small class="form-text text-muted">
                                Minimum 8 characters. Use a strong password.
                            </small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                required
                                minlength="8"
                            >
                        </div>

                        <!-- Host -->
                        <div class="mb-3">
                            <label for="host" class="form-label">Host</label>
                            <input 
                                type="text" 
                                class="form-control @error('host') is-invalid @enderror" 
                                id="host" 
                                name="host" 
                                value="{{ old('host', 'localhost') }}" 
                                placeholder="localhost"
                            >
                            <small class="form-text text-muted">
                                Default is 'localhost'. Use '%' for any host (not recommended for security).
                            </small>
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea 
                                class="form-control @error('description') is-invalid @enderror" 
                                id="description" 
                                name="description" 
                                rows="3"
                                placeholder="Optional description for this database"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Alert Box -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> This will create a new MySQL database and user with full privileges on the database.
                            Make sure to save the credentials securely.
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('databases.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Create Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
