<?php
// 1. Output buffering aur Session handling
ob_start();

require_once __DIR__ . '/common/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar user pehle se logged-in hai to index.php par bhej dein
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Input Safe Sanitize Function
if (!function_exists('clean_input')) {
    function clean_input($data) {
        return htmlspecialchars(trim($data));
    }
}

$error = '';
$success = '';

// -------------------------------------------------------------
// 2. SIGNUP / REGISTER LOGIC (Bina Kisi Referral Ke)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    
    $username = clean_input($_POST['username'] ?? '');
    $email    = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Kripya saari details (Username, Email, Password) bharein!";
    } else {
        try {
            // Check Karein ki Username ya Email pehle se toh nahi hai
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check_stmt->execute([$email, $username]);

            if ($check_stmt->fetch()) {
                $error = "Yeh Username ya Email pehle se registered hai!";
            } else {
                // Password Secure Hash Karein
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Simple Insert Query (Wallet Balance = 0)
                $sql = "INSERT INTO users (username, email, password, wallet_balance) VALUES (?, ?, ?, 0.00)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $new_user_id = $pdo->lastInsertId();
                    
                    // Direct Auto Login
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $username;

                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Registration nahi ho saka. Kripya dobara try karein.";
                }
            }
        } catch (PDOException $e) {
            // Screen par exact error dikhane ke liye
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// 3. LOGIN LOGIC
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email_or_user = clean_input($_POST['username'] ?? '');
    $password      = $_POST['password'] ?? '';

    if (empty($email_or_user) || empty($password)) {
        $error = "Email/Username aur Password dono daalna zaroori hai!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email_or_user, $email_or_user]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                header("Location: index.php");
                exit();
            } else {
                $error = "Galat Username/Email ya Password!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-950 text-white flex items-center justify-center min-h-screen p-4">

<div class="w-full max-w-md bg-gray-900 border border-gray-800 rounded-3xl p-6 shadow-2xl">
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-400 p-3 rounded-xl text-xs text-center font-semibold mb-4">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logout'): ?>
        <div class="bg-green-500/10 border border-green-500/40 text-green-400 p-3 rounded-xl text-xs text-center font-semibold mb-4">
            <i class="fa-solid fa-circle-check mr-1"></i> Aap Logout Ho Chuke Hain!
        </div>
    <?php endif; ?>

    <div class="flex bg-gray-950 p-1 rounded-2xl mb-6 border border-gray-800">
        <button id="loginTabBtn" onclick="toggleForm('login')" class="w-1/2 py-2.5 text-xs font-bold rounded-xl transition text-gray-950 bg-yellow-500">Login</button>
        <button id="signupTabBtn" onclick="toggleForm('signup')" class="w-1/2 py-2.5 text-xs font-bold rounded-xl transition text-gray-400">Sign Up</button>
    </div>

    <form id="loginForm" action="login.php" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="login">
        
        <div>
            <label class="block text-xs text-gray-400 mb-1">Username ya Email</label>
            <input type="text" name="username" required class="w-full bg-gray-950 border border-gray-800 text-white px-4 py-3 rounded-xl text-xs focus:border-yellow-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">Password</label>
            <input type="password" name="password" required class="w-full bg-gray-950 border border-gray-800 text-white px-4 py-3 rounded-xl text-xs focus:border-yellow-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-bold py-3 rounded-xl text-xs transition">
            Login
        </button>
    </form>

    <form id="signupForm" action="login.php" method="POST" class="space-y-4 hidden">
        <input type="hidden" name="action" value="register">

        <div>
            <label class="block text-xs text-gray-400 mb-1">Username</label>
            <input type="text" name="username" required placeholder="User_123" class="w-full bg-gray-950 border border-gray-800 text-white px-4 py-3 rounded-xl text-xs focus:border-yellow-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">Email</label>
            <input type="email" name="email" required placeholder="example@email.com" class="w-full bg-gray-950 border border-gray-800 text-white px-4 py-3 rounded-xl text-xs focus:border-yellow-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">Password</label>
            <input type="password" name="password" required placeholder="••••••••" class="w-full bg-gray-950 border border-gray-800 text-white px-4 py-3 rounded-xl text-xs focus:border-yellow-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-bold py-3 rounded-xl text-xs transition">
            Sign Up (Account Banayein)
        </button>
    </form>
</div>

<script>
function toggleForm(type) {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const loginBtn = document.getElementById('loginTabBtn');
    const signupBtn = document.getElementById('signupTabBtn');

    if (type === 'signup') {
        loginForm.classList.add('hidden');
        signupForm.classList.remove('hidden');
        
        signupBtn.className = "w-1/2 py-2.5 text-xs font-bold rounded-xl transition text-gray-950 bg-yellow-500";
        loginBtn.className = "w-1/2 py-2.5 text-xs font-bold rounded-xl transition text-gray-400";
    } else {
        signupForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
        
        loginBtn.className = "w-1/2 py-2.5 text-xs font-bold rounded-xl transition text-gray-950 bg-yellow-500";
        signupBtn.className = "w-1/2 py-2.5 text-xs font-bold rounded-xl transition text-gray-400";
    }
}
</script>

</body>
</html>
