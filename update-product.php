<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a supplier or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supplier' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: product-list.php');
    exit();
}

// Get product details
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: product-list.php?error=Product not found');
        exit();
    }
} catch (PDOException $e) {
    header('Location: product-list.php?error=Error loading product');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product - Professional Inventory System</title>
    <meta name="description" content="Update product information in your professional inventory management system">
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ===== PROFESSIONAL INVENTORY SYSTEM CSS 2025 ===== */
        
        /* CSS Reset & Variables */
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Modern Color Palette 2025 */
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93c5fd;
            --primary-400: #60a5fa;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-800: #1e40af;
            --primary-900: #1e3a8a;
            
            --success-50: #f0fdf4;
            --success-100: #dcfce7;
            --success-200: #bbf7d0;
            --success-300: #86efac;
            --success-400: #4ade80;
            --success-500: #22c55e;
            --success-600: #16a34a;
            --success-700: #15803d;
            --success-800: #166534;
            --success-900: #14532d;
            
            --warning-50: #fffbeb;
            --warning-100: #fef3c7;
            --warning-200: #fde68a;
            --warning-300: #fcd34d;
            --warning-400: #fbbf24;
            --warning-500: #f59e0b;
            --warning-600: #d97706;
            --warning-700: #b45309;
            --warning-800: #92400e;
            --warning-900: #78350f;
            
            --danger-50: #fef2f2;
            --danger-100: #fee2e2;
            --danger-200: #fecaca;
            --danger-300: #fca5a5;
            --danger-400: #f87171;
            --danger-500: #ef4444;
            --danger-600: #dc2626;
            --danger-700: #b91c1c;
            --danger-800: #991b1b;
            --danger-900: #7f1d1d;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Typography */
            --font-primary: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            --font-heading: 'Poppins', system-ui, sans-serif;
            
            /* Spacing */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --space-20: 5rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-3xl: 2rem;
            --radius-full: 9999px;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            /* Transitions */
            --transition-all: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-colors: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            --transition-transform: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-700) 100%);
            --gradient-success: linear-gradient(135deg, var(--success-500) 0%, var(--success-700) 100%);
            --gradient-hero: linear-gradient(135deg, var(--primary-600) 0%, var(--success-500) 50%, var(--primary-700) 100%);
            --gradient-glass: linear-gradient(145deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        }

        /* Base Styles */
        html {
            scroll-behavior: smooth;
            height: 100%;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: var(--font-primary);
            line-height: 1.6;
            color: var(--gray-800);
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--primary-50) 100%);
            min-height: 100vh;
            font-size: 16px;
            font-weight: 400;
            overflow-x: hidden;
            position: relative;
        }

        /* Professional background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(34, 197, 94, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: 600;
            line-height: 1.2;
            color: var(--gray-900);
            margin-bottom: var(--space-4);
            letter-spacing: -0.025em;
        }

        h1 { font-size: 2.25rem; font-weight: 800; }
        h2 { font-size: 1.875rem; font-weight: 700; }

        /* Container */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-6);
            position: relative;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: var(--space-8);
        }

        header .container {
            display: flex;
            flex-direction: column;
            gap: var(--space-6);
            padding: var(--space-8) var(--space-6);
        }

        header h1 {
            font-family: var(--font-heading);
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-hero);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin: 0;
            letter-spacing: -0.05em;
            position: relative;
        }

        header h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }

        /* Navigation Styles */
        nav {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: var(--space-2);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: var(--radius-3xl);
            padding: var(--space-6) var(--space-8);
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.3), transparent);
        }

        nav a {
            font-weight: 600;
            color: var(--gray-700);
            text-decoration: none;
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-2xl);
            transition: var(--transition-all);
            position: relative;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border: 2px solid transparent;
            white-space: nowrap;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-primary);
            opacity: 0;
            border-radius: var(--radius-2xl);
            transition: var(--transition-all);
            z-index: -1;
        }

        nav a:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            border-color: var(--primary-500);
        }

        nav a:hover::before {
            opacity: 1;
        }

        nav span {
            font-weight: 700;
            color: var(--success-800);
            background: linear-gradient(135deg, var(--success-50) 0%, var(--success-100) 100%);
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-2xl);
            border: 2px solid var(--success-200);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: var(--space-2);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        nav span::before {
            content: "👤";
            font-size: 1.1rem;
            animation: pulse 2s infinite;
        }

        /* Main Content */
        main {
            padding: var(--space-12) 0;
            min-height: calc(100vh - 200px);
            position: relative;
        }

        /* Form Container */
        .form-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-3xl);
            padding: var(--space-16);
            box-shadow: var(--shadow-2xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient-hero);
        }

        .form-container h2 {
            font-family: var(--font-heading);
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: var(--space-12);
            text-align: center;
            position: relative;
            padding-bottom: var(--space-6);
            letter-spacing: -0.05em;
        }

        .form-container h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: var(--space-10);
            position: relative;
            animation: slideInFromLeft 0.6s ease-out;
        }

        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.3s; }
        .form-group:nth-child(5) { animation-delay: 0.4s; }
        .form-group:nth-child(6) { animation-delay: 0.5s; }
        .form-group:nth-child(7) { animation-delay: 0.6s; }

        .form-group label {
            display: block;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: var(--space-4);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: relative;
            padding-left: var(--space-4);
        }

        .form-group label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }

        /* Enhanced Input Styles */
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: var(--space-5) var(--space-6);
            font-size: 1.1rem;
            font-family: var(--font-primary);
            border: 3px solid var(--gray-300);
            border-radius: var(--radius-2xl);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            transition: var(--transition-all);
            backdrop-filter: blur(10px);
            font-weight: 500;
            color: var(--gray-800);
            position: relative;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.1);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.95) 100%);
            transform: translateY(-2px);
        }

        input:hover,
        textarea:hover,
        select:hover {
            border-color: var(--primary-400);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            transform: translateY(-1px);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
            font-family: var(--font-primary);
            line-height: 1.6;
        }

        /* File Input Styling */
        input[type="file"] {
            padding: var(--space-8);
            border: 3px dashed var(--gray-400);
            background: linear-gradient(145deg, var(--gray-50) 0%, var(--primary-50) 100%);
            cursor: pointer;
            text-align: center;
            transition: var(--transition-all);
            position: relative;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        input[type="file"]::before {
            content: '📁 Choose New Image';
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-600);
        }

        input[type="file"]:hover {
            border-color: var(--primary-500);
            background: linear-gradient(145deg, var(--primary-50) 0%, var(--primary-100) 100%);
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        input[type="file"]:focus {
            border-color: var(--primary-600);
            background: linear-gradient(145deg, var(--primary-100) 0%, var(--primary-50) 100%);
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.1);
        }

        /* Current Image Display */
        .form-group img {
            max-width: 250px;
            height: auto;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            border: 4px solid white;
            transition: var(--transition-transform);
            margin: var(--space-4) 0;
        }

        .form-group img:hover {
            transform: scale(1.1) rotate(2deg);
            box-shadow: var(--shadow-2xl);
        }

        /* Enhanced Helper Text */
        small {
            color: var(--gray-600);
            font-size: 0.9rem;
            font-style: italic;
            margin-top: var(--space-2);
            display: block;
            padding-left: var(--space-4);
            border-left: 2px solid var(--gray-300);
        }

        /* Button System */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-3);
            padding: var(--space-5) var(--space-10);
            font-family: var(--font-primary);
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            border-radius: var(--radius-2xl);
            cursor: pointer;
            transition: var(--transition-all);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            min-width: 180px;
            box-shadow: var(--shadow-lg);
            border: 3px solid transparent;
        }

        /* Button shine effect */
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            transition: var(--transition-transform);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:focus {
            outline: 2px solid var(--primary-500);
            outline-offset: 4px;
        }

        .btn:active {
            transform: translateY(2px);
        }

        /* Button Variants */
        .btn-success {
            background: var(--gradient-success);
            color: white;
            border-color: var(--success-500);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--success-600) 0%, var(--success-800) 100%);
            transform: translateY(-4px);
            box-shadow: var(--shadow-2xl);
            border-color: var(--success-600);
            text-decoration: none;
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gray-500) 0%, var(--gray-700) 100%);
            color: white;
            border-color: var(--gray-500);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--gray-600) 0%, var(--gray-800) 100%);
            transform: translateY(-4px);
            box-shadow: var(--shadow-2xl);
            border-color: var(--gray-600);
            text-decoration: none;
            color: white;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: var(--space-8);
            margin-top: var(--space-16);
            justify-content: center;
            flex-wrap: wrap;
        }

        div[style*="display: flex"] {
            display: flex !important;
            gap: var(--space-8);
            margin-top: var(--space-16);
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Alert System */
        .alert {
            padding: var(--space-6) var(--space-8);
            border-radius: var(--radius-2xl);
            margin-bottom: var(--space-10);
            border: 3px solid transparent;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--space-4);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
            font-size: 1.1rem;
            animation: slideInFromTop 0.5s ease-out;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            border-radius: var(--radius-sm);
        }

        .alert::after {
            font-size: 1.5rem;
            margin-right: var(--space-2);
        }

        .alert-error {
            background: linear-gradient(135deg, var(--danger-50) 0%, rgba(239, 68, 68, 0.1) 100%);
            color: var(--danger-800);
            border-color: var(--danger-300);
        }

        .alert-error::before {
            background: var(--danger-500);
        }

        .alert-error::after {
            content: "⚠️";
        }

        .alert-success {
            background: linear-gradient(135deg, var(--success-50) 0%, rgba(34, 197, 94, 0.1) 100%);
            color: var(--success-800);
            border-color: var(--success-300);
        }

        .alert-success::before {
            background: var(--success-500);
        }

        .alert-success::after {
            content: "✅";
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 4px solid rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-600);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                padding: 0 var(--space-4);
            }
            
            nav {
                flex-direction: column;
                gap: var(--space-4);
                text-align: center;
                padding: var(--space-6);
            }
            
            .form-container {
                padding: var(--space-12);
                margin: 0 var(--space-4);
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .form-container h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 var(--space-3);
            }
            
            header .container {
                padding: var(--space-6) var(--space-4);
                gap: var(--space-4);
            }
            
            nav {
                flex-direction: column;
                padding: var(--space-4);
                gap: var(--space-3);
            }
            
            nav a,
            nav span {
                width: 100%;
                text-align: center;
                justify-content: center;
                font-size: 0.85rem;
                padding: var(--space-3) var(--space-4);
            }
            
            .form-container {
                padding: var(--space-8);
                margin: 0 var(--space-2);
                border-radius: var(--radius-2xl);
            }
            
            .form-container h2 {
                font-size: 1.75rem;
                margin-bottom: var(--space-8);
            }
            
            .form-group {
                margin-bottom: var(--space-8);
            }
            
            .btn {
                width: 100%;
                padding: var(--space-5) var(--space-6);
                font-size: 1rem;
                min-width: auto;
            }
            
            .form-actions,
            div[style*="display: flex"] {
                flex-direction: column;
                gap: var(--space-4);
            }
            
            header h1 {
                font-size: 1.75rem;
            }
            
            input,
            textarea,
            select {
                padding: var(--space-4) var(--space-5);
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 var(--space-2);
            }
            
            .form-container {
                padding: var(--space-6);
                margin: 0 var(--space-1);
            }
            
            .form-container h2 {
                font-size: 1.5rem;
            }
            
            nav a,
            nav span {
                font-size: 0.8rem;
                padding: var(--space-2) var(--space-3);
            }
            
            .alert {
                padding: var(--space-4) var(--space-5);
                font-size: 1rem;
            }
            
            header h1 {
                font-size: 1.5rem;
            }
        }

        /* Accessibility Enhancements */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus indicators for keyboard navigation */
        .btn:focus-visible,
        input:focus-visible,
        textarea:focus-visible,
        select:focus-visible,
        nav a:focus-visible {
            outline: 3px solid var(--primary-500);
            outline-offset: 4px;
            border-radius: var(--radius-md);
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
            }
            
            header,
            nav,
            .btn {
                display: none !important;
            }
            
            .form-container {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                background: white !important;
            }
            
            input,
            textarea,
            select {
                border: 1px solid #ccc !important;
                background: white !important;
            }
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .hidden { display: none; }
        .block { display: block; }
        .flex { display: flex; }
        .w-full { width: 100%; }
        .h-auto { height: auto; }
        .transition-all { transition: var(--transition-all); }
        .cursor-pointer { cursor: pointer; }
        .font-bold { font-weight: 700; }
        .text-primary { color: var(--primary-600); }
        .text-success { color: var(--success-600); }
        .text-danger { color: var(--danger-600); }
        .bg-white { background-color: white; }
        .rounded-lg { border-radius: var(--radius-lg); }
        .shadow-lg { box-shadow: var(--shadow-lg); }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <h1>🏪 Professional Inventory System</h1>
            <nav role="navigation" aria-label="Main navigation">
                <a href="index.php" class="nav-item">🏠 Home</a>
                <a href="supplier-dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="product-list.php" class="nav-item">📦 My Products</a>
                <a href="add-product.php" class="nav-item">➕ Add Product</a>
                <span class="user-info">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Supplier)
                </span>
                <a href="logout.php" class="nav-item logout">🚪 Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container">
            <div class="form-container">
                <h2>✏️ Update Product Information</h2>
                
                <!-- Flash Messages -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Update Form -->
                <form action="update-product-action.php" method="POST" enctype="multipart/form-data" novalidate id="updateProductForm">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">📦 Product Name:</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($product['name']); ?>" 
                            required
                            minlength="2"
                            maxlength="200"
                            placeholder="Enter product name"
                            aria-describedby="name-help"
                        >
                        <small id="name-help">Provide a clear, descriptive name for your product (2-200 characters)</small>
                    </div>

                    <div class="form-group">
                        <label for="description">📝 Product Description:</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            required
                            minlength="10"
                            maxlength="1000"
                            placeholder="Describe your product features, benefits, and specifications..."
                            aria-describedby="description-help"
                        ><?php echo htmlspecialchars($product['description']); ?></textarea>
                        <small id="description-help">Provide detailed information to help customers make informed decisions (10-1000 characters)</small>
                    </div>

                    <div class="form-group">
                        <label for="price">💰 Price (USD):</label>
                        <input 
                            type="number" 
                            id="price" 
                            name="price" 
                            step="0.01" 
                            min="0.01" 
                            max="999999.99"
                            value="<?php echo $product['price']; ?>" 
                            required
                            placeholder="0.00"
                            aria-describedby="price-help"
                        >
                        <small id="price-help">Set competitive pricing for your product (minimum ₹0.01)</small>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">📊 Stock Quantity:</label>
                        <input 
                            type="number" 
                            id="stock_quantity" 
                            name="stock_quantity" 
                            min="0" 
                            max="999999"
                            value="<?php echo $product['stock_quantity']; ?>" 
                            required
                            placeholder="0"
                            aria-describedby="stock-help"
                        >
                        <small id="stock-help">Current number of items available in inventory</small>
                    </div>

                    <?php if (!empty($product['image'])): ?>
                        <div class="form-group">
                            <label>🖼️ Current Product Image:</label>
                            <div class="current-image-container">
                                <img 
                                    src="<?php echo htmlspecialchars($product['image']); ?>" 
                                    alt="Current product image for <?php echo htmlspecialchars($product['name']); ?>" 
                                    class="current-product-image"
                                    loading="lazy"
                                >
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="image">🎨 Upload New Image (Optional):</label>
                        <input 
                            type="file" 
                            id="image" 
                            name="image" 
                            accept=".jpg,.jpeg,.jfif,.png,.gif,.webp,.bmp,.svg,.tiff,.tif,image/*"
                            aria-describedby="image-help"
                        >
                        <small id="image-help">
                            📸 Supported formats: JPG, PNG, GIF, WebP (Max: 5MB)<br>
                            Leave empty to keep current image
                        </small>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div style="display: flex; gap: 2rem; margin-top: 3rem; justify-content: center; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            💾 Update Product
                        </button>
                        <a href="product-list.php" class="btn btn-secondary">
                            ↩️ Back to Products
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Enhanced JavaScript -->
    <script>
        // Professional JavaScript Enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation and enhancement
            const form = document.getElementById('updateProductForm');
            const submitBtn = document.getElementById('submitBtn');
            
            // Price formatting
            const priceInput = document.getElementById('price');
            if (priceInput) {
                priceInput.addEventListener('blur', function() {
                    const value = parseFloat(this.value);
                    if (!isNaN(value)) {
                        this.value = value.toFixed(2);
                    }
                });

                // Real-time price validation
                priceInput.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    const formGroup = this.closest('.form-group');
                    
                    if (value <= 0 || isNaN(value)) {
                        this.style.borderColor = 'var(--danger-500)';
                        this.style.background = 'var(--danger-50)';
                    } else {
                        this.style.borderColor = 'var(--success-500)';
                        this.style.background = 'var(--success-50)';
                    }
                });
            }

            // Stock quantity validation with warnings
            const stockInput = document.getElementById('stock_quantity');
            if (stockInput) {
                stockInput.addEventListener('input', function() {
                    const value = parseInt(this.value);
                    const formGroup = this.closest('.form-group');
                    
                    // Remove existing warnings
                    const existingWarning = formGroup.querySelector('.stock-warning');
                    if (existingWarning) {
                        existingWarning.remove();
                    }
                    
                    if (value <= 10 && value > 0) {
                        this.style.borderColor = 'var(--warning-500)';
                        this.style.background = 'var(--warning-50)';
                        
                        const warningMsg = document.createElement('div');
                        warningMsg.className = 'stock-warning';
                        warningMsg.style.cssText = `
                            color: var(--warning-600);
                            font-size: 0.9rem;
                            margin-top: var(--space-2);
                            display: flex;
                            align-items: center;
                            gap: var(--space-2);
                            padding: var(--space-2) var(--space-3);
                            background: var(--warning-50);
                            border-radius: var(--radius-md);
                            border-left: 4px solid var(--warning-500);
                        `;
                        warningMsg.innerHTML = '⚠️ Low stock level - consider restocking soon';
                        formGroup.appendChild(warningMsg);
                    } else if (value === 0) {
                        this.style.borderColor = 'var(--danger-500)';
                        this.style.background = 'var(--danger-50)';
                        
                        const warningMsg = document.createElement('div');
                        warningMsg.className = 'stock-warning';
                        warningMsg.style.cssText = `
                            color: var(--danger-600);
                            font-size: 0.9rem;
                            margin-top: var(--space-2);
                            display: flex;
                            align-items: center;
                            gap: var(--space-2);
                            padding: var(--space-2) var(--space-3);
                            background: var(--danger-50);
                            border-radius: var(--radius-md);
                            border-left: 4px solid var(--danger-500);
                        `;
                        warningMsg.innerHTML = '🚫 Out of stock - product not available for purchase';
                        formGroup.appendChild(warningMsg);
                    } else {
                        this.style.borderColor = 'var(--success-500)';
                        this.style.background = 'var(--success-50)';
                    }
                });
            }

            // Enhanced file upload with preview
            const fileInput = document.getElementById('image');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // File size validation (5MB max)
                        if (file.size > 5 * 1024 * 1024) {
                            showNotification('File size must be less than 5MB', 'error');
                            this.value = '';
                            return;
                        }

                        // File type validation - support all common image formats
                        const allowedTypes = [
                            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                            'image/bmp', 'image/x-windows-bmp', 'image/svg+xml',
                            'image/tiff', 'image/x-tiff'
                        ];
                        if (!allowedTypes.includes(file.type)) {
                            showNotification('Please select a valid image file (JPG, PNG, GIF, WebP, BMP, SVG, TIFF)', 'error');
                            this.value = '';
                            return;
                        }

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            let previewContainer = document.querySelector('.new-image-preview');
                            if (!previewContainer) {
                                previewContainer = document.createElement('div');
                                previewContainer.className = 'new-image-preview';
                                previewContainer.style.cssText = `
                                    margin-top: var(--space-4);
                                    padding: var(--space-6);
                                    border: 3px solid var(--success-400);
                                    border-radius: var(--radius-2xl);
                                    background: linear-gradient(135deg, var(--success-50) 0%, var(--primary-50) 100%);
                                    text-align: center;
                                    animation: fadeInUp 0.5s ease-out;
                                `;
                                fileInput.parentNode.appendChild(previewContainer);
                            }
                            
                            previewContainer.innerHTML = `
                                <h4 style="color: var(--success-700); margin-bottom: var(--space-3); font-family: var(--font-heading); font-weight: 600;">
                                    📸 New Image Preview
                                </h4>
                                <img src="${e.target.result}" alt="New product image preview" 
                                     style="max-width: 250px; height: auto; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 4px solid white; transition: var(--transition-transform);"
                                     onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <p style="color: var(--success-600); margin-top: var(--space-3); font-weight: 600;">
                                    📎 ${file.name} <br>
                                    📏 ${(file.size / 1024).toFixed(1)} KB
                                </p>
                            `;
                        };
                        reader.readAsDataURL(file);
                        showNotification('Image selected successfully!', 'success');
                    }
                });
            }

            // Form submission enhancement
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Basic validation
                    const requiredFields = form.querySelectorAll('input[required], textarea[required]');
                    let hasError = false;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = 'var(--danger-500)';
                            field.style.background = 'var(--danger-50)';
                            hasError = true;
                        }
                    });

                    if (hasError) {
                        e.preventDefault();
                        showNotification('Please fill in all required fields', 'error');
                        return;
                    }

                    // Show loading state
                    submitBtn.innerHTML = '⏳ Updating Product...';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                    
                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.innerHTML = '💾 Update Product';
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                    }, 10000);
                });
            }

            // Character counters for text inputs
            const nameInput = document.getElementById('name');
            const descInput = document.getElementById('description');

            if (nameInput) {
                addCharacterCounter(nameInput, 200);
            }
            if (descInput) {
                addCharacterCounter(descInput, 1000);
            }

            // Auto-save functionality (optional)
            let autoSaveTimeout;
            const formInputs = form.querySelectorAll('input, textarea');
            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(() => {
                        saveFormData();
                    }, 2000);
                });
            });

            // Load saved form data on page load
            loadFormData();
        });

        // Utility functions
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: var(--space-8);
                right: var(--space-8);
                z-index: 10000;
                min-width: 350px;
                animation: slideInFromRight 0.5s ease-out;
                cursor: pointer;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);

            // Click to dismiss
            notification.addEventListener('click', () => {
                notification.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            });
        }

        function addCharacterCounter(input, maxLength) {
            const counter = document.createElement('div');
            counter.style.cssText = `
                font-size: 0.8rem;
                color: var(--gray-500);
                text-align: right;
                margin-top: var(--space-1);
            `;
            
            const updateCounter = () => {
                const remaining = maxLength - input.value.length;
                counter.textContent = `${input.value.length}/${maxLength} characters`;
                
                if (remaining < 20) {
                    counter.style.color = 'var(--warning-600)';
                } else if (remaining < 0) {
                    counter.style.color = 'var(--danger-600)';
                } else {
                    counter.style.color = 'var(--gray-500)';
                }
            };
            
            input.parentNode.appendChild(counter);
            input.addEventListener('input', updateCounter);
            updateCounter();
        }

        function saveFormData() {
            const formData = {};
            const form = document.getElementById('updateProductForm');
            const formInputs = form.querySelectorAll('input:not([type="file"]), textarea, select');
            
            formInputs.forEach(input => {
                if (input.name && input.value) {
                    formData[input.name] = input.value;
                }
            });
            
            localStorage.setItem('updateProductForm', JSON.stringify(formData));
        }

        function loadFormData() {
            const savedData = localStorage.getItem('updateProductForm');
            if (savedData) {
                try {
                    const formData = JSON.parse(savedData);
                    Object.keys(formData).forEach(key => {
                        const input = document.querySelector(`[name="${key}"]`);
                        if (input && input.type !== 'file') {
                            // Only load if current value is empty (don't override server data)
                            if (!input.value) {
                                input.value = formData[key];
                            }
                        }
                    });
                } catch (e) {
                    console.log('Error loading saved form data');
                }
            }
        }

        // Additional animations CSS
        const additionalStyles = `
            @keyframes slideInFromRight {
                from {
                    opacity: 0;
                    transform: translateX(100px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;

        const styleSheet = document.createElement('style');
        styleSheet.textContent = additionalStyles;
        document.head.appendChild(styleSheet);

        // Handle page visibility changes for auto-save
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                saveFormData();
            }
        });

        // Clear saved data on successful form submission
        window.addEventListener('beforeunload', () => {
            // Only clear if form was actually submitted
            const form = document.getElementById('updateProductForm');
            if (form && form.dataset.submitted) {
                localStorage.removeItem('updateProductForm');
            }
        });
    </script>
</body>
</html>
