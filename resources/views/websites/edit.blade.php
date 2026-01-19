@extends('layouts.app')

@section('title', 'Edit Website - Hostiqo')
@section('page-title', 'Edit ' . ucfirst($website->project_type) . ' Website')
@section('page-description', 'Update website configuration')

@section('page-actions')
    <a href="{{ route('websites.index', ['type' => $website->project_type]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Websites
    </a>
@endsection

@section('content')
    @if(in_array(config('app.env'), ['local', 'dev', 'development']))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Development Mode:</strong>
            Configurations will be saved to <code>storage/server/</code> instead of system directories.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('websites.update', $website) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="project_type" value="{{ $website->project_type }}">

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-2"></i> Basic Information
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Website Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $website->name) }}"
                                required
                                placeholder="My Awesome Project"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="domain" class="form-label">
                                Domain Name
                            </label>
                            <input
                                type="text"
                                class="form-control font-monospace"
                                id="domain"
                                value="{{ $website->domain }}"
                                readonly
                                disabled
                                style="background-color: #f8f9fa; cursor: not-allowed;"
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Domain cannot be changed after creation. Delete and recreate if needed.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-folder me-2"></i> Path Configuration
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="root_path" class="form-label">
                                Website Root Path
                            </label>
                            <input
                                type="text"
                                class="form-control font-monospace"
                                id="root_path"
                                value="{{ $website->root_path }}"
                                readonly
                                disabled
                                style="background-color: #f8f9fa; cursor: not-allowed;"
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Root path cannot be changed after creation.
                            </small>
                        </div>

                        @if($website->project_type === 'php')
                            <div class="mb-3">
                                <label for="working_directory" class="form-label">
                                    Working Directory (Document Root)
                                </label>
                                <input
                                    type="text"
                                    class="form-control font-monospace @error('working_directory') is-invalid @enderror"
                                    id="working_directory"
                                    name="working_directory"
                                    value="{{ old('working_directory', $website->working_directory ?? '/') }}"
                                    placeholder="/ or /public or /public_html"
                                >
                                <div class="form-text">
                                    <strong>Relative path</strong> from root path. Examples: <code>/</code> (root), <code>/public</code>, <code>/public_html</code>
                                    <br>Final path: <code>{{ $website->root_path }}{working_directory}</code>
                                </div>
                                @error('working_directory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif($website->project_type === 'reverse-proxy')
                            <div class="mb-3" id="run-opt-field" style="display: {{ $website->runtime === 'Node.js' ? 'block' : 'none' }};">
                                <label for="working_directory" class="form-label">
                                    Run opt
                                </label>
                                <input
                                    type="text"
                                    class="form-control font-monospace @error('working_directory') is-invalid @enderror"
                                    id="working_directory"
                                    name="working_directory"
                                    value="{{ old('working_directory', $website->working_directory) }}"
                                    placeholder="start"
                                >
                                <div class="form-text">Startup mode in package.json (Node.js only)</div>
                                @error('working_directory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>

                @if($website->project_type === 'php' || $website->project_type === 'reverse-proxy')
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-code-slash me-2"></i> 
                        @if($website->project_type === 'php')
                            PHP Configuration
                        @else
                            Reverse Proxy Configuration
                        @endif
                    </div>
                    <div class="card-body">
                        @if($website->project_type === 'php')
                            <div class="mb-3">
                                <label for="php_version" class="form-label">
                                    PHP Version <span class="text-danger">*</span>
                                </label>
                                <select
                                    class="form-select @error('php_version') is-invalid @enderror"
                                    id="php_version"
                                    name="php_version"
                                    required
                                >
                                    <option value="">-- Select PHP Version --</option>
                                    @foreach($phpVersions as $version)
                                        <option value="{{ $version }}" {{ old('php_version', $website->php_version) === $version ? 'selected' : '' }}>
                                            PHP {{ $version }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('php_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif($website->project_type === 'reverse-proxy')
                            <div class="mb-3">
                                <label for="runtime" class="form-label">
                                    Runtime <span class="text-danger">*</span>
                                </label>
                                <select
                                    class="form-select @error('runtime') is-invalid @enderror"
                                    id="runtime"
                                    name="runtime"
                                    required
                                >
                                    <option value="">Select Runtime</option>
                                    <option value="Node.js" {{ old('runtime', $website->runtime) === 'Node.js' ? 'selected' : '' }}>Node.js</option>
                                    <option value="Python" {{ old('runtime', $website->runtime) === 'Python' ? 'selected' : '' }}>Python</option>
                                    <option value="Go" {{ old('runtime', $website->runtime) === 'Go' ? 'selected' : '' }}>Go</option>
                                    <option value="Ruby" {{ old('runtime', $website->runtime) === 'Ruby' ? 'selected' : '' }}>Ruby</option>
                                    <option value="Java" {{ old('runtime', $website->runtime) === 'Java' ? 'selected' : '' }}>Java</option>
                                    <option value="Other" {{ old('runtime', $website->runtime) === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <div class="form-text">Select the runtime/language for your application</div>
                                @error('runtime')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if($website->project_type === 'reverse-proxy')

                            <!-- Port -->
                            <div class="mb-3">
                                <label for="port" class="form-label">
                                    Port
                                </label>
                                <input
                                    type="number"
                                    class="form-control @error('port') is-invalid @enderror"
                                    id="port"
                                    name="port"
                                    value="{{ old('port', $website->port) }}"
                                    placeholder="3000"
                                    min="1"
                                    max="65535"
                                >
                                <div class="form-text">Port where your application will run (Nginx will proxy to this port)</div>
                                @error('port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                @if($website->project_type === 'php')
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-gear me-2"></i> PHP Hardening Settings
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Customize PHP settings for this website. Default security settings will be applied if not specified.
                        </p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="memory_limit" class="form-label">Memory Limit</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="memory_limit"
                                        name="php_settings[memory_limit]"
                                        value="{{ old('php_settings.memory_limit', $website->php_settings['memory_limit'] ?? '256M') }}"
                                        placeholder="256M"
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_execution_time" class="form-label">Max Execution Time (seconds)</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="max_execution_time"
                                        name="php_settings[max_execution_time]"
                                        value="{{ old('php_settings.max_execution_time', $website->php_settings['max_execution_time'] ?? '300') }}"
                                        placeholder="300"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="upload_max_filesize" class="form-label">Upload Max Filesize</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="upload_max_filesize"
                                        name="php_settings[upload_max_filesize]"
                                        value="{{ old('php_settings.upload_max_filesize', $website->php_settings['upload_max_filesize'] ?? '100M') }}"
                                        placeholder="100M"
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="post_max_size" class="form-label">Post Max Size</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="post_max_size"
                                        name="php_settings[post_max_size]"
                                        value="{{ old('php_settings.post_max_size', $website->php_settings['post_max_size'] ?? '100M') }}"
                                        placeholder="100M"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-shield-lock me-1"></i> Enable Dangerous Functions
                                <small class="text-danger">(Security Risk)</small>
                            </label>
                            <div class="form-text mb-2">
                                <strong>All dangerous functions are disabled by default.</strong> Only enable functions if your application specifically requires them.
                            </div>

                            @php
                                $dangerousFunctions = [
                                    'exec' => 'Execute external programs',
                                    'passthru' => 'Execute external programs and display raw output',
                                    'shell_exec' => 'Execute command via shell',
                                    'system' => 'Execute external program and display output',
                                    'proc_open' => 'Open a process file pointer',
                                    'popen' => 'Open process file pointer',
                                    'curl_exec' => 'Execute CURL session',
                                    'curl_multi_exec' => 'Execute CURL multi sessions',
                                    'parse_ini_file' => 'Parse configuration file',
                                    'show_source' => 'Display source code',
                                ];

                                // Get currently disabled functions
                                $currentDisabled = [];
                                if (!empty($website->php_settings['disable_functions'])) {
                                    $currentDisabled = explode(',', $website->php_settings['disable_functions']);
                                } else {
                                    // Default: all dangerous functions disabled
                                    $currentDisabled = array_keys($dangerousFunctions);
                                }

                                // Calculate currently enabled (inverse of disabled)
                                $currentEnabled = array_diff(array_keys($dangerousFunctions), $currentDisabled);
                            @endphp

                            <div class="row">
                                @foreach($dangerousFunctions as $func => $desc)
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="enabled_functions[]"
                                                value="{{ $func }}"
                                                id="func_{{ $func }}"
                                                {{ in_array($func, $currentEnabled) ? 'checked' : '' }}
                                            >
                                            <label class="form-check-label small" for="func_{{ $func }}">
                                                <code>{{ $func }}()</code>
                                                <br><small class="text-muted">{{ $desc }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="open_basedir" class="form-label">
                                <i class="bi bi-folder-lock me-1"></i> Path Isolation (open_basedir)
                                <small class="text-success">(Security Feature)</small>
                            </label>
                            <textarea
                                class="form-control font-monospace"
                                id="open_basedir"
                                name="php_settings[open_basedir]"
                                rows="3"
                                placeholder="{{ $website->root_path }}:/tmp:/usr/share/php:/usr/share/pear"
                            >{{ old('php_settings.open_basedir', $website->php_settings['open_basedir'] ?? '') }}</textarea>
                            <div class="form-text">
                                <strong>Path jail:</strong> Restricts PHP file operations to these directories only (colon-separated).
                                Leave empty for automatic configuration: website root + /tmp + PHP libraries.
                            </div>
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Note:</strong> Changes will be applied when you redeploy the website configuration.
                        </div>
                    </div>
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shield-check me-2"></i> Security & Status
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="ssl_enabled"
                                    name="ssl_enabled"
                                    value="1"
                                    {{ old('ssl_enabled', $website->ssl_enabled) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="ssl_enabled">
                                    Enable Let's Encrypt SSL
                                </label>
                            </div>
                            <div class="form-text">Automatically request Let's Encrypt SSL certificate for HTTPS</div>
                        </div>

                        <!-- WWW Redirect -->
                        <div class="mb-3">
                            <label class="form-label">
                                Redirect Preference
                            </label>
                            <div class="form-text mb-2">Choose how to handle www subdomain traffic</div>

                            <div class="form-check">
                                <input
                                    class="form-check-input @error('www_redirect') is-invalid @enderror"
                                    type="radio"
                                    name="www_redirect"
                                    id="www_redirect_none"
                                    value="none"
                                    {{ old('www_redirect', $website->www_redirect ?? 'none') === 'none' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="www_redirect_none">
                                    No redirect (both www &amp; non-www work)
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input @error('www_redirect') is-invalid @enderror"
                                    type="radio"
                                    name="www_redirect"
                                    id="www_redirect_to_non_www"
                                    value="to_non_www"
                                    {{ old('www_redirect', $website->www_redirect) === 'to_non_www' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="www_redirect_to_non_www">
                                    Redirect www to non-www (www.example.com → example.com)
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input @error('www_redirect') is-invalid @enderror"
                                    type="radio"
                                    name="www_redirect"
                                    id="www_redirect_to_www"
                                    value="to_www"
                                    {{ old('www_redirect', $website->www_redirect) === 'to_www' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="www_redirect_to_www">
                                    Redirect non-www to www (example.com → www.example.com)
                                </label>
                            </div>

                            @error('www_redirect')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', $website->is_active) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="form-text">Mark website as active/inactive</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Update Website
                    </button>
                    <a href="{{ route('websites.index', ['type' => $website->project_type]) }}" class="btn btn-outline-secondary">
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
                    <h6>Configuration Changes</h6>
                    <p class="small">Updating website settings will trigger automatic Nginx configuration redeployment.</p>

                    <h6 class="mt-3">Path Changes</h6>
                    <p class="small">Changing root path or working directory requires redeploying configurations. Make sure the paths exist on the server.</p>

                    <h6 class="mt-3">Version Changes</h6>
                    <p class="small">@if($website->project_type === 'php')Changing PHP version will update the PHP-FPM pool configuration and reload the service.@else Changing Node.js version requires restarting your application via PM2.@endif</p>

                    <h6 class="mt-3">SSL Certificate</h6>
                    <p class="small">Toggle SSL on/off as needed. Use the "Enable SSL" button on the website detail page to request certificates.</p>

                    <h6 class="mt-3">Redeploy</h6>
                    <p class="small">If configurations aren't applying, use the "Redeploy Config" button on the website detail page.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide Run opt field based on runtime selection
    const runtimeSelect = document.getElementById('runtime');
    const runOptField = document.getElementById('run-opt-field');
    
    if (runtimeSelect && runOptField) {
        runtimeSelect.addEventListener('change', function() {
            if (this.value === 'Node.js') {
                runOptField.style.display = 'block';
            } else {
                runOptField.style.display = 'none';
            }
        });
    }
});
</script>
@endpush
