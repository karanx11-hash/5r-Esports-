<?php
require_once __DIR__ . '/common/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Create Users Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            wallet_balance DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create Admin Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create Tournaments Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS tournaments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            game_name VARCHAR(50) NOT NULL,
            entry_fee DECIMAL(10,2) NOT NULL,
            prize_pool DECIMAL(10,2) NOT NULL,
            commission_percent DECIMAL(5,2) DEFAULT 20.00,
            match_time DATETIME NOT NULL,
            room_id VARCHAR(50) DEFAULT '',
            room_password VARCHAR(50) DEFAULT '',
            status ENUM('Upcoming', 'Live', 'Completed') DEFAULT 'Upcoming',
            winner_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create Participants Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tournament_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participation (user_id, tournament_id)
        )");

        // Create Transactions Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            type ENUM('credit', 'debit') NOT NULL,
            description VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Insert Default Admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $default_pass = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES ('admin', ?)");
            $stmt->execute([$default_pass]);
        }

        $message = "Database & Tables installed successfully! Default Admin Created: <b>admin</b> / <b>admin123</b>";
    } catch (PDOException $e) {
        $error = "Installation Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Install 5r Esports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-gray-100 flex items-center justify-center min-h-screen p-4 select-none">
    <div class="bg-gray-800 p-6 rounded-2xl shadow-2xl border border-gray-700 w-full max-w-md text-center">
        <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-900 font-black text-2xl shadow-lg">
            5R
        </div>
        <h1 class="text-2xl font-bold mb-2 text-white">5r Esports Installer</h1>
        <p class="text-gray-400 text-sm mb-6">Click below to setup database tables and initial configuration.</p>

        <?php if ($message): ?>
            <div class="bg-green-500/10 border border-green-500 text-green-400 p-4 rounded-xl text-sm mb-6">
                <?= $message ?>
            </div>
            <a href="login.php" class="block w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-3 rounded-xl transition">
                Go to User Login
            </a>
            <a href="admin/login.php" class="block w-full bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 rounded-xl mt-3 transition">
                Go to Admin Login
            </a>
        <?php elseif ($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-400 p-4 rounded-xl text-sm mb-6">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <form method="POST" action="install.php">
                <button type="submit" name="install" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-3 rounded-xl shadow-lg transition">
                    <i class="fa-solid fa-download mr-2"></i> Run Database Installer
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
