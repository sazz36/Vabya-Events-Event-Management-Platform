<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Create Database instance and get connection
$db = new Database();
$pdo = $db->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>भव्य Event – Book Events Seamlessly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1B3C53;
            --primary-dark: #0f2a3a;
            --primary-light: #2d5a7a;
            --secondary: #D2C1B6;
            --secondary-dark: #b8a99e;
            --secondary-light: #e8dcd4;
            --accent: #456882;
            --accent-dark: #3a5a6f;
            --accent-light: #5a7a8f;
            --neutral-dark: #1B3C53;
            --neutral-light: #F9F3EF;
            --danger: #d63031;
            --danger-dark: #b71540;
            --light: var(--neutral-light);
            --dark: var(--neutral-dark);
            --text-dark: var(--neutral-dark);
            --text-light: #ffffff;
            --card-bg-light: #ffffff;
            --card-bg-dark: #1e1e1e;
            --shadow-light: 0 8px 32px rgba(27, 60, 83, 0.15);
            --shadow-dark: 0 8px 32px rgba(0, 0, 0, 0.3);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--neutral-light);
            color: var(--text-dark);
            min-height: 100vh;
            transition: var(--transition);
        }
        
        body.dark-mode {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 100%);
            color: var(--text-light);
        }
        
        /* Professional Spacing & Alignment Improvements */
        
        /* Navigation - Enhanced spacing and alignment */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            padding: 18px 0;
            transition: var(--transition);
        }
        
        .dark-mode .navbar {
            background: rgba(30, 30, 30, 0.95);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.4);
        }
        
        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 1.9rem;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.8px;
            margin-right: 2rem;
        }
        
        .nav-link {
            font-weight: 500;
            margin: 0 15px;
            position: relative;
            transition: var(--transition);
            padding: 8px 16px !important;
            border-radius: 8px;
        }
        
        .nav-link:hover {
            background: rgba(27, 60, 83, 0.1);
            transform: translateY(-1px);
        }
        
        .nav-link:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        
        .nav-link:hover:after {
            width: 80%;
        }
        
        .dark-mode .nav-link {
            color: var(--text-light) !important;
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        /* Hero Section - Professional spacing */
        .hero {
            padding: 120px 0 80px;
            background: linear-gradient(135deg, rgba(27, 60, 83, 0.08) 0%, rgba(69, 104, 130, 0.08) 100%);
            position: relative;
            overflow: hidden;
            margin-bottom: 0;
        }
        
        .dark-mode .hero {
            background: linear-gradient(135deg, rgba(27, 60, 83, 0.06) 0%, rgba(69, 104, 130, 0.06) 100%);
        }
        
        .hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 3.8rem;
            margin-bottom: 25px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.4rem;
            max-width: 650px;
            margin: 0 auto 40px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .hero .d-flex {
            gap: 20px !important;
        }
        
        /* Buttons - Enhanced spacing and alignment */
        .btn-gradient {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 35px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: 0 6px 20px rgba(27, 60, 83, 0.3);
            letter-spacing: 0.5px;
            font-size: 1.05rem;
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(27, 60, 83, 0.4);
        }
        
        .btn-outline-gradient {
            background: transparent;
            border: 2px solid;
            border-image: linear-gradient(90deg, var(--primary), var(--accent));
            border-image-slice: 1;
            color: var(--primary);
            border-radius: 50px;
            padding: 13px 33px;
            font-weight: 600;
            transition: var(--transition);
            font-size: 1.05rem;
        }
        
        .dark-mode .btn-outline-gradient {
            color: white;
        }
        
        .btn-outline-gradient:hover {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(27, 60, 83, 0.4);
        }
        
        /* Sections - Professional spacing */
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2.8rem;
            margin-bottom: 60px;
            text-align: center;
            position: relative;
            line-height: 1.3;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 3px;
        }
        
        .how-it-works {
            padding: 100px 0;
            background: white;
            margin: 0;
        }
        
        .dark-mode .how-it-works {
            background: var(--dark);
        }
        
        .featured-events {
            padding: 100px 0;
            margin: 0;
        }
        
        /* Cards - Enhanced spacing and alignment */
        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            background: var(--card-bg-light);
            height: 100%;
            margin-bottom: 30px;
        }
        
        .dark-mode .card {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            background: var(--card-bg-dark);
        }
        
        .card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(27, 60, 83, 0.25);
        }
        
        .dark-mode .card:hover {
            box-shadow: 0 20px 40px rgba(27, 60, 83, 0.15);
        }
        
        .card-img-top {
            height: 240px;
            object-fit: cover;
            transition: transform 0.5s ease;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        }
        
        .dark-mode .card-img-top {
            background: linear-gradient(45deg, #2a2a2a, #3a3a3a);
        }
        
        .card-img-top.loading {
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            animation: shimmer 1.5s infinite;
        }
        
        .dark-mode .card-img-top.loading {
            background: linear-gradient(45deg, #2a2a2a, #3a3a3a);
        }
        
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }
        
        .card-img-top.loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200px 100%;
            animation: shimmer 1.5s infinite;
        }
        
        .dark-mode .card-img-top.loading {
            background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%);
            background-size: 200px 100%;
            animation: shimmer 1.5s infinite;
        }
        
        .card:hover .card-img-top {
            transform: scale(1.05);
        }
        
        .card-body {
            padding: 30px;
            position: relative;
        }
        
        .card-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 18px;
            line-height: 1.4;
        }
        
        .card-text {
            margin-bottom: 25px;
            color: #666;
            line-height: 1.6;
        }
        
        .dark-mode .card-text {
            color: #aaa;
        }
        
        /* Icon containers - Better spacing */
        .icon-container {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(27, 60, 83, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            transition: all 0.3s ease;
        }
        
        .icon-container:hover {
            transform: scale(1.1);
            background: rgba(27, 60, 83, 0.2);
            cursor: pointer;
        }
        
        .dark-mode .icon-container {
            background: rgba(27, 60, 83, 0.2);
        }
        
        .icon-container i {
            font-size: 2.2rem;
            color: var(--primary);
        }
        
        /* Action steps - Enhanced spacing */
        .action-step {
            transition: all 0.4s ease;
            border-radius: 20px;
            padding: 35px 25px;
            cursor: pointer;
            height: 100%;
            margin-bottom: 20px;
        }
        
        .action-step:hover {
            background: rgba(27, 60, 83, 0.05);
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(27, 60, 83, 0.1);
        }
        
        .dark-mode .action-step:hover {
            background: rgba(27, 60, 83, 0.1);
        }
        
        .action-step h3 {
            font-size: 1.4rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .action-step p {
            line-height: 1.7;
            margin-bottom: 25px;
        }
        
        /* Event badges and info - Better alignment */
        .event-badge {
            position: absolute;
            top: -18px;
            right: 25px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            z-index: 10;
            box-shadow: 0 6px 15px rgba(27, 60, 83, 0.3);
        }
        
        .event-info {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.08);
        }
        
        .dark-mode .event-info {
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        
        /* Footer - Professional layout and spacing */
        .footer {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            padding: 18px 0 10px;
            text-align: center;
            font-size: 0.97rem;
            position: relative;
            margin-top: 0;
        }
        .footer .footer-content {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: flex-start;
            gap: 110px;
            flex-wrap: wrap;
        }
        .footer .footer-block {
            min-width: 160px;
            margin-bottom: 0;
        }
        .footer h5 {
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.08rem;
            position: relative;
            display: inline-block;
        }
        .footer h5:after {
            content: '';
            display: block;
            margin: 5px auto 0;
            width: 32px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            opacity: 0.4;
        }
        .footer .list-unstyled {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .footer .list-unstyled li {
            margin-bottom: 6px;
        }
        .footer .list-unstyled a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .footer .list-unstyled a:hover {
            color: #fff;
            text-decoration: underline;
        }
        .footer .list-unstyled i {
            margin-right: 7px;
        }
        .footer hr {
            border-color: rgba(255,255,255,0.13);
            margin: 10px 0 8px;
        }
        .footer p {
            margin: 0;
            font-size: 0.97rem;
            opacity: 0.9;
        }
        @media (max-width: 900px) {
            .footer .footer-content {
                flex-direction: column;
                align-items: center;
                gap: 14px;
            }
            .footer .footer-block {
                margin-bottom: 0;
            }
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .hero {
                padding: 80px 0 60px;
            }
            
            .hero h1 {
                font-size: 2.8rem;
                margin-bottom: 20px;
            }
            
            .hero p {
                font-size: 1.2rem;
                margin-bottom: 30px;
            }
            
            .section-title {
                font-size: 2.2rem;
                margin-bottom: 40px;
            }
            
            .how-it-works,
            .featured-events {
                padding: 60px 0;
            }
            
            .card-body {
                padding: 25px;
            }
            
            .action-step {
                padding: 25px 20px;
                margin-bottom: 15px;
            }
            
            .footer {
                padding: 60px 0 30px;
            }
            
            .footer .col-md-6,
            .footer .col-md-3 {
                text-align: center;
                margin-bottom: 25px;
            }
            
            .navbar-brand {
                font-size: 1.6rem;
                margin-right: 1rem;
            }
            
            .nav-link {
                margin: 0 8px;
                padding: 6px 12px !important;
            }
        }
        
        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .btn-gradient,
            .btn-outline-gradient {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .hero .d-flex {
                flex-direction: column;
                gap: 15px !important;
            }
        }
        
        /* Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .confirmation-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            margin: 15% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .dark-mode .confirmation-content {
            background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
            color: var(--text-light);
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .confirmation-content h3 {
            color: var(--primary);
            margin-bottom: 20px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        
        .confirmation-content p {
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .confirmation-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .confirmation-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .btn-confirm {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 60, 83, 0.4);
        }
        
        .btn-cancel {
            background: transparent;
            border: 2px solid #ddd;
            color: #666;
        }
        
        .btn-cancel:hover {
            background: #f8f9fa;
            border-color: #ccc;
        }
        
        .dark-mode .btn-cancel {
            border-color: #555;
            color: #aaa;
        }
        
        .dark-mode .btn-cancel:hover {
            background: #333;
            border-color: #666;
        }
        /* Confirmation Modal Styles (for footer Events link and others) */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        .confirmation-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            margin: 10% auto;
            padding: 32px 32px 28px 32px;
            border-radius: 22px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
            animation: modalSlideIn 0.3s ease-out;
        }
        .confirmation-content h3 {
            color: var(--primary);
            margin-bottom: 18px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.35rem;
        }
        .confirmation-content p {
            margin-bottom: 28px;
            font-size: 1.08rem;
            line-height: 1.6;
        }
        .confirmation-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .confirmation-btn {
            padding: 12px 28px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
            font-size: 1rem;
        }
        .btn-confirm {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 60, 83, 0.4);
        }
        .btn-cancel {
            background: transparent;
            border: 2px solid #ddd;
            color: #666;
        }
        .btn-cancel:hover {
            background: #f8f9fa;
            border-color: #ccc;
        }
        .dark-mode .confirmation-content {
            background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
            color: var(--text-light);
        }
        .dark-mode .btn-cancel {
            border-color: #555;
            color: #aaa;
        }
        .dark-mode .btn-cancel:hover {
            background: #333;
            border-color: #666;
        }
        @media (max-width: 576px) {
            .confirmation-content {
                padding: 18px 8px 16px 8px;
                max-width: 98vw;
            }
            .confirmation-content h3 {
                font-size: 1.1rem;
            }
            .confirmation-content p {
                font-size: 0.98rem;
            }
            .confirmation-btn {
                padding: 10px 12px;
                min-width: 90px;
                font-size: 0.98rem;
            }
        }
        /* Add this to ensure vertical and horizontal centering in How It Works cards */
        .how-it-works .action-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
        }
        .how-it-works .icon-container {
            margin-bottom: 1.2rem;
        }
        .how-it-works .action-step h3 {
            margin-bottom: 1rem;
        }
        .how-it-works .action-step p {
            margin-bottom: 1.2rem;
        }
        .how-it-works .mt-4 {
            margin-top: 1.2rem !important;
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['dark']) && $_COOKIE['dark'] === 'true' ? 'dark-mode' : ''; ?>">

