@extends('layouts.app')

@section('title', 'Add Website - Hostiqo')
@section('page-title', 'Add New ' . ucfirst($type) . ' Website')
@section('page-description', 'Configure a new ' . $type . ' virtual host')

@section('page-actions')
    <a href="{{ route('websites.index', ['type' => $type]) }}" class="btn btn-outline-secondary">
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
            <form action="{{ route('websites.store') }}" method="POST" id="websiteForm">
                @csrf
                <input type="hidden" name="project_type" value="{{ $type }}">

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
                                value="{{ old('name') }}"
                                required
                                placeholder="My Awesome Project"
                            >
                            <div class="form-text">A friendly name for your website</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Domain -->
                        <div class="mb-3">
                            <label for="domain" class="form-label">
                                Domain Name @if($type !== 'backend')<span class="text-danger">*</span>@endif
                            </label>
                            <input
                                type="text"
                                class="form-control font-monospace @error('domain') is-invalid @enderror"
                                id="domain"
                                name="domain"
                                value="{{ old('domain') }}"
                                @if($type !== 'backend') required @endif
                                placeholder="@if($type === 'backend')api.example.com (optional)@else example.com @endif"
                            >
                            <div class="form-text">
                                @if($type === 'backend')
                                    Optional: Set domain for nginx proxy (e.g., api.example.com). Leave empty if backend runs on port only.
                                @else
                                    The domain name for this website (e.g., example.com or subdomain.example.com)
                                @endif
                            </div>
                            @error('domain')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                @if($type === 'php' || $type === 'backend')
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-code-slash me-2"></i> 
                        @if($type === 'php')
                            PHP Configuration
                        @else
                            Backend Configuration
                        @endif
                    </div>
                    <div class="card-body">
                        @if($type === 'php')
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
                                        <option value="{{ $version }}" {{ old('php_version') === $version ? 'selected' : '' }}>
                                            PHP {{ $version }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the PHP version for this website</div>
                                @error('php_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif($type === 'backend')
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
                                    <option value="Node.js" {{ old('runtime') === 'Node.js' ? 'selected' : '' }}>Node.js</option>
                                    <option value="Python" {{ old('runtime') === 'Python' ? 'selected' : '' }}>Python</option>
                                    <option value="Go" {{ old('runtime') === 'Go' ? 'selected' : '' }}>Go</option>
                                    <option value="Ruby" {{ old('runtime') === 'Ruby' ? 'selected' : '' }}>Ruby</option>
                                    <option value="Java" {{ old('runtime') === 'Java' ? 'selected' : '' }}>Java</option>
                                    <option value="Other" {{ old('runtime') === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <div class="form-text">Select the runtime/language for your application</div>
                                @error('runtime')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Node.js Version (shown when Node.js runtime selected) -->
                            <div class="mb-3" id="node-version-field" style="display: {{ old('runtime') === 'Node.js' ? 'block' : 'none' }};">
                                <label for="node_version" class="form-label">
                                    Node.js Version
                                </label>
                                <select
                                    class="form-select @error('node_version') is-invalid @enderror"
                                    id="node_version"
                                    name="node_version"
                                >
                                    <option value="">-- Select Node.js Version --</option>
                                    @foreach($nodeVersions as $version)
                                        <option value="{{ $version }}" {{ old('node_version', $nodeVersions[0] ?? '') === $version ? 'selected' : '' }}>
                                            Node.js {{ $version }} LTS
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the Node.js version for this application</div>
                                @error('node_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Port -->
                            <div class="mb-3">
                                <label for="port" class="form-label">
                                    Port <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    class="form-control @error('port') is-invalid @enderror"
                                    id="port"
                                    name="port"
                                    value="{{ old('port') }}"
                                    placeholder="3000"
                                    min="1"
                                    max="65535"
                                    required
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
                                class="form-control font-monospace @error('root_path') is-invalid @enderror"
                                id="root_path"
                                name="root_path"
                                value="{{ old('root_path') }}"
                                placeholder="/var/www/example_com"
                            >
                            <div class="form-text">Leave empty to auto-generate from domain name (e.g., /var/www/example_com)</div>
                            @error('root_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($type === 'php')
                            <div class="mb-3">
                                <label for="working_directory" class="form-label">
                                    Working Directory (Document Root)
                                </label>
                                <input
                                    type="text"
                                    class="form-control font-monospace @error('working_directory') is-invalid @enderror"
                                    id="working_directory"
                                    name="working_directory"
                                    value="{{ old('working_directory', '/') }}"
                                    placeholder="/ or /public or /public_html"
                                >
                                <div class="form-text">
                                    <strong>Relative path</strong> from root path. Examples: <code>/</code> (root), <code>/public</code>, <code>/public_html</code>
                                    <br>Final path: <code>{root_path}{working_directory}</code>
                                </div>
                                @error('working_directory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif($type === 'backend')
                            <p class="text-muted mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Backend applications don't need a webroot directory. They run on a port and nginx proxies requests to that port.
                            </p>
                        @endif
                    </div>
                </div>

                @if($type === 'static')
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-arrow-left-right me-2"></i> API Proxy (Optional)
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="enable_api_proxy"
                                    name="enable_api_proxy"
                                    value="1"
                                    {{ old('enable_api_proxy') ? 'checked' : '' }}
                                    onchange="document.getElementById('api-proxy-fields').style.display = this.checked ? 'block' : 'none';"
                                >
                                <label class="form-check-label" for="enable_api_proxy">
                                    Enable API Proxy
                                </label>
                            </div>
                            <div class="form-text">Proxy API requests to a backend service running on a different port</div>
                        </div>

                        <div id="api-proxy-fields" style="display: {{ old('enable_api_proxy') ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label for="api_proxy_path" class="form-label">
                                    API Path
                                </label>
                                <input
                                    type="text"
                                    class="form-control font-monospace @error('api_proxy_path') is-invalid @enderror"
                                    id="api_proxy_path"
                                    name="api_proxy_path"
                                    value="{{ old('api_proxy_path', '/api') }}"
                                    placeholder="/api"
                                >
                                <div class="form-text">Path to proxy (e.g., /api, /graphql). Requests to this path will be forwarded to the backend port.</div>
                                @error('api_proxy_path')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="api_proxy_port" class="form-label">
                                    Backend Port <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    class="form-control @error('api_proxy_port') is-invalid @enderror"
                                    id="api_proxy_port"
                                    name="api_proxy_port"
                                    value="{{ old('api_proxy_port', '3000') }}"
                                    placeholder="3000"
                                    min="1"
                                    max="65535"
                                >
                                <div class="form-text">Port where your backend API is running (e.g., 3000 for Node.js, 8000 for Python)</div>
                                @error('api_proxy_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-signpost-split me-2"></i> SPA Fallback (Optional)
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="spa_fallback"
                                    name="spa_fallback"
                                    value="1"
                                    {{ old('spa_fallback') ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="spa_fallback">
                                    Enable SPA Fallback
                                </label>
                            </div>
                            <div class="form-text">For single-page apps (React, Vue, Angular). Unknown routes fall back to index.html instead of returning 404.</div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shield-check me-2"></i> Security & Status
                    </div>
                    <div class="card-body">
                        @if($type !== 'backend')
                        <div id="ssl-section">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="ssl_enabled"
                                    name="ssl_enabled"
                                    value="1"
                                    {{ old('ssl_enabled') ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="ssl_enabled">
                                    Enable Let's Encrypt SSL
                                </label>
                            </div>
                            <div class="form-text">Automatically request Let's Encrypt SSL certificate for HTTPS. You can enable this later from the website detail page.</div>
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
                                    {{ old('www_redirect', 'none') === 'none' ? 'checked' : '' }}
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
                                    {{ old('www_redirect') === 'to_non_www' ? 'checked' : '' }}
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
                                    {{ old('www_redirect') === 'to_www' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="www_redirect_to_www">
                                    Redirect non-www to www (example.com → www.example.com)
                                </label>
                            </div>

                            @error('www_redirect')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        </div>
                        @endif

                        <!-- Active Status -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
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
                        <i class="bi bi-check-circle me-1"></i> Create Website
                    </button>
                    <a href="{{ route('websites.index', ['type' => $type]) }}" class="btn btn-outline-secondary">
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
                    @if($type === 'php')
                        <h6>PHP Websites</h6>
                        <p class="small">For Laravel, set working directory to <code>/public</code>. For WordPress, use <code>/</code> (root).</p>

                        <h6 class="mt-3">PHP-FPM Pool</h6>
                        <p class="small">Each website gets its own PHP-FPM pool for better resource isolation and performance.</p>
                    @else
                        <h6>Node.js Applications</h6>
                        <p class="small">Nginx will act as a reverse proxy to your Node.js application running on the specified port.</p>

                        <h6 class="mt-3">PM2 Process Manager</h6>
                        <p class="small">Your Node.js app will be managed by PM2 for auto-restart, logging, and monitoring.</p>
                    @endif

                    <h6 class="mt-3">Auto-Generated Paths</h6>
                    <p class="small">If you leave the root path empty, it will be auto-generated as <code>/var/www/domain_name</code></p>

                    <h6 class="mt-3">SSL Certificate</h6>
                    <p class="small">Enable SSL during creation or later. Let's Encrypt certificates are automatically renewed every 90 days.</p>

                    <h6 class="mt-3">Cloudflare DNS</h6>
                    <p class="small">After creating the website, use the DNS sync button in the website list to create DNS A records automatically.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function() {
    var $domainInput = $('#domain');
    var $rootPathInput = $('#root_path');
    var manuallyEdited = false;

    // Track if user manually edited root path
    $rootPathInput.on('input', function() {
        if ($(this).val() !== '') {
            manuallyEdited = true;
        }
    });

    // Auto-generate root path from domain
    $domainInput.on('input', function() {
        // Only auto-generate if user hasn't manually edited the root path
        if (!manuallyEdited || $rootPathInput.val() === '') {
            var domain = $(this).val().trim();

            if (domain) {
                // Remove www. prefix if exists
                domain = domain.replace(/^www\./, '');

                // Replace dots with underscores
                var path = domain.replace(/\./g, '_');

                // Generate full path
                $rootPathInput.val('/var/www/' + path);
            } else {
                $rootPathInput.val('');
            }
        }
    });

    // Reset manual edit flag when root path is cleared
    $rootPathInput.on('keydown', function(e) {
        if ((e.key === 'Backspace' || e.key === 'Delete') && $(this).val().length <= 1) {
            manuallyEdited = false;
        }
    });

    // Show/hide Node.js version field based on runtime selection
    const runtimeSelect = document.getElementById('runtime');
    const nodeVersionField = document.getElementById('node-version-field');

    if (runtimeSelect && nodeVersionField) {
        runtimeSelect.addEventListener('change', function() {
            nodeVersionField.style.display = this.value === 'Node.js' ? 'block' : 'none';
        });
    }

    // For backend: Show/hide SSL section based on domain input
    const urlParams = new URLSearchParams(window.location.search);
    const projectType = urlParams.get('type');
    
    if (projectType === 'backend') {
        const domainInput = document.getElementById('domain');
        const sslSection = document.getElementById('ssl-section');
        
        function toggleSslSection() {
            const hasDomain = domainInput && domainInput.value.trim() !== '';
            if (sslSection) {
                sslSection.style.display = hasDomain ? 'block' : 'none';
            }
        }
        
        if (domainInput) {
            // Initial check
            toggleSslSection();
            
            // Listen for changes
            domainInput.addEventListener('input', toggleSslSection);
            domainInput.addEventListener('change', toggleSslSection);
        }
    }
});
</script>
@endpush
