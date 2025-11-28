@extends('layouts.app')

@section('title', 'Change Password - ' . $database->name)
@section('page-title', 'Change Database Password')
@section('page-description', 'Update password for ' . $database->username)

@section('content')
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <!-- Database Info -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Database:</strong> <code>{{ $database->name }}</code><br>
                        <strong>Username:</strong> <code>{{ $database->username }}</code><br>
                        <strong>Host:</strong> {{ $database->host }}
                    </div>

                    <form action="{{ route('databases.update-password', $database) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="password" 
                                class="form-control @error('password') is-invalid @enderror" 
                                id="password" 
                                name="password" 
                                required
                                minlength="8"
                                placeholder="Enter new password"
                            >
                            <small class="form-text text-muted">
                                Minimum 8 characters. Use a strong password with letters, numbers, and symbols.
                            </small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                required
                                minlength="8"
                                placeholder="Confirm new password"
                            >
                        </div>

                        <!-- Warning Alert -->
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Changing the password will affect all applications using this database. 
                            Make sure to update the password in all application configurations.
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('databases.show', $database) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-1"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
