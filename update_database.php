<?php
require_once __DIR__ . '/common/config.php';

$message = '';
$error = '';

try {
    // 1. Create deposits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS deposits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transaction_id VARCHAR(100) NOT NULL,
        status ENUM('Pending', 'Completed', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 2. Create withdrawals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        upi_id VARCHAR(255) NOT NULL,
        status ENUM('Pending', 'Completed', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 3. Create settings table for Admin UPI and QR Code
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_upi_id VARCHAR(255) DEFAULT '',
        qr_code_image VARCHAR(255) DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insert default settings row if empty
    $check_settings = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($check_settings == 0) {
        $pdo->exec("INSERT INTO settings (admin_upi_id, qr_code_image) VALUES ('', '')");
    }

    // 4. Safely add upi_id column to users table if it does not exist
    $col_check = $pdo->query("SHOW COLUMNS FROM users LIKE 'upi_id'");
    if ($col_check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN upi_id VARCHAR(255) NULL");
    }

    $message = "✅ Database schema updated successfully! You can now safely delete this file.";

} catch (PDOException $e) {
    $error = "Database Update Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Database Migration - 5r Esports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-950 text-gray-100 flex items-center justify-center min-h-screen p-4 select-none">
    <div class="bg-gray-900 p-6 rounded-3xl border border-gray-800 w-full max-w-md text-center shadow-2xl">
        <div class="w-16 h-16 bg-yellow-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-950 font-black text-2xl shadow-lg">
            5R
        </div>
        <h2 class="text-xl font-bold text-white mb-2">Database Schema Updater</h2>

        <?php if ($message): ?>
            <div class="bg-green-500/10 border border-green-500/40 text-green-400 p-4 rounded-2xl text-xs font-semibold mb-6">
                <?= $message ?>
            </div>
            <a href="index.php" class="block w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-extrabold py-3 rounded-xl transition text-sm">
                Go to User App
            </a>
            <a href="admin/setting.php" class="block w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-xl mt-2 transition text-sm border border-gray-700">
                Go to Admin Settings
            </a>
        <?php else: ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-400 p-4 rounded-2xl text-xs font-semibold mb-6">
                <?= $error ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