<!-- 🔝 Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">भव्य Event</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="home.php">Home</a></li>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="showConfirmation('browse'); return false;">Browse Events</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="events.php">Browse Events</a></li>
                <?php endif; ?>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="user_dashboard.php">My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="ms-3">
                <label class="toggle-switch">
                    <input type="checkbox" id="darkToggle" <?php echo isset($_COOKIE['dark']) && $_COOKIE['dark'] === 'true' ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>
</nav>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-content">
        <h3>�� Ready to Dive into भव्य Event?</h3>
        <p>Are you sure you want to explore the amazing world of events? You'll need to be logged in to access this feature.</p>
        <div class="confirmation-buttons">
            <button class="confirmation-btn btn-confirm" onclick="confirmAction()">Yes, Let's Go! 🎉</button>
            <button class="confirmation-btn btn-cancel" onclick="closeModal()">Maybe Later</button>
        </div>
    </div>
</div>

<!-- 🚀 Hero Section -->
<section class="hero">
    <div class="container text-center">
        <div class="hero-content">
            <h1>Experience Events Like Never Before</h1>
            <p class="lead">Book your seat at concerts, workshops, and events instantly with our seamless booking platform</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <button onclick="showConfirmation('browse')" class="btn btn-gradient">Browse Events <i class="fas fa-arrow-right ms-2"></i></button>
                    <a href="register.php" class="btn btn-outline-gradient">Register Now</a>
                <?php else: ?>
                    <a href="events.php" class="btn btn-gradient">Browse Events <i class="fas fa-arrow-right ms-2"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- 📦 How It Works -->
