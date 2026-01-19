@extends('layouts.app')

@section('title', 'Create Systemd Service - Hostiqo')
@section('page-title', 'Systemd Services')
@section('page-description', 'Create a new systemd service')

@section('page-actions')
    <a href="{{ route('systemd.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('systemd.store') }}" method="POST">
                @csrf

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-2"></i> Basic Information
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required
                                   placeholder="myapp" pattern="[a-zA-Z0-9_-]+">
                            <small class="form-text text-muted">Alphanumeric, hyphens, and underscores only</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                   id="description" name="description" value="{{ old('description') }}" required
                                   placeholder="My Application Service">
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
                                   id="exec_start" name="exec_start" value="{{ old('exec_start') }}" required
                                   placeholder="/usr/bin/python3 /var/www/myapp/app.py">
                            <small class="form-text text-muted">Full command to execute. Use absolute paths.</small>
                            @error('exec_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="working_directory" class="form-label">Working Directory <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace @error('working_directory') is-invalid @enderror" 
                                   id="working_directory" name="working_directory" value="{{ old('working_directory') }}" required
                                   placeholder="/var/www/myapp">
                            <small class="form-text text-muted">Directory where command will be executed</small>
                            @error('working_directory')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="user" class="form-label">User <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('user') is-invalid @enderror" 
                                       id="user" name="user" value="{{ old('user', 'www-data') }}" required>
                                @error('user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="simple" {{ old('type', 'simple') === 'simple' ? 'selected' : '' }}>Simple</option>
                                    <option value="forking" {{ old('type') === 'forking' ? 'selected' : '' }}>Forking</option>
                                    <option value="oneshot" {{ old('type') === 'oneshot' ? 'selected' : '' }}>Oneshot</option>
                                    <option value="notify" {{ old('type') === 'notify' ? 'selected' : '' }}>Notify</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="restart" class="form-label">Restart <span class="text-danger">*</span></label>
                                <select class="form-select @error('restart') is-invalid @enderror" id="restart" name="restart" required>
                                    <option value="always" {{ old('restart', 'always') === 'always' ? 'selected' : '' }}>Always</option>
                                    <option value="on-failure" {{ old('restart') === 'on-failure' ? 'selected' : '' }}>On Failure</option>
                                    <option value="on-abnormal" {{ old('restart') === 'on-abnormal' ? 'selected' : '' }}>On Abnormal</option>
                                    <option value="no" {{ old('restart') === 'no' ? 'selected' : '' }}>No</option>
                                </select>
                                @error('restart')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="restart_sec" class="form-label">Restart Delay (seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('restart_sec') is-invalid @enderror" 
                                   id="restart_sec" name="restart_sec" value="{{ old('restart_sec', 10) }}" required min="0">
                            <small class="form-text text-muted">Seconds to wait before restarting</small>
                            @error('restart_sec')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="environment" class="form-label">Environment Variables</label>
                            <textarea class="form-control font-monospace @error('environment') is-invalid @enderror" 
                                      id="environment" name="environment" rows="4" 
                                      placeholder="PORT=8000&#10;PYTHONUNBUFFERED=1&#10;DEBUG=False">{{ old('environment') }}</textarea>
                            <small class="form-text text-muted">One per line: KEY=value</small>
                            @error('environment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="standard_output" class="form-label">Standard Output <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('standard_output') is-invalid @enderror" 
                                       id="standard_output" name="standard_output" value="{{ old('standard_output', 'journal') }}" required>
                                @error('standard_output')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="standard_error" class="form-label">Standard Error <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('standard_error') is-invalid @enderror" 
                                       id="standard_error" name="standard_error" value="{{ old('standard_error', 'journal') }}" required>
                                @error('standard_error')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Deploy Service
                                <small class="text-muted d-block">Enable and start service immediately</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Create Service
                        </button>
                        <a href="{{ route('systemd.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i> Quick Tips
                </div>
                <div class="card-body">
                    <h6>Service Types</h6>
                    <ul class="small">
                        <li><strong>Simple:</strong> Main process doesn't fork</li>
                        <li><strong>Forking:</strong> Process forks and parent exits</li>
                        <li><strong>Oneshot:</strong> Process exits after completion</li>
                        <li><strong>Notify:</strong> Process sends notification when ready</li>
                    </ul>

                    <h6 class="mt-3">Restart Policies</h6>
                    <ul class="small">
                        <li><strong>Always:</strong> Always restart on exit</li>
                        <li><strong>On Failure:</strong> Restart only on failure</li>
                        <li><strong>On Abnormal:</strong> Restart on abnormal exit</li>
                        <li><strong>No:</strong> Never restart</li>
                    </ul>

                    <h6 class="mt-3">Example Commands</h6>
                    <p class="small mb-1"><strong>Python:</strong></p>
                    <code class="small d-block bg-white p-2 rounded mb-2" style="white-space: pre-wrap;">/usr/bin/python3 /var/www/app/app.py</code>

                    <p class="small mb-1"><strong>Go:</strong></p>
                    <code class="small d-block bg-white p-2 rounded mb-2" style="white-space: pre-wrap;">/var/www/app/myapp</code>

                    <p class="small mb-1"><strong>Ruby:</strong></p>
                    <code class="small d-block bg-white p-2 rounded" style="white-space: pre-wrap;">/usr/bin/ruby /var/www/app/app.rb</code>
                </div>
            </div>
        </div>
    </div>
@endsection
