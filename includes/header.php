<?php
// Ensure core functions and session are loaded
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { background-color: #F3F4F6; font-family: 'Inter', 'Segoe UI', sans-serif; overflow-x: hidden; }
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
        .top-navbar { background: linear-gradient(135deg, var(--theme-primary, #0056D2) 0%, var(--theme-secondary, #00d2ff) 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-bottom: 25px; padding: 12px 25px; display: flex; justify-content: space-between; align-items: center; transition: all 0.5s ease; }
        
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
    </script>
</head>
<body>
    <div class="wrapper">