<section class="how-it-works">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <div class="row mt-5">
            <div class="col-md-4 mb-5 mb-md-0">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div onclick="showConfirmation('browse')" class="action-step text-decoration-none text-dark" style="cursor: pointer;">
                        <div class="icon-container">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="mb-3">Browse Events</h3>
                        <p>Explore a diverse range of events happening near you. Filter by category, date, or venue to find exactly what you're looking for.</p>
                        <div class="mt-4">
                            <span class="text-primary fw-bold">Start Exploring <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="events.php" class="action-step text-decoration-none text-dark">
                        <div class="icon-container">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="mb-3">Browse Events</h3>
                        <p>Explore a diverse range of events happening near you. Filter by category, date, or venue to find exactly what you're looking for.</p>
                        <div class="mt-4">
                            <span class="text-primary fw-bold">Start Exploring <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md-4 mb-5 mb-md-0">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div onclick="showConfirmation('seat')" class="action-step text-decoration-none text-dark" style="cursor: pointer;">
                        <div class="icon-container">
                            <i class="fas fa-chair"></i>
                        </div>
                        <h3 class="mb-3">Select Your Seat</h3>
                        <p>Use our interactive seating map to choose the perfect spot. See real-time availability and pick exactly where you want to be.</p>
                        <div class="mt-4">
                            <span class="text-primary fw-bold">Choose Your Seat <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="event_details.php" class="action-step text-decoration-none text-dark">
                        <div class="icon-container">
                            <i class="fas fa-chair"></i>
                        </div>
                        <h3 class="mb-3">Select Your Seat</h3>
                        <p>Use our interactive seating map to choose the perfect spot. See real-time availability and pick exactly where you want to be.</p>
                        <div class="mt-4">
                            <span class="text-primary fw-bold">Choose Your Seat <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div onclick="showConfirmation('booking')" class="action-step text-decoration-none text-dark" style="cursor: pointer;">
                        <div class="icon-container">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3 class="mb-3">Secure Booking</h3>
                        <p>Complete your booking with our secure payment system. Receive instant confirmation and e-tickets directly to your email.</p>
                        <div class="mt-4">
                            <span class="text-primary fw-bold">Book Now <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="checkout.php" class="action-step text-decoration-none text-dark">
                        <div class="icon-container">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3 class="mb-3">Secure Booking</h3>
                        <p>Complete your booking with our secure payment system. Receive instant confirmation and e-tickets directly to your email.</p>
                        <div class="mt-4">
                            <span class="text-primary fw-bold">Book Now <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- 🎉 Featured Events -->
