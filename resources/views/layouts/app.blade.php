<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hostiqo')</title>
    
    <!-- Google Fonts - Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.3/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- App Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
        <i class="bi bi-list" style="font-size: 1.5rem;"></i>
    </button>

    <!-- Sidebar Overlay (for mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-content">
            <div class="brand">
                <img src="{{ asset('images/logo-white.svg') }}" alt="Hostiqo" class="sidebar-logo">
            </div>
            <nav class="nav flex-column">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>

                <!-- Application Section -->
                <div class="nav-section-title">
                    Applications
                </div>

                <a class="nav-link {{ request()->routeIs('websites.*') ? 'active' : '' }}" href="{{ route('websites.index') }}">
                    <i class="bi bi-globe me-2"></i> Websites
                </a>
                <a class="nav-link {{ request()->routeIs('databases.*') ? 'active' : '' }}" href="{{ route('databases.index') }}">
                    <i class="bi bi-database me-2"></i> Databases
                </a>
                <a class="nav-link {{ request()->routeIs('webhooks.*') ? 'active' : '' }}" href="{{ route('webhooks.index') }}">
                    <i class="bi bi-hdd-network me-2"></i> Webhooks
                </a>
                <a class="nav-link {{ request()->routeIs('deployments.*') ? 'active' : '' }}" href="{{ route('deployments.index') }}">
                    <i class="bi bi-cloud-haze2 me-2"></i> Deployments
                </a>

                <!-- Server Tools Section -->
                <div class="nav-section-title">
                    Server Tools
                </div>
                <a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">
                    <i class="bi bi-gear-fill me-2"></i> Services
                </a>
                <a class="nav-link {{ request()->routeIs('firewall.*') ? 'active' : '' }}" href="{{ route('firewall.index') }}">
                    <i class="bi bi-shield-check me-2"></i> Firewall
                </a>
                <a class="nav-link {{ request()->routeIs('server-health') ? 'active' : '' }}" href="{{ route('server-health') }}">
                    <i class="bi bi-heart-pulse me-2"></i> Server Health
                </a>
                <a class="nav-link {{ request()->routeIs('cron-jobs.*') ? 'active' : '' }}" href="{{ route('cron-jobs.index') }}">
                    <i class="bi bi-clock-history me-2"></i> Cron Jobs
                </a>
                <a class="nav-link {{ request()->routeIs('supervisor.*') ? 'active' : '' }}" href="{{ route('supervisor.index') }}">
                    <i class="bi bi-terminal me-2"></i> Supervisor
                </a>

                <!-- Operation Section -->
                <div class="nav-section-title">
                    Operations
                </div>
                <a class="nav-link {{ request()->routeIs('queues.*') ? 'active' : '' }}" href="{{ route('queues.index') }}">
                    <i class="bi bi-calendar2-check me-2"></i> Queues
                </a>
                <a class="nav-link {{ request()->routeIs('artisan.*') ? 'active' : '' }}" href="{{ route('artisan.index') }}">
                    <i class="bi bi-code-slash me-2"></i> Artisan
                </a>
                <a class="nav-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">
                    <i class="bi bi-bell me-2"></i> Alerts
                </a>
                <a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                    <i class="bi bi-file-text me-2"></i> Logs
                </a>
                <a class="nav-link {{ request()->routeIs('files.*') ? 'active' : '' }}" href="{{ route('files.index') }}">
                    <i class="bi bi-folder me-2"></i> File Manager
                </a>
            </nav>
        </div>
        
        <!-- User Info -->
        <div class="user-info">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="flex-grow-1">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-email">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="d-grid">
                @csrf
                <button type="submit" class="btn btn-logout btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">@yield('page-title', 'Dashboard')</h2>
                <p class="text-muted mb-0">@yield('page-description', '')</p>
            </div>
            <div>
                @yield('page-actions')
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show auto-hide-alert" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Validation Error:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Page Content -->
        @yield('content')
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.3/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Copy to clipboard function
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check"></i> Copied!';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 2000);
            });
        }
        
        // Confirm delete with SweetAlert2
        function confirmDelete(message) {
            return Swal.fire({
                title: 'Are you sure?',
                text: message || 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => result.isConfirmed);
        }
        
        // Confirm action with SweetAlert2
        async function confirmAction(title, message, confirmText = 'Yes, proceed!', icon = 'question') {
            const result = await Swal.fire({
                title: title,
                text: message,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel'
            });
            return result.isConfirmed;
        }
        
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            function toggleMenu() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
            
            function closeMenu() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
            
            // Toggle menu on button click
            menuToggle.addEventListener('click', toggleMenu);
            
            // Close menu when clicking overlay
            overlay.addEventListener('click', closeMenu);
            
            // Close menu when clicking a nav link (on mobile)
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 992) {
                        closeMenu();
                    }
                });
            });
            
            // Close menu on window resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    closeMenu();
                }
            });
        });
        
        // Auto-hide success alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const autoHideAlerts = document.querySelectorAll('.auto-hide-alert');
            
            autoHideAlerts.forEach(function(alert) {
                // Show with animation
                setTimeout(function() {
                    // Start fade out
                    alert.classList.remove('show');
                    
                    // Remove from DOM after fade animation completes
                    setTimeout(function() {
                        alert.remove();
                    }, 150); // Bootstrap fade transition time
                }, 5000); // 5 seconds
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>
