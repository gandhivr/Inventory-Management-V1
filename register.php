<?php
// Start a new PHP session or resume the existing one
// This enables the use of $_SESSION variables across pages
// Sessions allow data to persist across multiple page requests 
// for the same user
// Essential for maintaining user login state and storing temporary data
session_start();

// Check if the user is already logged in by looking for 'user_id' in the session
// isset() function checks if a variable is set and is not NULL
// $_SESSION['user_id'] would be set when a user successfully logs in
if (isset($_SESSION['user_id'])) {
    // If user is already logged in, redirect them to the main index.php page
    // This prevents logged-in users from accessing the registration page
    // header() function sends a raw HTTP header to redirect the browser
    header('Location: index.php');
    // Stop executing the rest of the PHP code after redirect
    // exit() prevents any HTML below from being processed or sent to browser
    // This is crucial to prevent the registration form from showing
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Professional Inventory Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Professional Dark Theme Register Page */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif !important;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%) !important;
            background-attachment: fixed;
            min-height: 100vh;
            color: #e2e8f0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 0, 212, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 255, 136, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .register-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            min-height: 700px;
            background: rgba(30, 41, 59, 0.9) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(25px);
            border: 1px solid rgba(100, 116, 139, 0.2) !important;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #00d4ff, #ff00d4, #00ff88);
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Left Side - Branding */
        .left-side {
            flex: 1;
            background: rgba(15, 23, 42, 0.8);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #f1f5f9 !important;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(15px);
        }

        .left-side::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, transparent, rgba(100, 116, 139, 0.3), transparent);
        }

        .brand-content {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            font-size: 4rem !important;
            margin-bottom: 32px;
            text-align: center;
            filter: drop-shadow(0 0 20px rgba(0, 212, 255, 0.3));
        }

        .brand-title {
            font-size: 3rem !important;
            font-weight: 900 !important;
            margin-bottom: 24px;
            line-height: 1.1;
            background: linear-gradient(135deg, #00d4ff, #ff00d4) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
        }

        .brand-subtitle {
            color: #cbd5e1 !important;
            font-size: 1.25rem;
            margin-bottom: 48px;
            line-height: 1.6;
            font-weight: 400;
        }

        .features-preview {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(100, 116, 139, 0.3);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            transform: translateX(8px);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }

        .feature-text h4 {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
            margin-bottom: 4px;
            color: #f1f5f9 !important;
        }

        .feature-text p {
            font-size: 0.9rem !important;
            color: #cbd5e1 !important;
            line-height: 1.4;
        }

        /* Right Side - Form */
        .right-side {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            background: rgba(30, 41, 59, 0.8);
            overflow-y: auto;
            backdrop-filter: blur(15px);
        }

        .form-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 2.5rem !important;
            font-weight: 900 !important;
            color: #f1f5f9 !important;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #00d4ff, #ff00d4) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
        }

        .form-header p {
            color: #cbd5e1 !important;
            font-size: 1.125rem;
            font-weight: 400;
        }

        /* Form Styles */
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-weight: 600 !important;
            color: #f1f5f9 !important;
            font-size: 0.95rem;
            letter-spacing: 0.025em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            font-size: 1.125rem;
            color: #64748b !important;
            z-index: 1;
            transition: all 0.2s ease;
        }

        .form-input {
            width: 100%;
            padding: 16px 16px 16px 48px !important;
            border: 2px solid rgba(100, 116, 139, 0.3) !important;
            border-radius: 12px !important;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: rgba(15, 23, 42, 0.8) !important;
            color: #f1f5f9 !important;
            font-family: inherit;
            backdrop-filter: blur(10px);
        }

        .form-input::placeholder {
            color: #64748b !important;
        }

        .form-input:focus {
            outline: none;
            border-color: #00d4ff !important;
            background: rgba(15, 23, 42, 0.9) !important;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3) !important;
        }

        .form-input:focus + .input-icon {
            color: #00d4ff !important;
        }

        .form-help {
            font-size: 0.8125rem !important;
            color: #64748b !important;
            line-height: 1.4;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background: rgba(100, 116, 139, 0.3);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-fill.very-weak { width: 20%; background: #ef4444; }
        .strength-fill.weak { width: 40%; background: #f59e0b; }
        .strength-fill.fair { width: 60%; background: #eab308; }
        .strength-fill.good { width: 80%; background: #22c55e; }
        .strength-fill.strong { width: 100%; background: #10b981; }

        .strength-text {
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .strength-text.very-weak { color: #ef4444 !important; }
        .strength-text.weak { color: #f59e0b !important; }
        .strength-text.fair { color: #eab308 !important; }
        .strength-text.good { color: #22c55e !important; }
        .strength-text.strong { color: #10b981 !important; }

        /* Role Selection */
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 12px;
        }

        .role-option {
            position: relative;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .role-card {
            display: block !important;
            padding: 24px 20px !important;
            border: 2px solid rgba(100, 116, 139, 0.3) !important;
            border-radius: 16px !important;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(15, 23, 42, 0.6) !important;
            text-align: center;
            height: 100%;
            backdrop-filter: blur(10px);
        }

        .role-card:hover {
            border-color: #00d4ff !important;
            background: rgba(15, 23, 42, 0.8) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        }

        .role-option input[type="radio"]:checked + .role-card {
            border-color: #00d4ff !important;
            background: rgba(0, 212, 255, 0.1) !important;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3) !important;
        }

        .role-icon {
            font-size: 2.5rem !important;
            margin-bottom: 12px;
            display: block;
        }

        .role-content h4 {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
            color: #f1f5f9 !important;
            margin-bottom: 8px;
        }

        .role-content p {
            font-size: 0.875rem !important;
            color: #cbd5e1 !important;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .role-features {
            list-style: none !important;
            padding: 0 !important;
            font-size: 0.8125rem !important;
            color: #64748b !important;
        }

        .role-features li {
            padding: 2px 0 !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .role-features li::before {
            content: '✓' !important;
            color: #00d4ff !important;
            font-weight: 600 !important;
        }

        /* Profile Section Styling */
        .profile-section-title {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #f1f5f9 !important;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: rgba(15, 23, 42, 0.6) !important;
            border-radius: 12px !important;
            border: 1px solid rgba(100, 116, 139, 0.3) !important;
            backdrop-filter: blur(10px);
        }

        .buyer-fields, .supplier-fields {
            padding: 20px !important;
            background: rgba(15, 23, 42, 0.4) !important;
            border-radius: 16px !important;
            border: 1px solid rgba(100, 116, 139, 0.2) !important;
            backdrop-filter: blur(10px);
        }

        /* Checkbox Styling */
        .checkbox-group {
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
            padding: 16px !important;
            background: rgba(15, 23, 42, 0.6) !important;
            border-radius: 12px !important;
            border: 1px solid rgba(100, 116, 139, 0.3) !important;
            backdrop-filter: blur(10px);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px !important;
            height: 20px !important;
            border: 2px solid rgba(100, 116, 139, 0.5) !important;
            border-radius: 4px !important;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
            background: rgba(15, 23, 42, 0.8) !important;
            appearance: none;
            position: relative;
        }

        .checkbox-group input[type="checkbox"]:checked {
            background: #00d4ff !important;
            border-color: #00d4ff !important;
        }

        .checkbox-group input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #0f172a !important;
            font-weight: bold;
            font-size: 12px;
        }

        .checkbox-label {
            font-size: 0.9rem !important;
            color: #cbd5e1 !important;
            line-height: 1.5;
            cursor: pointer;
        }

        .link {
            color: #00d4ff !important;
            text-decoration: none;
            font-weight: 600 !important;
            transition: color 0.2s ease;
        }

        .link:hover {
            color: #0ea5e9 !important;
            text-decoration: underline;
        }

        /* Register Button */
        .register-btn {
            width: 100% !important;
            padding: 18px 24px !important;
            background: linear-gradient(135deg, #00d4ff, #0ea5e9) !important;
            color: #0f172a !important;
            border: none !important;
            border-radius: 12px !important;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 12px !important;
            margin-top: 8px;
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3) !important;
        }

        .register-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 35px rgba(0, 212, 255, 0.5) !important;
            background: linear-gradient(135deg, #0ea5e9, #0284c7) !important;
        }

        .btn-text {
            font-weight: 700 !important;
        }

        .btn-icon {
            font-size: 1.25rem !important;
        }

        /* Form Footer */
        .form-footer {
            text-align: center !important;
            margin-top: 32px !important;
            padding-top: 24px !important;
            border-top: 1px solid rgba(100, 116, 139, 0.3) !important;
        }

        .form-footer p {
            color: #cbd5e1 !important;
            margin-bottom: 16px !important;
        }

        .link-secondary {
            color: #64748b !important;
            text-decoration: none;
            font-size: 0.9rem !important;
            transition: color 0.2s ease;
        }

        .link-secondary:hover {
            color: #cbd5e1 !important;
        }

        /* Alert Styling */
        .alert {
            padding: 16px 20px !important;
            border-radius: 12px !important;
            margin-bottom: 24px !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
            border: 1px solid;
            backdrop-filter: blur(10px);
        }

        .alert-icon {
            font-size: 1.25rem !important;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content strong {
            display: block !important;
            font-weight: 600 !important;
            margin-bottom: 4px !important;
        }

        .alert-content p {
            margin: 0 !important;
            font-size: 0.9rem !important;
            line-height: 1.4;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2) !important;
            color: #34d399 !important;
            border-color: #10b981 !important;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2) !important;
            color: #f87171 !important;
            border-color: #ef4444 !important;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .register-container {
                flex-direction: column;
                max-width: 600px;
                min-height: auto;
            }

            .left-side::after {
                width: 100%;
                height: 1px;
                top: auto;
                bottom: 0;
                right: 0;
                background: linear-gradient(90deg, transparent, rgba(100, 116, 139, 0.3), transparent);
            }

            .left-side {
                padding: 40px 30px;
                text-align: center;
            }

            .brand-title {
                font-size: 2.5rem !important;
            }

            .features-preview {
                flex-direction: row;
                gap: 16px;
                overflow-x: auto;
                padding-bottom: 8px;
            }

            .feature-item {
                min-width: 200px;
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }

            .right-side {
                padding: 40px 30px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .register-container {
                max-width: none;
                margin: 0;
            }

            .left-side, .right-side {
                padding: 30px 20px;
            }

            .form-header h2 {
                font-size: 2rem !important;
            }

            .brand-title {
                font-size: 2rem !important;
            }

            .form-row, .role-selection {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .features-preview {
                flex-direction: column;
            }

            .feature-item {
                min-width: auto;
                flex-direction: row;
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .left-side, .right-side {
                padding: 20px 16px;
            }

            .form-header h2 {
                font-size: 1.75rem !important;
            }

            .brand-title {
                font-size: 1.75rem !important;
            }

            .form-input {
                padding: 14px 14px 14px 44px !important;
                font-size: 0.95rem !important;
            }

            .role-card {
                padding: 20px 16px !important;
            }

            .register-btn {
                padding: 16px 20px !important;
                font-size: 1rem !important;
            }
        }
    </style>
    <meta name="description" content="Join our professional inventory management platform. Create your account and start managing your business efficiently.">
    <!-- Debug: Check if CSS file exists -->
    <?php if (file_exists('css/register.css')): ?>
        <!-- CSS file exists -->
    <?php else: ?>
        <style>body::after { content: "CSS file missing!"; position: fixed; top: 0; left: 0; background: red; color: white; padding: 10px; z-index: 9999; }</style>
    <?php endif; ?>
</head>
<body>
    <div class="register-container">
        <!-- Left Side - Branding -->
        <div class="left-side">
            <div class="brand-content">
                <div class="brand-logo">📦</div>
                <h1 class="brand-title">Join Our Platform</h1>
                <p class="brand-subtitle">Create your account and unlock powerful inventory management tools designed for modern businesses.</p>
                
                <div class="features-preview">
                    <div class="feature-item">
                        <div class="feature-icon">🚀</div>
                        <div class="feature-text">
                            <h4>Advanced Analytics</h4>
                            <p>Real-time insights and business intelligence</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        <div class="feature-text">
                            <h4>Enterprise Security</h4>
                            <p>Bank-level security for your business data</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📱</div>
                        <div class="feature-text">
                            <h4>Mobile Optimized</h4>
                            <p>Access your inventory anywhere, anytime</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Right Side - Registration Form -->
        <div class="right-side">
            <div class="form-container">
                <div class="form-header">
                    <h2>Create Your Account</h2>
                    <p>Join thousands of businesses using our platform</p>
                </div>


                <?php 
                // Check if there's an 'error' parameter in the URL (GET request)
                // This would be set when registration fails and user is redirected back
                // $_GET is a PHP superglobal that contains data sent via HTTP GET method
                // This typically happens when register-action.php encounters an error
                // and redirects back with: header('Location: register.php?error=message')
                if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon">⚠️</div>
                        <div class="alert-content">
                            <strong>Registration Failed</strong>
                            <!-- Display the error message from URL parameter -->
                            <!-- htmlspecialchars() prevents XSS attacks by escaping HTML characters -->
                            <!-- This converts characters like <, >, &, ", ' into HTML entities -->
                            <!-- Essential security measure to prevent malicious code injection -->
                            <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                        </div>
                    </div>
                <?php 
                // endif; is the closing tag for PHP's alternative if syntax
                // This syntax is cleaner when mixing PHP with HTML
                endif; ?>


                <?php 
                // Check if there's a 'success' parameter in the URL (GET request)
                // This would be set when registration is successful
                // Used to display confirmation messages to the user
                // Example URL: register.php?success=Account created successfully
                if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon">✅</div>
                        <div class="alert-content">
                            <strong>Success!</strong>
                            <!-- Display the success message from URL parameter -->
                            <!-- htmlspecialchars() prevents XSS attacks by escaping HTML characters -->
                            <!-- Even success messages need sanitization for security -->
                            <p><?php echo htmlspecialchars($_GET['success']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>


                <form action="register-action.php" method="POST" class="register-form" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <div class="input-icon">👤</div>
                                <input type="text" id="username" name="username" class="form-input" 
                                       placeholder="Enter your username" required minlength="3" maxlength="50">
                            </div>
                            <div class="form-help">Choose a unique username (3-50 characters)</div>
                        </div>


                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <div class="input-icon">📧</div>
                                <input type="email" id="email" name="email" class="form-input" 
                                       placeholder="Enter your email address" required>
                            </div>
                            <div class="form-help">We'll use this for account verification</div>
                        </div>
                    </div>


                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <div class="input-icon">🔒</div>
                                <input type="password" id="password" name="password" class="form-input" 
                                       placeholder="Create a strong password" required minlength="6">
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText">Password strength</div>
                            </div>
                        </div>


                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <div class="input-icon">🔐</div>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       placeholder="Confirm your password" required>
                            </div>
                            <div class="form-help" id="passwordMatch">Passwords must match</div>
                        </div>
                    </div>


                    <div class="form-group">
                        <label for="role" class="form-label">Account Type</label>
                        <div class="role-selection">
                            <div class="role-option">
                                <input type="radio" id="buyer" name="role" value="buyer" required>
                                <label for="buyer" class="role-card">
                                    <div class="role-icon">🛒</div>
                                    <div class="role-content">
                                        <h4>Buyer Account</h4>
                                        <p>Browse products, manage orders, and track purchases</p>
                                        <ul class="role-features">
                                            <li>Product browsing</li>
                                            <li>Order management</li>
                                            <li>Purchase history</li>
                                        </ul>
                                    </div>
                                </label>
                            </div>


                            <div class="role-option">
                                <input type="radio" id="supplier" name="role" value="supplier" required>
                                <label for="supplier" class="role-card">
                                    <div class="role-icon">🏭</div>
                                    <div class="role-content">
                                        <h4>Supplier Account</h4>
                                        <p>Add products, manage inventory, and track sales</p>
                                        <ul class="role-features">
                                            <li>Product management</li>
                                            <li>Inventory tracking</li>
                                            <li>Sales analytics</li>
                                        </ul>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>


                    <!-- BUYER PROFILE FIELDS -->
                    <!-- These fields are initially hidden and only shown when buyer role is selected -->
                    <!-- JavaScript toggles visibility based on radio button selection -->
                    <!-- CSS display: none hides the section until buyer is selected -->
                    <div class="form-group buyer-fields" id="buyerFields" style="display: none;">
                        <h3 class="profile-section-title">📍 Buyer Profile Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="buyer_address" class="form-label">Address</label>
                                <div class="input-group">
                                    <div class="input-icon">🏠</div>
                                    <textarea id="buyer_address" name="buyer_address" class="form-input" 
                                              placeholder="Enter your complete address" rows="3"></textarea>
                                </div>
                                <div class="form-help">Your delivery address for orders</div>
                            </div>


                            <div class="form-group">
                                <label for="buyer_phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <div class="input-icon">📱</div>
                                    <input type="tel" id="buyer_phone" name="buyer_phone" class="form-input" 
                                           placeholder="Enter your phone number" maxlength="20">
                                </div>
                                <div class="form-help">We'll contact you for order updates</div>
                            </div>
                        </div>
                    </div>


                    <!-- SUPPLIER PROFILE FIELDS (UPDATED - Removed contact_person) -->
                    <!-- These fields are initially hidden and only shown when supplier role is selected -->
                    <!-- Dynamic field visibility improves user experience by showing relevant fields only -->
                    <!-- JavaScript handles the show/hide logic and required field validation -->
                    <div class="form-group supplier-fields" id="supplierFields" style="display: none;">
                        <h3 class="profile-section-title">🏭 Supplier Profile Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="supplier_company" class="form-label">Company Name</label>
                                <div class="input-group">
                                    <div class="input-icon">🏢</div>
                                    <input type="text" id="supplier_company" name="supplier_company" class="form-input" 
                                           placeholder="Enter your company name" maxlength="100">
                                </div>
                                <div class="form-help">Your business or company name</div>
                            </div>


                            <div class="form-group">
                                <label for="supplier_phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <div class="input-icon">📱</div>
                                    <input type="tel" id="supplier_phone" name="supplier_phone" class="form-input" 
                                           placeholder="Enter business phone" maxlength="20">
                                </div>
                                <div class="form-help">Business contact number</div>
                            </div>
                        </div>


                        <div class="form-row">
                            <div class="form-group">
                                <label for="supplier_address" class="form-label">Business Address</label>
                                <div class="input-group">
                                    <div class="input-icon">🏠</div>
                                    <textarea id="supplier_address" name="supplier_address" class="form-input" 
                                              placeholder="Enter business address" rows="2"></textarea>
                                </div>
                                <div class="form-help">Your business location</div>
                            </div>
                        </div>
                    </div>


                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms" class="checkbox-label">
                                I agree to the <a href="#" class="link">Terms of Service</a> and <a href="#" class="link">Privacy Policy</a>
                            </label>
                        </div>
                    </div>


                    <button type="submit" class="register-btn" id="registerBtn">
                        <span class="btn-text">Create My Account</span>
                        <div class="btn-icon">🚀</div>
                    </button>
                </form>


                <div class="form-footer">
                    <p>Already have an account? <a href="login.php" class="link">Sign in here</a></p>
                    <div class="back-home">
                        <a href="index.php" class="link-secondary">← Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Password strength checker functionality
        // Get references to DOM elements that will be used for password validation
        const password = document.getElementById('password'); // Main password input field
        const confirmPassword = document.getElementById('confirm_password'); // Password confirmation field
        const strengthFill = document.getElementById('strengthFill'); // Visual strength bar fill element
        const strengthText = document.getElementById('strengthText'); // Text displaying strength level
        const passwordMatch = document.getElementById('passwordMatch'); // Element showing password match status

        // Add event listener to password field that triggers on every keystroke
        // This provides real-time password strength feedback as user types
        password.addEventListener('input', function() {
            const value = this.value; // Get current password value
            let strength = 0; // Initialize strength counter (0-5 scale)
            let text = ''; // Will hold the strength description text
            let className = ''; // Will hold the CSS class for styling

            // Check password against 5 different criteria
            // Each criterion met increases strength score by 1
            if (value.length >= 6) strength++; // Minimum 6 characters
            if (value.match(/[a-z]/)) strength++; // Contains lowercase letters
            if (value.match(/[A-Z]/)) strength++; // Contains uppercase letters  
            if (value.match(/[0-9]/)) strength++; // Contains numbers
            if (value.match(/[^a-zA-Z0-9]/)) strength++; // Contains special characters (anything not alphanumeric)

            // Determine strength level and corresponding text/styling based on score
            // Uses switch statement to handle different strength levels
            switch(strength) {
                case 0: // No criteria met
                case 1: // Only 1 criterion met
                    text = 'Very Weak'; // Lowest security level
                    className = 'very-weak'; // CSS class for red/danger styling
                    break;
                case 2: // 2 criteria met
                    text = 'Weak'; // Poor security level
                    className = 'weak'; // CSS class for orange/warning styling
                    break;
                case 3: // 3 criteria met
                    text = 'Fair'; // Acceptable security level
                    className = 'fair'; // CSS class for yellow/caution styling
                    break;
                case 4: // 4 criteria met
                    text = 'Good'; // Good security level
                    className = 'good'; // CSS class for light green styling
                    break;
                case 5: // All 5 criteria met
                    text = 'Strong'; // Maximum security level
                    className = 'strong'; // CSS class for green/success styling
                    break;
            }

            // Update the visual strength indicator
            strengthFill.className = 'strength-fill ' + className; // Apply CSS class to strength bar
            strengthText.textContent = text; // Update strength text display
            strengthText.className = 'strength-text ' + className; // Apply CSS class to text
        });

        // Password confirmation validation
        // Add event listener to confirm password field for real-time matching feedback
        confirmPassword.addEventListener('input', function() {
            // Check if passwords match and confirmation field is not empty
            if (this.value === password.value && this.value !== '') {
                passwordMatch.textContent = '✅ Passwords match'; // Success message with checkmark
                passwordMatch.style.color = '#059669'; // Green color for success
            } else if (this.value !== '') { // Confirmation field has content but doesn't match
                passwordMatch.textContent = '❌ Passwords do not match'; // Error message with X
                passwordMatch.style.color = '#dc2626'; // Red color for error
            } else { // Confirmation field is empty
                passwordMatch.textContent = 'Passwords must match'; // Default instruction text
                passwordMatch.style.color = '#6b7280'; // Gray color for neutral state
            }
        });

        // Role-based dynamic form fields functionality
        // Get references to radio buttons and field containers for buyer/supplier sections
        const buyerRadio = document.getElementById('buyer'); // Buyer role radio button
        const supplierRadio = document.getElementById('supplier'); // Supplier role radio button
        const buyerFields = document.getElementById('buyerFields'); // Container for buyer-specific fields
        const supplierFields = document.getElementById('supplierFields'); // Container for supplier-specific fields

        // Function to show/hide appropriate fields based on selected role
        // Also manages which fields are required for form validation
        function toggleProfileFields() {
            if (buyerRadio.checked) { // User selected buyer role
                buyerFields.style.display = 'block'; // Show buyer fields
                supplierFields.style.display = 'none'; // Hide supplier fields
                
                // Make buyer fields required for form submission validation
                document.getElementById('buyer_address').required = true;
                document.getElementById('buyer_phone').required = true;
                
                // Remove requirement from supplier fields (since they're hidden)
                document.getElementById('supplier_company').required = false;
                document.getElementById('supplier_phone').required = false;
                document.getElementById('supplier_address').required = false;
                
            } else if (supplierRadio.checked) { // User selected supplier role
                buyerFields.style.display = 'none'; // Hide buyer fields
                supplierFields.style.display = 'block'; // Show supplier fields
                
                // Make supplier fields required for form submission validation
                document.getElementById('supplier_company').required = true;
                document.getElementById('supplier_phone').required = true;
                document.getElementById('supplier_address').required = true;
                
                // Remove requirement from buyer fields (since they're hidden)
                document.getElementById('buyer_address').required = false;
                document.getElementById('buyer_phone').required = false;
                
            } else { // No role selected yet
                // Hide both field sets until user makes a selection
                buyerFields.style.display = 'none';
                supplierFields.style.display = 'none';
            }
        }

        // Attach event listeners to radio buttons to trigger field toggling
        // 'change' event fires when radio button selection changes
        buyerRadio.addEventListener('change', toggleProfileFields);
        supplierRadio.addEventListener('change', toggleProfileFields);

        // Form submission validation
        // Add event listener to entire form to validate before submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            // Final check to ensure passwords match before allowing form submission
            if (password.value !== confirmPassword.value) {
                e.preventDefault(); // Stop form submission
                alert('Passwords do not match!'); // Show error alert to user
                return false; // Prevent form submission
            }
            // If passwords match, form will submit normally to register-action.php
        });
</script>

</body>
</html>
