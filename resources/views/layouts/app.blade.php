<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Salary Management System')</title>
    
    <!-- Premium Google Fonts & Icon Libraries -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ApexCharts for Stunning Visual Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- Custom Elegant Vanilla CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
</head>
<body>
    <div class="app-container">
        <!-- Navigation Header -->
        <header class="app-header">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="logo-text">
                    <h1>PayScale<span>Hub</span></h1>
                    <span class="sub-text">Enterprise HR Portal</span>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="profile-avatar">HR</div>
                    <div class="profile-info">
                        <span class="profile-name">HR Manager</span>
                        <span class="profile-role">Administrator</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="app-main">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="app-footer">
            <p>&copy; {{ date('Y') }} PayScaleHub Inc. All rights reserved. Designed for HR Operations.</p>
            <div class="footer-meta">
                <span class="badge"><i class="fa-solid fa-shield-halved"></i> Secure TLS</span>
                <span class="badge"><i class="fa-solid fa-bolt"></i> High Performance Index</span>
            </div>
        </footer>
    </div>

    <!-- Modals & Global Dynamic UI Elements -->
    @yield('modals')

    <!-- Toast Notifications Area -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Custom Logic Scripts -->
    <script>
        // Global AJAX setup
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Show Toast Notifications
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
            toast.innerHTML = `
                <i class="fa-solid ${icon}"></i>
                <div class="toast-content">
                    <p class="toast-message">${message}</p>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            container.appendChild(toast);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }

        // Show session flash messages as toasts
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif
        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif
    </script>
    @yield('scripts')
</body>
</html>
