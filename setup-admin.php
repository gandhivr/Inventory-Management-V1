<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
        }
        .btn-setup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
        }
        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="text-center mb-4">
            <h2>🔧 Setup Admin User</h2>
            <p class="text-muted">Create the main administrator account</p>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'config/database.php';
            
            try {
                // Check if admin already exists
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' OR email = 'admin@inventory.com'");
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    echo '<div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Admin user already exists! Try logging in with:<br>
                            <strong>Username:</strong> admin<br>
                            <strong>Password:</strong> admin123
                          </div>';
                } else {
                    // Create admin user
                    $password = 'admin123';
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, username, email, password, role, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $result = $stmt->execute([
                        'Main Administrator',
                        'admin',
                        'admin@inventory.com',
                        $hashed_password,
                        'admin',
                        'active'
                    ]);
                    
                    if ($result) {
                        echo '<div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Admin Created Successfully!</h5>
                                <hr>
                                <strong>Username:</strong> admin<br>
                                <strong>Password:</strong> admin123<br>
                                <strong>Role:</strong> Administrator<br>
                                <hr>
                                <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
                              </div>';
                        
                        // Test password verification
                        if (password_verify($password, $hashed_password)) {
                            echo '<div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Password verification test: <strong>PASSED</strong>
                                  </div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i>
                                Failed to create admin user!
                              </div>';
                    }
                }
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Database Error: ' . htmlspecialchars($e->getMessage()) . '
                      </div>';
            }
        }
        ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Admin Details</label>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <strong>Username:</strong><br>
                                <code>admin</code>
                            </div>
                            <div class="col-6">
                                <strong>Password:</strong><br>
                                <code>admin123</code>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <strong>Email:</strong><br>
                                <small>admin@inventory.com</small>
                            </div>
                            <div class="col-6">
                                <strong>Role:</strong><br>
                                <small>Administrator</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-setup">
                    <i class="fas fa-user-plus"></i> Create Admin User
                </button>
            </div>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                After creating the admin, you can delete this file for security.
            </small>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>