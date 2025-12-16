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
                                Domain Name <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control font-monospace @error('domain') is-invalid @enderror" 
                                id="domain" 
                                name="domain" 
                                value="{{ old('domain') }}" 
                                required
                                placeholder="example.com"
                            >
                            <div class="form-text">The domain name for this website (e.g., example.com or subdomain.example.com)</div>
                            @error('domain')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

                        <!-- Working Directory / Run Opt -->
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
                        @else
                            <div class="mb-3">
                                <label for="working_directory" class="form-label">
                                    Run opt
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control font-monospace @error('working_directory') is-invalid @enderror" 
                                    id="working_directory" 
                                    name="working_directory" 
                                    value="{{ old('working_directory') }}" 
                                    placeholder="start"
                                >
                                <div class="form-text">Startup mode in package.json</div>
                                @error('working_directory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-code-slash me-2"></i> @if($type === 'php') PHP @else Node.js @endif Configuration
                    </div>
                    <div class="card-body">
                        @if($type === 'php')
                            <div class="mb-3">
                                <label for="php_version" class="form-label">
                                    PHP Version
                                </label>
                                <select 
                                    class="form-select @error('php_version') is-invalid @enderror" 
                                    id="php_version" 
                                    name="php_version"
                                >
                                    <option value="">System Default</option>
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
                        @else
                            <div class="mb-3">
                                <label for="node_version" class="form-label">
                                    Node.js Version
                                </label>
                                <select 
                                    class="form-select @error('node_version') is-invalid @enderror" 
                                    id="node_version" 
                                    name="node_version"
                                >
                                    <option value="">System Default</option>
                                    @foreach($nodeVersions as $version)
                                        <option value="{{ $version }}" {{ old('node_version') === $version ? 'selected' : '' }}>
                                            Node.js {{ $version }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the Node.js version for this website</div>
                                @error('node_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

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
                                    value="{{ old('port') }}" 
                                    placeholder="3000"
                                    min="1"
                                    max="65535"
                                >
                                <div class="form-text">Port where your Node.js application will run (Nginx will proxy to this port)</div>
                                @error('port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>

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
document.addEventListener('DOMContentLoaded', function() {
    const domainInput = document.getElementById('domain');
    const rootPathInput = document.getElementById('root_path');
    let manuallyEdited = false;

    // Track if user manually edited root path
    rootPathInput.addEventListener('input', function() {
        if (this.value !== '') {
            manuallyEdited = true;
        }
    });

    // Auto-generate root path from domain
    domainInput.addEventListener('input', function() {
        // Only auto-generate if user hasn't manually edited the root path
        if (!manuallyEdited || rootPathInput.value === '') {
            let domain = this.value.trim();
            
            if (domain) {
                // Remove www. prefix if exists
                domain = domain.replace(/^www\./, '');
                
                // Replace dots with underscores
                const path = domain.replace(/\./g, '_');
                
                // Generate full path
                rootPathInput.value = '/var/www/' + path;
            } else {
                rootPathInput.value = '';
            }
        }
    });

    // Reset manual edit flag when root path is cleared
    rootPathInput.addEventListener('keydown', function(e) {
        if ((e.key === 'Backspace' || e.key === 'Delete') && this.value.length <= 1) {
            manuallyEdited = false;
        }
    });
});
</script>
@endpush
