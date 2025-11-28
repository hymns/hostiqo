<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Git Webhook Manager')</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- App Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
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
        <div class="brand">
            <i class="bi bi-git"></i> GitWebhook
        </div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('webhooks.*') ? 'active' : '' }}" href="{{ route('webhooks.index') }}">
                <i class="bi bi-hdd-network me-2"></i> Webhooks
            </a>
            <a class="nav-link {{ request()->routeIs('deployments.*') ? 'active' : '' }}" href="{{ route('deployments.index') }}">
                <i class="bi bi-cloud-haze2 me-2"></i> Deployments
            </a>
            <a class="nav-link {{ request()->routeIs('databases.*') ? 'active' : '' }}" href="{{ route('databases.index') }}">
                <i class="bi bi-database me-2"></i> Databases
            </a>
            <a class="nav-link {{ request()->routeIs('queues.*') ? 'active' : '' }}" href="{{ route('queues.index') }}">
                <i class="bi bi-calendar2-check me-2"></i> Queues
            </a>
        </nav>
        
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
            <div class="alert alert-success alert-dismissible fade show" role="alert">
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
        
        // Confirm delete
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item?');
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
    </script>
    
    @stack('scripts')
</body>
</html>