<section class="featured-events">
    <div class="container">
        <h2 class="section-title">Featured Events</h2>
        <div class="row">
            <?php
            // Load featured events from database with proper image handling
            $events = [];
            $fallback_images = [
                'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1535223289827-42f1e9919769?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80'
            ];
            
            try {
                // Get upcoming events with images, ordered by date
                $stmt = $pdo->query("SELECT * FROM events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 3");
                $events = $stmt->fetchAll();
                
                // If no upcoming events, get the most recent events
                if (empty($events)) {
                    $stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC LIMIT 3");
                    $events = $stmt->fetchAll();
                }
                
                // Ensure we have valid event data
                if (!is_array($events)) {
                    $events = [];
                }
            } catch (Exception $e) {
                // If database fails, show static events
                $events = [
                    [
                        'event_id' => 1,
                        'title' => 'Summer Music Festival',
                        'venue' => 'Central Park',
                        'date' => date('Y-m-d', strtotime('+7 days')),
                        'price' => 49.99,
                        'image' => $fallback_images[0]
                    ],
                    [
                        'event_id' => 2,
                        'title' => 'Tech Conference 2023',
                        'venue' => 'Convention Center',
                        'date' => date('Y-m-d', strtotime('+14 days')),
                        'price' => 199.99,
                        'image' => $fallback_images[1]
                    ],
                    [
                        'event_id' => 3,
                        'title' => 'Art Exhibition Opening',
                        'venue' => 'City Art Gallery',
                        'date' => date('Y-m-d', strtotime('+3 days')),
                        'price' => 24.99,
                        'image' => $fallback_images[2]
                    ]
                ];
            }
            
            foreach ($events as $index => $event):
                // Ensure event is an array and has required fields
                if (!is_array($event)) {
                    continue;
                }
                
                // Handle image path - check if it's a local file or external URL
                $image = '';
                if (!empty($event['image']) && is_string($event['image'])) {
                    if (strpos($event['image'], 'http') === 0) {
                        // External URL
                        $image = $event['image'];
                    } else {
                        // Local file - check if file exists
                        $imagePath = __DIR__ . '/../' . $event['image'];
                        if (file_exists($imagePath)) {
                            $image = $event['image'];
                        } else {
                            // File doesn't exist, use fallback
                            $image = $fallback_images[$index] ?? 'https://source.unsplash.com/random/600x400/?event';
                        }
                    }
                } else {
                    // No image in database, use fallback
                    $image = $fallback_images[$index] ?? 'https://source.unsplash.com/random/600x400/?event';
                }
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <img src="/Event_sphere/Event_sphere/<?= ltrim(htmlspecialchars($image), '/') ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($event['title'] ?? 'Event') ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary">Featured</span>
                                <span class="text-muted"><i class="far fa-calendar me-1"></i> <?= isset($event['date']) ? date('M d, Y', strtotime($event['date'])) : 'TBD' ?></span>
                            </div>
                            
                            <h5 class="card-title"><?= htmlspecialchars($event['title'] ?? 'Event Title') ?></h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt me-2"></i> <?= htmlspecialchars($event['venue'] ?? 'Venue TBD') ?><br>
                                <i class="fas fa-ticket-alt me-2"></i> $<?= htmlspecialchars($event['price'] ?? '0.00') ?>
                            </p>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <button onclick="showConfirmation('event', <?= $event['event_id'] ?? 0 ?>)" class="btn btn-gradient w-100">View Details</button>
                            <?php else: ?>
                                <a href="event_details.php?id=<?= $event['event_id'] ?? 0 ?>" class="btn btn-gradient w-100">View Details</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <button onclick="showConfirmation('all-events')" class="btn btn-outline-gradient px-5">
                    View All Events <i class="fas fa-arrow-right ms-2"></i>
                </button>
            <?php else: ?>
                <a href="events.php" class="btn btn-outline-gradient px-5">
                    View All Events <i class="fas fa-arrow-right ms-2"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 🦶 Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-block">
                <h5>भव्य Event</h5>
                <p>Your premier event booking platform.</p>
            </div>
            <div class="footer-block">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="home.php">Home</a></li>
                    <li><a href="#">Events</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            <div class="footer-block">
                <h5>Contact</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-envelope"></i> info@bhavyaevent.com</li>
                    <li><i class="fas fa-phone"></i> +977 9841234567</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; <?php echo date('Y'); ?> भव्य Event. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Footer Events Modal -->
