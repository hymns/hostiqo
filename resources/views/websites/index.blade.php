@extends('layouts.app')

@section('title', 'Websites - Hostiqo')
@section('page-title', 'Websites')
@section('page-description', 'Manage PHP and Node.js website configurations')

@section('page-actions')
    @if($type !== 'deployment')
        <a href="{{ route('websites.create', ['type' => $type]) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add {{ ucfirst($type) }} Website
        </a>
    @endif
@endsection

@section('content')
    <!-- Tabs -->
    <div class="tabs-container">
        <a href="{{ route('websites.index', ['type' => 'php']) }}" 
           class="tab-btn text-decoration-none {{ $type === 'php' ? 'active' : '' }}">
            <i class="bi bi-code-slash me-1"></i> PHP Projects
        </a>
        <a href="{{ route('websites.index', ['type' => 'node']) }}" 
           class="tab-btn text-decoration-none {{ $type === 'node' ? 'active' : '' }}">
            <i class="bi bi-hexagon me-1"></i> Node Projects
        </a>
        <a href="{{ route('websites.index', ['type' => 'deployment']) }}" 
           class="tab-btn text-decoration-none {{ $type === 'deployment' ? 'active' : '' }}">
            <i class="bi bi-rocket-takeoff me-1"></i> 1-Click Deployment
        </a>
    </div>

    @if($type === 'deployment')
        <!-- 1-Click Deployment Apps Selection -->
        <div class="row g-4 mb-4">
            <!-- WordPress Card -->
            <div class="col-md-4">
                <div class="app-card" data-bs-toggle="modal" data-bs-target="#wordpressModal">
                    <div class="app-card-icon">
                        <i class="bi bi-wordpress"></i>
                    </div>
                    <h5 class="app-card-title">WordPress</h5>
                    <p class="app-card-desc">Popular CMS with FastCGI cache & security</p>
                    <div class="app-card-badge">
                        <span class="badge bg-success">Available</span>
                    </div>
                </div>
            </div>

            <!-- Drupal Card (Coming Soon) -->
            <div class="col-md-4">
                <div class="app-card app-card-disabled">
                    <div class="app-card-icon">
                        <i class="bi bi-droplet"></i>
                    </div>
                    <h5 class="app-card-title">Drupal</h5>
                    <p class="app-card-desc">Enterprise-grade CMS platform</p>
                    <div class="app-card-badge">
                        <span class="badge bg-secondary">Coming Soon</span>
                    </div>
                </div>
            </div>

            <!-- More Apps Card -->
            <div class="col-md-4">
                <div class="app-card app-card-disabled">
                    <div class="app-card-icon">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </div>
                    <h5 class="app-card-title">Other Apps</h5>
                    <p class="app-card-desc">Joomla & more</p>
                    <div class="app-card-badge">
                        <span class="badge bg-secondary">Coming Soon</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deployed Sites List -->
        <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>Deployed Sites</h5>
        @if($websites->isEmpty())
            <div class="website-card">
                <div class="text-center py-5" style="padding: 3rem 1.5rem;">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-4">No sites deployed yet</h4>
                    <p class="text-muted">Click on an app card above to deploy your first site!</p>
                </div>
            </div>
        @endif
    @elseif($websites->isEmpty())
        <div class="website-card">
            <div class="text-center py-5" style="padding: 3rem 1.5rem;">
                <i class="bi bi-globe text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-4">No {{ ucfirst($type) }} websites yet</h4>
                <p class="text-muted">Create your first {{ $type }} website to get started.</p>
                <a href="{{ route('websites.create', ['type' => $type]) }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-1"></i> Add {{ ucfirst($type) }} Website
                </a>
            </div>
        </div>
    @endif
    
    @if(!$websites->isEmpty())
        @foreach($websites as $website)
            <div class="website-card">
                <!-- Card Header -->
                <div class="website-card-header" onclick="toggleCard({{ $website->id }})">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-start gap-3" style="flex: 1;">
                            <i class="bi bi-chevron-right chevron-icon" id="chevron-{{ $website->id }}" 
                               style="font-size: 1.25rem; color: #9ca3af; margin-top: 0.25rem;"></i>
                            <div class="website-icon">
                                <i class="bi bi-globe"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="website-name">                                    
                                    {{ $website->name }} <span class="status-dot {{ $website->is_active ? 'active' : 'inactive' }}"></span>
                                    @if($website->ssl_status === 'active')
                                        <span class="badge" style="background: #10b981; font-size: 0.75rem;"><i class="bi bi-lock-fill"></i> SSL</span>                                    
                                    @endif
                                </div>
                                <a href="http://{{ $website->domain }}" target="_blank" class="website-domain">
                                    {{ $website->domain }}
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
                                    <span class="badge badge-pastel-purple">PHP {{ $website->php_version }}</span>
                                </a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="deploy-btn" 
                                    onclick="event.stopPropagation(); redeployWebsite({{ $website->id }})">
                                <i class="bi bi-rocket-takeoff-fill me-2"></i> Redeploy
                            </button>
                            <div class="dropdown" onclick="event.stopPropagation();">
                                <button class="btn btn-link text-dark p-0" type="button" 
                                        data-bs-toggle="dropdown" style="font-size: 1.25rem;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('websites.show', $website) }}">
                                        <i class="bi bi-search me-2"></i>View Details
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('websites.edit', $website) }}">
                                        <i class="bi bi-pencil me-2"></i>Edit
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" 
                                           onclick="event.preventDefault(); confirmDelete('Delete {{ $website->name }}? This action cannot be undone!').then(confirmed => { if(confirmed) document.getElementById('delete-form-{{ $website->id }}').submit(); });">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Body (Collapsible) -->
                <div id="card-body-{{ $website->id }}" style="display: none;">
                    <div class="website-card-body">
                        <!-- Configuration Section -->
                        <div class="section-label">CONFIGURATION</div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-code-slash"></i> Type</span>
                            <span class="info-value">{{ $website->project_type === 'php' ? 'PHP' : 'Node.js' }}</span>
                        </div>
                        @if($website->project_type === 'php')
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-gear"></i> PHP Version</span>
                                <span class="info-value">{{ $website->php_version ?? 'System Default' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-layers"></i> FPM Pool</span>
                                <span class="info-value">{{ $website->php_pool_name ?? 'www' }}</span>
                            </div>
                        @else
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-hexagon"></i> Node Version</span>
                                <span class="info-value">{{ $website->node_version ?? '18.x' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-ethernet"></i> Port</span>
                                <span class="info-value">{{ $website->port ?? '3000' }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-folder"></i> Root Path</span>
                            <span class="info-value"><code>{{ $website->root_path }}</code></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-folder-symlink"></i> Working Directory</span>
                            <span class="info-value"><code>{{ $website->working_directory ?? '/' }}</code></span>
                        </div>

                        <!-- Services Section -->
                        <div class="section-label">SERVICES</div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-hexagon"></i> Nginx</span>
                            <span class="badge badge-md badge-pastel-{{ $website->nginx_status === 'active' ? 'green' : ($website->nginx_status === 'pending' ? 'yellow' : 'red') }}">
                                {{ ucfirst($website->nginx_status) }}
                            </span>
                        </div>
                        @if($website->ssl_enabled)
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-shield-lock"></i> SSL/TLS</span>
                                <span class="badge badge-md badge-pastel-{{ $website->ssl_status === 'active' ? 'green' : ($website->ssl_status === 'pending' ? 'yellow' : 'red') }}">
                                    {{ ucfirst($website->ssl_status) }}
                                </span>
                            </div>
                        @endif
                        @if(config('services.cloudflare.enabled') && $website->dns_status !== 'none')
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-cloud"></i> CloudFlare DNS</span>
                                <span class="info-value">
                                    {{ $website->server_ip }} ← 
                                    <span class="badge badge-md badge-pastel-{{ $website->dns_status === 'active' ? 'green' : ($website->dns_status === 'pending' ? 'yellow' : 'red') }}">
                                        {{ ucfirst($website->dns_status) }}
                                    </span>
                                </span>
                            </div>
                        @endif

                        <!-- Deployment Section -->
                        <div class="section-label">DEPLOYMENT</div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-clock-history"></i> Last Deploy</span>
                            <span class="info-value">{{ $website->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-activity"></i> Status</span>
                            <span class="badge badge-md badge-pastel-{{ $website->is_active ? 'green' : 'red' }}">
                                {{ $website->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Forms -->
                <form id="delete-form-{{ $website->id }}" action="{{ route('websites.destroy', $website) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
                <form id="redeploy-form-{{ $website->id }}" action="{{ route('websites.redeploy', $website) }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        @endforeach

        <!-- Pagination -->
        @if($websites->hasPages())
            <div class="mt-4">
                {{ $websites->appends(['type' => $type])->links() }}
            </div>
        @endif
    @endif

    <script>
        function toggleCard(id) {
            var $body = $('#card-body-' + id);
            var $chevron = $('#chevron-' + id);
            
            if ($body.is(':hidden')) {
                $body.show();
                $chevron.addClass('expanded');
            } else {
                $body.hide();
                $chevron.removeClass('expanded');
            }
        }

        function redeployWebsite(id) {
            Swal.fire({
                title: 'Redeploy Website?',
                text: 'Regenerate and redeploy Nginx and PHP-FPM configurations?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, redeploy!',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Redeploying...',
                        text: 'Please wait while we redeploy your configuration.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form
                    $('#redeploy-form-' + id).submit();
                }
            });
        }
    </script>

    <!-- WordPress Deployment Modal -->
    <div class="modal fade" id="wordpressModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background: linear-gradient(135deg, #5865f2 0%, #7289da 100%); color: white; border: none;">
                    <div>
                        <h4 class="modal-title mb-1">
                            <i class="bi bi-wordpress me-2"></i> Deploy WordPress Website
                        </h4>
                        <p class="mb-0 small" style="opacity: 0.9;">Step-by-step guided installation with auto-tuned performance</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    @include('websites.partials.wordpress-quick-installer')
                </div>
            </div>
        </div>
    </div>
@endsection
