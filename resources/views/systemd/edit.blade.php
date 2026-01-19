@extends('layouts.app')

@section('title', 'Edit Systemd Service - Hostiqo')
@section('page-title', 'Systemd Services')
@section('page-description', 'Edit ' . $systemd->name)

@section('page-actions')
    <a href="{{ route('systemd.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('systemd.update', $systemd) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-2"></i> Basic Information
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Service Name</label>
                            <input type="text" class="form-control" value="{{ $systemd->name }}" disabled>
                            <small class="form-text text-muted">Service name cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                   id="description" name="description" value="{{ old('description', $systemd->description) }}" required>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-terminal me-2"></i> Service Configuration
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="exec_start" class="form-label">ExecStart Command <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace @error('exec_start') is-invalid @enderror" 
                                   id="exec_start" name="exec_start" value="{{ old('exec_start', $systemd->exec_start) }}" required>
                            @error('exec_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="working_directory" class="form-label">Working Directory <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace @error('working_directory') is-invalid @enderror" 
                                   id="working_directory" name="working_directory" value="{{ old('working_directory', $systemd->working_directory) }}" required>
                            @error('working_directory')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="user" class="form-label">User <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('user') is-invalid @enderror" 
                                       id="user" name="user" value="{{ old('user', $systemd->user) }}" required>
                                @error('user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="simple" {{ old('type', $systemd->type) === 'simple' ? 'selected' : '' }}>Simple</option>
                                    <option value="forking" {{ old('type', $systemd->type) === 'forking' ? 'selected' : '' }}>Forking</option>
                                    <option value="oneshot" {{ old('type', $systemd->type) === 'oneshot' ? 'selected' : '' }}>Oneshot</option>
                                    <option value="notify" {{ old('type', $systemd->type) === 'notify' ? 'selected' : '' }}>Notify</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="restart" class="form-label">Restart <span class="text-danger">*</span></label>
                                <select class="form-select @error('restart') is-invalid @enderror" id="restart" name="restart" required>
                                    <option value="always" {{ old('restart', $systemd->restart) === 'always' ? 'selected' : '' }}>Always</option>
                                    <option value="on-failure" {{ old('restart', $systemd->restart) === 'on-failure' ? 'selected' : '' }}>On Failure</option>
                                    <option value="on-abnormal" {{ old('restart', $systemd->restart) === 'on-abnormal' ? 'selected' : '' }}>On Abnormal</option>
                                    <option value="no" {{ old('restart', $systemd->restart) === 'no' ? 'selected' : '' }}>No</option>
                                </select>
                                @error('restart')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="restart_sec" class="form-label">Restart Delay (seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('restart_sec') is-invalid @enderror" 
                                   id="restart_sec" name="restart_sec" value="{{ old('restart_sec', $systemd->restart_sec) }}" required min="0">
                            @error('restart_sec')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="environment" class="form-label">Environment Variables</label>
                            <textarea class="form-control font-monospace @error('environment') is-invalid @enderror" 
                                      id="environment" name="environment" rows="4">{{ old('environment', $systemd->environment) }}</textarea>
                            @error('environment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="standard_output" class="form-label">Standard Output <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('standard_output') is-invalid @enderror" 
                                       id="standard_output" name="standard_output" value="{{ old('standard_output', $systemd->standard_output) }}" required>
                                @error('standard_output')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="standard_error" class="form-label">Standard Error <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('standard_error') is-invalid @enderror" 
                                       id="standard_error" name="standard_error" value="{{ old('standard_error', $systemd->standard_error) }}" required>
                                @error('standard_error')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', $systemd->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Deploy Service
                                <small class="text-muted d-block">Enable and start service after update</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Update Service
                        </button>
                        <a href="{{ route('systemd.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i> Service Info
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>Service File:</strong></p>
                    <code class="small d-block bg-white p-2 rounded mb-3">{{ $systemd->service_file_path }}</code>

                    <p class="small mb-2"><strong>Current Status:</strong></p>
                    <span class="badge bg-{{ $systemd->status_badge }}">{{ ucfirst($systemd->status ?? 'unknown') }}</span>

                    <hr>

                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Changes will redeploy the service configuration and restart the service if active.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