<div id="footerEventsModal" class="confirmation-modal">
    <div class="confirmation-content">
        <h3>Ready to Browse Events?</h3>
        <p>Are you sure you want to explore our amazing events? You'll need to be logged in to access this feature.</p>
        <div class="confirmation-buttons">
            <button class="confirmation-btn btn-confirm" id="footerEventsYes">Yes, Let's Go!</button>
            <button class="confirmation-btn btn-cancel" id="footerEventsNo">Maybe Later</button>
        </div>
    </div>
</div>

<script>
    // Global variables for confirmation modal
    let currentAction = '';
    let currentEventId = null;
    
    // Enhanced Dark Mode Toggle
    const toggle = document.getElementById('darkToggle');
    
    toggle.addEventListener('change', () => {
        // Toggle dark mode class
        document.body.classList.toggle('dark-mode');
        
        // Set cookie to remember preference (expires in 1 year)
        const isDarkMode = document.body.classList.contains('dark-mode');
        document.cookie = `dark=${isDarkMode};path=/;max-age=${365*24*60*60}`;
        
        // Show toast notification
        showToast(`${isDarkMode ? 'Dark' : 'Light'} mode activated`);
    });
    
    // Confirmation Modal Functions
    function showConfirmation(action, eventId = null) {
        currentAction = action;
        currentEventId = eventId;
        
        // Update modal content based on action
        const modal = document.getElementById('confirmationModal');
        const title = modal.querySelector('h3');
        const message = modal.querySelector('p');
        
        switch(action) {
            case 'browse':
                title.innerHTML = '🔍 Ready to Browse Events?';
                message.textContent = 'Are you sure you want to explore our amazing events? You\'ll need to be logged in to access this feature.';
                break;
            case 'seat':
                title.innerHTML = '💺 Ready to Select Your Seat?';
                message.textContent = 'Are you ready to choose your perfect spot? You\'ll need to be logged in to access our interactive seating map.';
                break;
            case 'booking':
                title.innerHTML = '💳 Ready for Secure Booking?';
                message.textContent = 'Are you ready to complete your booking with our secure payment system? You\'ll need to be logged in to proceed.';
                break;
            case 'event':
                title.innerHTML = '🎫 Ready to View Event Details?';
                message.textContent = 'Are you ready to dive into the details of this amazing event? You\'ll need to be logged in to access this feature.';
                break;
            case 'all-events':
                title.innerHTML = '🎉 Ready to Explore All Events?';
                message.textContent = 'Are you ready to discover all the amazing events we have to offer? You\'ll need to be logged in to access this feature.';
                break;
            default:
                title.innerHTML = '�� Ready to Dive into भव्य Event?';
                message.textContent = 'Are you sure you want to explore the amazing world of events? You\'ll need to be logged in to access this feature.';
        }
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
    
    function closeModal() {
        const modal = document.getElementById('confirmationModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
    
    function confirmAction() {
        closeModal();
        
        // Show a brief loading message
        showToast('Redirecting to login... 🚀');
        
        // Redirect to login page after a short delay
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1000);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('confirmationModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    
    // Toast notification function
    function showToast(message) {
        // Remove existing toast if any
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Add styles for toast notifications
    const style = document.createElement('style');
    style.textContent = `
        .toast-notification {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
    
    // Card hover effect enhancement
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-10px) scale(1.02)';
            card.style.boxShadow = '0 15px 35px rgba(27, 60, 83, 0.25)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
            card.style.boxShadow = 'var(--shadow-light)';
        });
    });
    
    // Image loading and error handling for featured events
    document.addEventListener('DOMContentLoaded', function() {
        const eventImages = document.querySelectorAll('.card-img-top');
        
        eventImages.forEach(img => {
            // Add loading class initially
            img.classList.add('loading');
            
            // Handle image load success
            img.addEventListener('load', function() {
                this.classList.remove('loading');
                this.style.opacity = '1';
            });
            
            // Handle image load error
            img.addEventListener('error', function() {
                this.classList.remove('loading');
                // Set a fallback image
                this.src = 'https://source.unsplash.com/random/600x400/?event';
                this.style.opacity = '1';
            });
            
            // Set initial opacity for smooth transition
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
        });
    });
</script>

<script>
// Footer Events link custom modal
const footerEventsLink = document.getElementById('footerEventsLink');
const footerEventsModal = document.getElementById('footerEventsModal');
const footerEventsYes = document.getElementById('footerEventsYes');
const footerEventsNo = document.getElementById('footerEventsNo');

if (footerEventsLink && footerEventsModal && footerEventsYes && footerEventsNo) {
    footerEventsLink.addEventListener('click', function(e) {
        e.preventDefault();
        footerEventsModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });
    footerEventsYes.addEventListener('click', function() {
        window.location.href = 'login.php';
    });
    footerEventsNo.addEventListener('click', function() {
        footerEventsModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === footerEventsModal) {
            footerEventsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && footerEventsModal.style.display === 'block') {
            footerEventsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>