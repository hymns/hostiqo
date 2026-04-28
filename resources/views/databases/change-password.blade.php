@extends('layouts.app')

@section('title', 'Change Password - ' . $database->name)
@section('page-title', 'Change Database Password')
@section('page-description', 'Update password for ' . $database->username)

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
                <strong>Database:</strong> <code>{{ $database->name }}</code><br>
                <strong>Username:</strong> <code>{{ $database->username }}</code><br>
                <strong>Host:</strong> {{ $database->host }}
            </div>

            <form action="{{ route('databases.' . ($type ?? 'mysql') . '.update-password', $database) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-key me-2"></i> New Password
                    </div>
                    <div class="card-body">
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
                            <div class="form-text">Minimum 8 characters. Use a strong password with letters, numbers, and symbols.</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

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
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Changing the password will affect all applications using this database.
                    Make sure to update the password in all application configurations.
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-key me-1"></i> Change Password
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
                    <h6>Password Security</h6>
                    <p class="small">Use a strong, unique password. Combine uppercase, lowercase, numbers, and special characters.</p>

                    <h6 class="mt-3">Update Applications</h6>
                    <p class="small">After changing the password, update all applications that use this database:</p>
                    <ul class="small">
                        <li>Update <code>.env</code> file</li>
                        <li>Clear application cache</li>
                        <li>Restart services if needed</li>
                    </ul>

                    <h6 class="mt-3">Connection String</h6>
                    <p class="small">Typical Laravel format:</p>
                    <code class="small">DB_DATABASE={{ $database->name }}<br>DB_USERNAME={{ $database->username }}<br>DB_PASSWORD=your_new_password</code>

                    <h6 class="mt-3">Testing</h6>
                    <p class="small">After updating, test your application to ensure database connectivity works with the new password.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
