<?php
// Start PHP session to check if user is already logged in
// This enables access to session variables set during login or registration
session_start();

// Check if user is already authenticated by looking for user_id in session
// If user is already logged in, redirect them away from login page
if (isset($_SESSION['user_id'])) {
    // Redirect authenticated users to the main dashboard/index page
    // This prevents logged-in users from seeing the login form unnecessarily
    header('Location: index.php');
    // Stop executing remaining PHP code after redirect
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Professional Inventory Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Professional Dark Theme Login Page */
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

        .login-container {
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

        .login-container::before {
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
            justify-content: center;
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
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-bottom: 32px;
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

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .checkbox-group {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            border: 2px solid rgba(100, 116, 139, 0.5) !important;
            border-radius: 4px !important;
            cursor: pointer;
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
            cursor: pointer;
        }

        .forgot-link {
            color: #00d4ff !important;
            text-decoration: none;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #0ea5e9 !important;
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
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
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 12px !important;
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3) !important;
        }

        .login-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 35px rgba(0, 212, 255, 0.5) !important;
            background: linear-gradient(135deg, #0ea5e9, #0284c7) !important;
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-text {
            font-weight: 700 !important;
        }

        .btn-icon {
            font-size: 1.25rem !important;
        }

        /* Demo Section */
        .demo-section {
            background: rgba(15, 23, 42, 0.6) !important;
            border-radius: 16px !important;
            padding: 24px !important;
            margin-bottom: 32px;
            border: 1px solid rgba(100, 116, 139, 0.3) !important;
            backdrop-filter: blur(10px);
        }

        .demo-header {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
        }

        .demo-icon {
            font-size: 1.5rem !important;
        }

        .demo-header h3 {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #f1f5f9 !important;
        }

        .demo-account {
            background: rgba(15, 23, 42, 0.8) !important;
            border-radius: 12px !important;
            padding: 20px !important;
            margin-bottom: 16px !important;
            border: 1px solid rgba(100, 116, 139, 0.2) !important;
        }

        .demo-role {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 16px !important;
        }

        .role-icon {
            width: 40px !important;
            height: 40px !important;
            border-radius: 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.25rem !important;
        }

        .role-icon.admin {
            background: linear-gradient(135deg, #ff00d4, #a855f7) !important;
        }

        .role-info h4 {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            color: #f1f5f9 !important;
            margin-bottom: 4px !important;
        }

        .role-info p {
            font-size: 0.85rem !important;
            color: #cbd5e1 !important;
        }

        .demo-credentials {
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
        }

        .credential-item {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .credential-label {
            font-size: 0.85rem !important;
            color: #64748b !important;
            min-width: 80px !important;
        }

        .credential-value {
            background: rgba(0, 212, 255, 0.1) !important;
            color: #00d4ff !important;
            padding: 4px 8px !important;
            border-radius: 6px !important;
            font-size: 0.85rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease;
            border: 1px solid rgba(0, 212, 255, 0.3) !important;
        }

        .credential-value:hover {
            background: rgba(0, 212, 255, 0.2) !important;
            transform: scale(1.05);
        }

        .demo-note {
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
            padding: 16px !important;
            background: rgba(0, 255, 136, 0.1) !important;
            border-radius: 10px !important;
            border: 1px solid rgba(0, 255, 136, 0.3) !important;
        }

        .note-icon {
            font-size: 1.25rem !important;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .demo-note p {
            font-size: 0.9rem !important;
            color: #cbd5e1 !important;
            line-height: 1.4;
        }

        .demo-note strong {
            color: #00ff88 !important;
        }

        /* Form Footer */
        .form-footer {
            text-align: center !important;
            padding-top: 24px !important;
            border-top: 1px solid rgba(100, 116, 139, 0.3) !important;
        }

        .form-footer p {
            color: #cbd5e1 !important;
            margin-bottom: 16px !important;
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
            .login-container {
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

            .login-container {
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

            .features-preview {
                flex-direction: column;
            }

            .feature-item {
                min-width: auto;
                flex-direction: row;
                text-align: left;
            }

            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
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

            .login-btn {
                padding: 16px 20px !important;
                font-size: 1rem !important;
            }

            .demo-section {
                padding: 20px 16px !important;
            }
        }
    </style>
    <meta name="description" content="Sign in to your professional inventory management account. Access your dashboard and manage your business efficiently.">
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="left-side">
            <div class="brand-content">
                <div class="brand-logo">📦</div>
                <h1 class="brand-title">Welcome Back</h1>
                <p class="brand-subtitle">Sign in to your account and continue managing your business with our powerful inventory management platform.</p>
                
                <div class="features-preview">
                    <div class="feature-item">
                        <div class="feature-icon">⚡</div>
                        <div class="feature-text">
                            <h4>Lightning Fast</h4>
                            <p>Optimized performance for busy professionals</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📊</div>
                        <div class="feature-text">
                            <h4>Real-time Analytics</h4>
                            <p>Live insights and business intelligence</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🌐</div>
                        <div class="feature-text">
                            <h4>Cloud-Based</h4>
                            <p>Access your data from anywhere, anytime</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Right Side - Login Form -->
        <div class="right-side">
            <div class="form-container">
                <div class="form-header">
                    <h2>Sign In to Your Account</h2>
                    <p>Enter your credentials to access your dashboard</p>
                </div>


                <?php 
                // Check for error messages passed via URL parameter
                // This displays login failure messages from login-action.php
                // Example URL: login.php?error=Invalid credentials
                if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon">⚠️</div>
                        <div class="alert-content">
                            <strong>Login Failed</strong>
                            <!-- Display error message while preventing XSS attacks -->
                            <!-- htmlspecialchars() converts special characters to HTML entities -->
                            <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>


                <?php 
                // Check for success messages passed via URL parameter
                // This might display messages like "Registration successful, please login"
                // Or "Password reset successful, please login with new password"
                if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon">✅</div>
                        <div class="alert-content">
                            <strong>Success!</strong>
                            <!-- Display success message while preventing XSS attacks -->
                            <p><?php echo htmlspecialchars($_GET['success']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- Login form that submits to login-action.php for processing -->
                <form action="login-action.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <div class="input-icon">👤</div>
                            <!-- Input accepts both username and email for user convenience -->
                            <input type="text" id="username" name="username" class="form-input" 
                                   placeholder="Enter your username or email" required>
                        </div>
                    </div>


                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <div class="input-icon">🔒</div>
                            <!-- Password field with required validation -->
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>


                    <div class="form-options">
                        <div class="checkbox-group">
                            <!-- Remember me checkbox (can be used for extended sessions) -->
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember" class="checkbox-label">Remember me</label>
                        </div>
                        <!-- Forgot password link (currently placeholder) -->
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>


                    <!-- Submit button with JavaScript-controlled loading state -->
                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                        <div class="btn-icon">🚀</div>
                    </button>
                </form>


                <!-- Demo Accounts Section for testing purposes -->
                <div class="demo-section">
                    <div class="demo-header">
                        <div class="demo-icon">🎯</div>
                        <h3>Demo Accounts</h3>
                    </div>
                    <div class="demo-accounts">
                        <!-- Admin demo account with predefined credentials -->
                        <div class="demo-account">
                            <div class="demo-role">
                                <div class="role-icon admin">👑</div>
                                <div class="role-info">
                                    <h4>Admin Account</h4>
                                    <p>Full system access and management</p>
                                </div>
                            </div>
                            <div class="demo-credentials">
                                <div class="credential-item">
                                    <span class="credential-label">Username:</span>
                                    <!-- Clickable credential that auto-fills form via JavaScript -->
                                    <code class="credential-value" onclick="fillCredentials('admin', 'admin123')">admin</code>
                                </div>
                                <div class="credential-item">
                                    <span class="credential-label">Password:</span>
                                    <!-- Same onclick function fills both username and password -->
                                    <code class="credential-value" onclick="fillCredentials('admin', 'admin123')">admin123</code>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Information note about creating other account types -->
                        <div class="demo-note">
                            <div class="note-icon">💡</div>
                            <p>Create <strong>Supplier</strong> and <strong>Buyer</strong> accounts via registration to test all features</p>
                        </div>
                    </div>
                </div>


                <div class="form-footer">
                    <!-- Link to registration page for new users -->
                    <p>Don't have an account? <a href="register.php" class="link">Create one here</a></p>
                    <div class="back-home">
                        <!-- Navigation back to main site -->
                        <a href="index.php" class="link-secondary">← Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Function to automatically fill login credentials for demo accounts
        // Called when user clicks on demo credential values
        function fillCredentials(username, password) {
            // Fill the form fields with provided credentials
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Provide visual feedback to user by temporarily changing background color
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            // Set light green background to indicate fields were filled
            usernameField.style.background = '#ecfdf5';
            passwordField.style.background = '#ecfdf5';
            
            // Remove the visual feedback after 1 second
            setTimeout(() => {
                usernameField.style.background = '';
                passwordField.style.background = '';
            }, 1000);
        }

        // Client-side form validation before submission
        // Prevents submission of empty forms and provides immediate feedback
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Get current values and remove whitespace with trim()
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Check if either field is empty after trimming whitespace
            if (!username || !password) {
                e.preventDefault(); // Stop form submission
                alert('Please fill in all fields'); // Show user-friendly error
                return false; // Prevent form submission
            }
        });

        // Add loading state to login button during form submission
        // Provides visual feedback that login process is in progress
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            // Change button text and icon to show loading state
            btn.innerHTML = '<span class="btn-text">Signing In...</span><div class="btn-icon">⏳</div>';
            btn.disabled = true; // Disable button to prevent multiple submissions
        });
    </script>
</body>
</html>
