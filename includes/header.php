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
        .top-navbar { background: #fff; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 25px; padding: 10px 20px; }
        
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; height: 100vh; }
            #sidebar.active { margin-left: 0; }
            #content { padding: 15px; }
        }
        
        .avatar-circle { width: 35px; height: 35px; border-radius: 50%; background: #0056D2; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">