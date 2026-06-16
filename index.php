<?php
session_start();

// Check login status
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role_name']);

// Redirect if logged in
if ($isLoggedIn) {
    header("Location: /{$_SESSION['role_name']}/index.php");
    exit;
}

// Define APP_NAME if not defined
if (!defined('APP_NAME')) {
    define('APP_NAME', 'EXAMs');
}

// Try to get stats, but don't break if DB fails
$userCount = 0;
$examCount = 0;
$materialCount = 0;

try {
    require_once __DIR__ . '/includes/db.php';
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'] ?? 0;
    $examCount = $pdo->query("SELECT COUNT(*) as count FROM exams")->fetch()['count'] ?? 0;
    $materialCount = $pdo->query("SELECT COUNT(*) as count FROM study_materials")->fetch()['count'] ?? 0;
} catch (Exception $e) {
    // Silently fail - use default values
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Learning Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(-45deg, #0f172a, #1e293b, #0f172a, #1a1f2e);
            background-size: 400% 400%;
            min-height: 100vh;
            color: #e2e8f0;
            animation: gradientShift 15s ease infinite;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.25rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: #cbd5e1 !important;
            margin: 0 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #a5b4fc !important;
            transform: translateY(-2px);
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28225, 232, 240, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .btn-login, .btn-register {
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.5);
            color: #a5b4fc;
        }

        .btn-login:hover {
            background: rgba(99, 102, 241, 0.3);
            border-color: #6366f1;
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        /* Hero Section */
        .hero {
            padding: 6rem 0;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #a5b4fc, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: slideDown 0.8s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero p {
            font-size: 1.3rem;
            color: #cbd5e1;
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            animation: slideUp 0.8s ease-out 0.2s both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideUp 0.8s ease-out 0.4s both;
        }

        .hero-btn {
            padding: 0.95rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hero-btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .hero-btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.5);
        }

        .hero-btn-secondary {
            background: rgba(99, 102, 241, 0.1);
            color: #a5b4fc;
            border: 2px solid rgba(99, 102, 241, 0.3);
        }

        .hero-btn-secondary:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
            color: white;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 4rem 0;
            position: relative;
            z-index: 1;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.4s ease;
            animation: cardSlideUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.4s; }

        @keyframes cardSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
            transform: translateY(-8px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        /* Features Section */
        .features-section {
            padding: 4rem 0;
            position: relative;
            z-index: 1;
            margin-top: 4rem;
        }

        .features-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 3.5rem;
            background: linear-gradient(135deg, #a5b4fc, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.4s ease;
            animation: cardSlideUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .feature-card:nth-child(1) { animation-delay: 0s; }
        .feature-card:nth-child(2) { animation-delay: 0.15s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.45s; }

        .feature-card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
            transform: translateY(-8px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: white;
        }

        .feature-desc {
            color: #cbd5e1;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0;
            text-align: center;
            color: #94a3b8;
            margin-top: 6rem;
            position: relative;
            z-index: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.25rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-btn {
                width: 100%;
                justify-content: center;
            }

            .features-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">📚 <?= APP_NAME ?></a>
            <button class="navbar-toggler btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item ms-2">
                        <a class="btn btn-login" href="login.php">Login</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-register" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Master Your Skills with <?= APP_NAME ?></h1>
            <p>Learn from industry experts, get certified, and launch your career in your chosen trade</p>
            
            <div class="hero-buttons">
                <a href="register.php" class="hero-btn hero-btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    Start Learning
                </a>
                <a href="login.php" class="hero-btn hero-btn-secondary">
                    Log In to Account
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section>
        <div class="container">
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($userCount) ?>+</div>
                    <div class="stat-label">Active Learners</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($examCount) ?>+</div>
                    <div class="stat-label">Exams Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($materialCount) ?>+</div>
                    <div class="stat-label">Learning Materials</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <h2 class="features-title">Why Choose Us?</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎓</div>
                    <div class="feature-title">Quality Education</div>
                    <div class="feature-desc">Learn from certified instructors with real-world experience and expertise</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <div class="feature-title">Comprehensive Materials</div>
                    <div class="feature-desc">Access detailed notes, videos, and resources for each course</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <div class="feature-title">Track Progress</div>
                    <div class="feature-desc">Monitor your learning journey with detailed analytics and insights</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <div class="feature-title">Get Certified</div>
                    <div class="feature-desc">Earn industry-recognized certificates upon course completion</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 <?= APP_NAME ?>. All rights reserved. | Building skills, changing lives.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
