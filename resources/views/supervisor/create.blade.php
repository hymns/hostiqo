@extends('layouts.app')

@section('title', 'Create Supervisor Program - Hostiqo')
@section('page-title', 'Supervisor Programs')
@section('page-description', 'Create a new long-running process')

@section('page-actions')
    <a href="{{ route('supervisor.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('supervisor.store') }}" method="POST">
            @csrf
            
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i> Basic Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Program Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required
                               placeholder="laravel-queue"
                               pattern="[a-zA-Z0-9_-]+">
                        <small class="form-text text-muted">Use only letters, numbers, hyphens, and underscores</small>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" 
                               class="form-control @error('description') is-invalid @enderror" 
                               id="description" 
                               name="description" 
                               value="{{ old('description') }}"
                               placeholder="Laravel Queue Worker">
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="command" class="form-label">Command <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control font-monospace @error('command') is-invalid @enderror" 
                               id="command" 
                               name="command" 
                               value="{{ old('command') }}" 
                               required
                               placeholder="/usr/bin/php8.3 artisan queue:work --tries=3">
                        <small class="form-text text-muted">Full command to execute. Use absolute paths.</small>
                        @error('command')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="directory" class="form-label">Working Directory <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control font-monospace @error('directory') is-invalid @enderror" 
                               id="directory" 
                               name="directory" 
                               value="{{ old('directory') }}" 
                               required
                               placeholder="/var/www/myapp">
                        <small class="form-text text-muted">Directory where command will be executed</small>
                        @error('directory')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-gear me-2"></i> Process Configuration
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="numprocs" class="form-label">Number of Processes <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('numprocs') is-invalid @enderror" 
                                   id="numprocs" 
                                   name="numprocs" 
                                   value="{{ old('numprocs', 1) }}" 
                                   min="1" 
                                   max="20" 
                                   required>
                            <small class="form-text text-muted">How many instances to run</small>
                            @error('numprocs')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="user" class="form-label">Run as User <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('user') is-invalid @enderror" 
                                   id="user" 
                                   name="user" 
                                   value="{{ old('user', 'www-data') }}" 
                                   required>
                            @error('user')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="startsecs" class="form-label">Start Seconds <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('startsecs') is-invalid @enderror" 
                                   id="startsecs" 
                                   name="startsecs" 
                                   value="{{ old('startsecs', 1) }}" 
                                   min="0" 
                                   required>
                            <small class="form-text text-muted">Seconds to stay running before considered started</small>
                            @error('startsecs')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="stopwaitsecs" class="form-label">Stop Wait Seconds <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('stopwaitsecs') is-invalid @enderror" 
                                   id="stopwaitsecs" 
                                   name="stopwaitsecs" 
                                   value="{{ old('stopwaitsecs', 10) }}" 
                                   min="1" 
                                   required>
                            <small class="form-text text-muted">Seconds to wait before killing process</small>
                            @error('stopwaitsecs')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="autostart" value="0">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="autostart" 
                               name="autostart" 
                               value="1" 
                               {{ old('autostart', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="autostart">
                            Auto Start
                            <small class="text-muted d-block">Start automatically when supervisor starts</small>
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="autorestart" value="0">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="autorestart" 
                               name="autorestart" 
                               value="1" 
                               {{ old('autorestart', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="autorestart">
                            Auto Restart
                            <small class="text-muted d-block">Automatically restart if process exits</small>
                        </label>
                    </div>

                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1" 
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                            <small class="text-muted d-block">Deploy to supervisor immediately</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-file-text me-2"></i> Logging (Optional)
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="stdout_logfile" class="form-label">Log File Path</label>
                        <input type="text" 
                               class="form-control font-monospace @error('stdout_logfile') is-invalid @enderror" 
                               id="stdout_logfile" 
                               name="stdout_logfile" 
                               value="{{ old('stdout_logfile') }}"
                               placeholder="/var/log/supervisor/program-name.log">
                        <small class="form-text text-muted">Leave empty to use default: /var/log/supervisor/{program-name}.log</small>
                        @error('stdout_logfile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Create Program
                </button>
                <a href="{{ route('supervisor.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-header">
                <i class="bi bi-lightbulb me-2"></i> Quick Examples
            </div>
            <div class="card-body">
                <h6>Laravel Queue Worker</h6>
                <p class="small mb-2"><strong>Command:</strong></p>
                <code class="small d-block bg-white p-2 rounded mb-3">/usr/bin/php8.3 artisan queue:work --tries=3</code>

                <h6>Laravel Scheduler</h6>
                <p class="small mb-2"><strong>Command:</strong></p>
                <code class="small d-block bg-white p-2 rounded mb-3">/usr/bin/php8.3 artisan schedule:work</code>

                <h6>Custom Script</h6>
                <p class="small mb-2"><strong>Command:</strong></p>
                <code class="small d-block bg-white p-2 rounded">/usr/bin/python3 /path/to/script.py</code>
            </div>
        </div>
    </div>
</div>

@endsection
