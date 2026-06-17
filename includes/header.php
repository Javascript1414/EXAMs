<?php
// Ensure core functions and session are loaded
require_once __DIR__ . '/functions.php';

// Get user's theme preference if logged in
$userTheme = 'light';
$userDashboardView = 'grid';
$userLanguage = 'en';

if (isLoggedIn()) {
    try {
        require_once __DIR__ . '/student_settings_functions.php';
        $prefs = getStudentPreferences($_SESSION['user_id']);
        if (is_array($prefs)) {
            $userTheme = $prefs['theme'] ?? 'light';
            $userDashboardView = $prefs['dashboard_view'] ?? 'grid';
            $userLanguage = $prefs['language'] ?? 'en';
        }
    } catch (Exception $e) {
        // Preferences not available, use defaults
    }
}

// Build HTML classes
$htmlClasses = [];
if ($userTheme === 'dark') $htmlClasses[] = 'dark-mode';
if ($userTheme === 'auto') $htmlClasses[] = 'auto-theme';
if ($userDashboardView !== 'grid') $htmlClasses[] = 'dashboard-' . htmlspecialchars($userDashboardView);
$classAttr = !empty($htmlClasses) ? ' class="' . implode(' ', $htmlClasses) . '"' : '';
?>
<!DOCTYPE html>
<html lang="<?= $userLanguage ?>"<?= $classAttr ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Dark Mode Theme CSS -->
    <link href="/assets/css/dark-mode.css" rel="stylesheet">
    <!-- Analytics Dashboard CSS -->
    <link href="/assets/css/analytics.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        // Store user preferences for JavaScript access
        window.userPreferences = {
            theme: '<?= htmlspecialchars($userTheme) ?>',
            dashboardView: '<?= htmlspecialchars($userDashboardView) ?>',
            language: '<?= htmlspecialchars($userLanguage) ?>'
        };
    </script>
    
    <style>
        body { background-color: #F3F4F6; font-family: 'Inter', 'Segoe UI', sans-serif; overflow-x: hidden; }
        html.dark-mode body { background-color: #1a1a1a !important; color: #e0e0e0 !important; }
        .wrapper { display: flex; width: 100%; align-items: stretch; min-height: 100vh; }
        
        /* Gradient Sidebar */
        #sidebar { min-width: 260px; max-width: 260px; background: linear-gradient(135deg, #0056D2 0%, #00d2ff 100%); color: #fff; transition: all 0.3s; z-index: 1000; position: relative; }
        #sidebar.active { margin-left: -260px; }
        #sidebar .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar ul.components { padding: 15px 0; }
        #sidebar ul li a { padding: 12px 25px; font-size: 1rem; display: flex; align-items: center; color: rgba(255,255,255,0.85); text-decoration: none; transition: 0.2s; font-weight: 500; }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.15); border-left: 4px solid #fff; padding-left: 21px; }
        #sidebar ul li a i, #sidebar ul li a svg { margin-right: 12px; width: 20px; height: 20px; }
        
        /* Content Area */
        #content { width: 100%; padding: 20px 30px; min-height: 100vh; transition: all 0.3s; }
        
        /* Cards & Navbar */
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); background: #fff; transition: transform 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.05); }
        .top-navbar { background: linear-gradient(135deg, var(--theme-primary, #0056D2) 0%, var(--theme-secondary, #00d2ff) 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-bottom: 25px; padding: 12px 25px; display: flex; justify-content: space-between; align-items: stretch; transition: all 0.5s ease; overflow: visible !important; position: relative; z-index: 999; }
        
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; height: 100vh; }
            #sidebar.active { margin-left: 0; }
            #content { padding: 15px; }
        }
        
        /* Enhanced Avatar Circle */
        .avatar-circle { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            background: rgba(255, 255, 255, 0.25); 
            color: #fff; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }
        
        .avatar-circle:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.35);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.3);
        }
        
        /* Notification Bell */
        .notification-bell {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFB84D 0%, #FF9800 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
            position: relative;
        }
        
        .notification-bell:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 6px 25px rgba(255, 152, 0, 0.6);
        }
        
        .notification-bell svg {
            width: 22px;
            height: 22px;
        }
        
        .notification-bell::after {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 14px;
            height: 14px;
            background: linear-gradient(135deg, #FF4444 0%, #DD0000 100%);
            border-radius: 50%;
            border: 2px solid #fff;
            animation: pulse-dot 2s infinite;
        }
        
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 68, 68, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(255, 68, 68, 0); }
        }
        
        /* User Profile Section */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 15px;
            border-radius: 10px;
            background: rgba(0, 84, 210, 0.05);
        }
        
        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(3px);
        }
        
        .user-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 0.95rem;
            letter-spacing: -0.3px;
        }
        
        .top-navbar .text-dark {
            color: #ffffff !important;
        }
        
        .top-navbar .text-secondary {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .top-navbar a {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .top-navbar a:hover {
            color: #ffffff !important;
        }
        
        /* Top Navbar Actions */
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .top-navbar .btn-light {
            background-color: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }
        
        .top-navbar .btn-light:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            position: relative;
        }
        
        .notification-dropdown .dropdown-menu {
            min-width: 320px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .notification-item {
            padding: 12px 15px;
            border-left: 4px solid #0056D2;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        
        .notification-item:hover {
            background: rgba(0, 84, 210, 0.05);
            border-left-color: #FF9800;
            transform: translateX(3px);
        }
        
        /* Shimmer Effect */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .notification-bell::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            background-size: 1000px 100%;
            border-radius: 50%;
            animation: shimmer 3s infinite;
        }
        
        /* User Profile Dropdown Styles */
        #userDropdown {
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        #userDropdown:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
        }
        
        #userDropdown svg {
            transition: transform 0.3s ease;
        }
        
        #userDropdown[aria-expanded="true"] svg {
            transform: rotate(180deg);
        }
        
        .dropdown-menu {
            animation: slideDown 0.2s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Dropdown Item Styling */
        .dropdown-item {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            border-radius: 10px;
            margin: 6px 8px;
            padding: 12px 14px !important;
            color: #1f2937 !important;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .dropdown-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
            border-radius: 10px;
        }
        
        .dropdown-item:hover::before {
            left: 100%;
        }
        
        .dropdown-item i, .dropdown-item svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }
        
        .dropdown-item:hover i, .dropdown-item:hover svg {
            transform: scale(1.2);
        }
        
        .dropdown-item:not(.text-danger) {
            color: #1f2937 !important;
        }
        
        .dropdown-item:not(.text-danger):hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
            color: #0056D2 !important;
            border: 1px solid rgba(0, 84, 210, 0.4) !important;
            transform: translateX(6px);
            box-shadow: 0 4px 12px rgba(0, 84, 210, 0.2);
            font-weight: 600;
        }
        
        .dropdown-item.text-danger {
            color: #ef4444 !important;
        }
        
        .dropdown-item.text-danger:hover {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
            color: #dc2626 !important;
            border: 1px solid rgba(239, 68, 68, 0.4) !important;
            transform: translateX(6px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            font-weight: 600;
        }
        
        /* Dropdown Menu Styling */
        .dropdown-menu {
            position: absolute !important;
            top: calc(100% + 10px) !important;
            right: 0 !important;
            left: auto !important;
            min-width: 260px !important;
            z-index: 9999 !important;
            visibility: visible !important;
            background-color: #fff !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-radius: 16px !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.08) !important;
            padding: 12px !important;
            margin: 0 !important;
            backdrop-filter: blur(20px);
        }
        
        .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            animation: slideDownElegant 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        
        .dropdown-menu:not(.show) {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        @keyframes slideDownElegant {
            from {
                opacity: 0;
                transform: translateY(-15px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .dropdown-menu li {
            list-style: none;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Dropdown User Info Section */
        .dropdown-menu li:first-child {
            padding: 16px 12px !important;
            margin-bottom: 10px !important;
            border-bottom: none !important;
            border-radius: 10px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 50%, #fcd34d 100%);
        }
        
        .dropdown-menu li:first-child .fw-bold {
            font-size: 0.98rem;
            letter-spacing: -0.3px;
            color: #78350f !important;
        }
        
        .dropdown-menu li:first-child > div > div:last-child {
            color: #92400e !important;
            font-size: 0.82rem;
        }
        
        /* Dropdown Separator */
        .dropdown {
            position: relative !important;
        }
        
        /* User Dropdown Trigger Button */
        .user-dropdown-trigger {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative;
            z-index: 10;
        }
        
        .user-dropdown-trigger::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .user-dropdown-trigger:hover::after {
            opacity: 1;
        }
        
        .user-dropdown-trigger:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .user-dropdown-trigger[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.25) !important;
            border-color: rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .user-dropdown-trigger[aria-expanded="true"] i {
            transform: rotate(180deg) scale(1.1);
        }
        
        /* ===== DARK MODE THEME ===== */
        html.dark-mode,
        html.dark-mode body {
            --bs-body-bg: #1a1a1a;
            --bs-body-color: #e0e0e0;
        }
        
        html.dark-mode body { 
            background-color: #1a1a1a !important;
            color: #e0e0e0 !important;
        }
        
        html.dark-mode #content {
            background-color: #1a1a1a !important;
            color: #e0e0e0 !important;
        }
        
        html.dark-mode .card {
            background-color: #2d2d2d !important;
            color: #e0e0e0 !important;
            border: 1px solid #3d3d3d !important;
        }
        
        html.dark-mode .form-control,
        html.dark-mode .form-select,
        html.dark-mode select.select-control {
            background-color: #2d2d2d !important;
            color: #e0e0e0 !important;
            border-color: #3d3d3d !important;
        }
        
        html.dark-mode .form-control:focus,
        html.dark-mode .form-select:focus,
        html.dark-mode select.select-control:focus {
            background-color: #2d2d2d !important;
            color: #e0e0e0 !important;
            border-color: #0056D2 !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 210, 0.25) !important;
        }
        
        html.dark-mode .table {
            color: #e0e0e0;
            border-color: #3d3d3d;
        }
        
        html.dark-mode .table thead th {
            background-color: #2d2d2d;
            color: #e0e0e0;
            border-color: #3d3d3d;
        }
        
        html.dark-mode .table tbody tr {
            border-color: #3d3d3d;
        }
        
        html.dark-mode .table tbody tr:hover {
            background-color: #3d3d3d;
        }
        
        html.dark-mode h1, html.dark-mode h2, html.dark-mode h3, 
        html.dark-mode h4, html.dark-mode h5, html.dark-mode h6,
        html.dark-mode .heading,
        html.dark-mode p,
        html.dark-mode label,
        html.dark-mode span {
            color: #e0e0e0 !important;
        }
        
        html.dark-mode .text-muted {
            color: #a0a0a0 !important;
        }
        
        html.dark-mode .badge {
            background-color: #3d3d3d;
            color: #e0e0e0;
        }
        
        html.dark-mode .btn-light {
            background-color: #3d3d3d;
            color: #e0e0e0;
            border-color: #3d3d3d;
        }
        
        html.dark-mode .btn-light:hover {
            background-color: #4d4d4d;
            color: #e0e0e0;
            border-color: #4d4d4d;
        }
        
        html.dark-mode .alert {
            background-color: #2d2d2d;
            color: #e0e0e0;
            border-color: #3d3d3d;
        }
        
        /* ===== COMPACT DASHBOARD VIEW ===== */
        html.dashboard-compact .setting-item {
            padding: 8px 0 !important;
        }
        
        html.dashboard-compact .card {
            margin-bottom: 8px !important;
        }
        
        /* ===== LIST DASHBOARD VIEW ===== */
        html.dashboard-list .grid-layout {
            display: flex !important;
            flex-direction: column !important;
        }
        
        html.dashboard-list .grid-layout > * {
            margin-bottom: 10px !important;
        }
        
        /* ===== THEME TOGGLE BUTTON ===== */
        .theme-toggle-btn {
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%) !important;
            border: 1.5px solid rgba(255,255,255,0.3) !important;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 8px 12px !important;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .theme-toggle-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.6s ease;
        }
        
        .theme-toggle-btn:hover::before {
            left: 100%;
        }
        
        .theme-toggle-btn:hover {
            background: linear-gradient(135deg, rgba(255,255,255,0.35) 0%, rgba(255,255,255,0.2) 100%) !important;
            border-color: rgba(255,255,255,0.5) !important;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .theme-toggle-btn:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .theme-toggle-btn i {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            animation: spin-once 0.6s ease-in-out;
        }
        
        .theme-toggle-btn:hover i {
            transform: rotate(180deg) scale(1.2);
        }
        
        @keyframes spin-once {
            0% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Dark Mode - Theme Toggle Button */
        body[data-theme="dark"] .theme-toggle-btn {
            background: linear-gradient(135deg, rgba(100,150,255,0.3) 0%, rgba(50,100,255,0.2) 100%) !important;
            border-color: rgba(100,150,255,0.5) !important;
            color: #e0e0e0 !important;
            box-shadow: 0 4px 12px rgba(88, 101, 242, 0.2);
        }
        
        body[data-theme="dark"] .theme-toggle-btn:hover {
            background: linear-gradient(135deg, rgba(100,150,255,0.4) 0%, rgba(50,100,255,0.3) 100%) !important;
            border-color: rgba(100,150,255,0.7) !important;
            box-shadow: 0 8px 20px rgba(88, 101, 242, 0.4);
        }
    </style>
    
    <script>
        // Dynamic Color Theme - Different color on each refresh
        document.addEventListener('DOMContentLoaded', function() {
            const colorThemes = [
                // Blue themes
                { primary: '#0056D2', secondary: '#00d2ff' },
                { primary: '#1e3a8a', secondary: '#3b82f6' },
                { primary: '#0369a1', secondary: '#06b6d4' },
                
                // Purple themes
                { primary: '#7c3aed', secondary: '#a78bfa' },
                { primary: '#6d28d9', secondary: '#8b5cf6' },
                
                // Green themes
                { primary: '#059669', secondary: '#10b981' },
                { primary: '#0d9488', secondary: '#14b8a6' },
                
                // Red/Pink themes
                { primary: '#dc2626', secondary: '#f87171' },
                { primary: '#be123c', secondary: '#fb7185' },
                
                // Orange themes
                { primary: '#ea580c', secondary: '#fb923c' },
                { primary: '#c2410c', secondary: '#f97316' },
                
                // Teal themes
                { primary: '#0891b2', secondary: '#06b6d4' },
                { primary: '#115e59', secondary: '#14b8a6' },
                
                // Indigo themes
                { primary: '#4f46e5', secondary: '#818cf8' },
                { primary: '#312e81', secondary: '#6366f1' },
                
                // Cyan themes
                { primary: '#0e7490', secondary: '#22d3ee' },
                { primary: '#164e63', secondary: '#06b6d4' }
            ];
            
            // Helper function to convert hex to RGB
            function hexToRgb(hex) {
                const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '99, 102, 241';
            }
            
            // Get random theme
            const randomTheme = colorThemes[Math.floor(Math.random() * colorThemes.length)];
            
            // Apply to sidebar
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.style.background = `linear-gradient(135deg, ${randomTheme.primary} 0%, ${randomTheme.secondary} 100%)`;
            }
            
            // Store current colors in data attribute for hover effects
            const root = document.documentElement;
            root.style.setProperty('--theme-primary', randomTheme.primary);
            root.style.setProperty('--theme-secondary', randomTheme.secondary);
            root.style.setProperty('--theme-primary-rgb', hexToRgb(randomTheme.primary));
            root.style.setProperty('--theme-secondary-rgb', hexToRgb(randomTheme.secondary));
        });
        
        // Theme Application Function - Called after preference updates
        function applyUserPreferences() {
            if (window.userPreferences) {
                const html = document.documentElement;
                const { theme, dashboardView } = window.userPreferences;
                
                // Remove existing theme classes
                html.classList.remove('dark-mode', 'auto-theme');
                
                // Apply new theme
                if (theme === 'dark') {
                    html.classList.add('dark-mode');
                } else if (theme === 'auto') {
                    // Check system preference
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        html.classList.add('dark-mode');
                    }
                }
                
                // Update dashboard view classes
                html.classList.remove('dashboard-list', 'dashboard-compact');
                if (dashboardView === 'list') {
                    html.classList.add('dashboard-list');
                } else if (dashboardView === 'compact') {
                    html.classList.add('dashboard-compact');
                }
            }
        }
        
        // Apply preferences immediately on page load
        applyUserPreferences();
        
        // Listen for preference updates from settings
        document.addEventListener('preferenceUpdated', function(e) {
            if (e.detail && e.detail.field && e.detail.value) {
                window.userPreferences[e.detail.field] = e.detail.value;
                applyUserPreferences();
            }
        });
    </script>
    <!-- Dark Mode Manager -->
    <script src="/assets/js/dark-mode.js"></script>
</head>
<body>
    <div class="wrapper">