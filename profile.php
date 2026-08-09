<?php
require_once __DIR__ . '/common/header.php';
require_user_login();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $upi_id = sanitize($_POST['upi_id'] ?? '');

        if (empty($username) || empty($email)) {
            $error = 'Username and Email are required.';
        } else {
            // Check for existing duplicates
            $chk = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $chk->execute([$username, $email, $user['id']]);
            if ($chk->fetch()) {
                $error = 'Username or Email is already taken.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, upi_id = ? WHERE id = ?");
                $stmt->execute([$username, $email, $upi_id, $user['id']]);
                $msg = 'Profile & UPI ID updated successfully!';
                $user = get_logged_user($pdo);
            }
        }
    } elseif ($action === 'change_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';

        if (!password_verify($old_pass, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 4) {
            $error = 'New password must be at least 4 characters long.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user['id']]);
            $msg = 'Password changed successfully!';
        }
    }
}
?>

<div class="space-y-4">
    <div class="bg-gray-900 border border-gray-800 rounded-3xl p-5 text-center shadow-lg">
        <div class="w-16 h-16 bg-gradient-to-tr from-yellow-500 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-950 text-2xl font-black shadow-lg">
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
        <h3 class="text-base font-bold text-white"><?= htmlspecialchars($user['username']) ?></h3>
        <p class="text-xs text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
        <span class="inline-block mt-2 bg-yellow-500/10 text-yellow-500 text-[10px] font-bold px-3 py-1 rounded-full border border-yellow-500/20">
            User ID: #5R<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?>
        </span>
    </div>

    <?php if ($msg): ?>
        <div class="bg-green-500/10 border border-green-500/50 text-green-400 p-3 rounded-xl text-xs text-center flex items-center justify-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-xl text-xs text-center flex items-center justify-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 shadow-md">
        <h4 class="text-xs font-bold text-gray-300 uppercase tracking-wider mb-3">Edit Profile & Payment Info</h4>
        <form method="POST" action="profile.php" class="space-y-3">
            <input type="hidden" name="action" value="update_profile">
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500">
            </div>
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500">
            </div>
            <div>
                <label class="block text-[11px] text-yellow-500 mb-1">Your Saved Withdrawal UPI ID</label>
                <input type="text" name="upi_id" value="<?= htmlspecialchars($user['upi_id'] ?? '') ?>" placeholder="e.g. yourname@upi" class="w-full bg-gray-950 border border-yellow-500/30 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500 font-mono">
            </div>
            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-bold py-2.5 rounded-xl text-xs transition">
                Update Info
            </button>
        </form>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 shadow-md">
        <h4 class="text-xs font-bold text-gray-300 uppercase tracking-wider mb-3">Change Password</h4>
        <form method="POST" action="profile.php" class="space-y-3">
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">Current Password</label>
                <input type="password" name="old_password" required placeholder="••••••••" class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500">
            </div>
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">New Password</label>
                <input type="password" name="new_password" required placeholder="••••••••" class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500">
            </div>
            <button type="submit" class="w-full bg-gray-800 hover:bg-gray-700 text-yellow-500 font-bold py-2.5 rounded-xl text-xs border border-gray-700 transition">
                Change Password
            </button>
        </form>
    </div>

    <a href="login.php?action=logout" class="block w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 text-center font-bold py-3 rounded-2xl border border-red-500/30 text-xs transition">
        <i class="fa-solid fa-right-from-bracket mr-1"></i> Logout Account
    </a>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
