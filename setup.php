<?php
// Database setup script
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'inventory_management';

echo "<h2>Inventory Management System - Setup</h2>";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<p>✓ Database '$dbname' created/verified</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the schema
    $schema = file_get_contents('database/schema.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✓ Database tables created successfully</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@example.com', $adminPassword, 'admin']);
        echo "<p>✓ Admin user created (username: admin, password: admin123)</p>";
    } else {
        echo "<p>✓ Admin user already exists</p>";
    }
    
    // Create uploads directory
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "<p>✓ Created uploads directory</p>";
    }
    
    if (!is_dir('uploads/products')) {
        mkdir('uploads/products', 0755, true);
        echo "<p>✓ Created products upload directory</p>";
    }
    
    echo "<h3 style='color: green;'>Setup completed successfully!</h3>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Login as Admin: username = 'admin', password = 'admin123'</li>";
    echo "<li>Register new Supplier and Buyer accounts</li>";
    echo "<li>Start using the system</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Please check:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL server is running</li>";
    echo "<li>Database credentials are correct in config/database.php</li>";
    echo "<li>PHP has PDO MySQL extension enabled</li>";
    echo "</ul>";
}
?>