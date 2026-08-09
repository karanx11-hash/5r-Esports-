<?php
require_once __DIR__ . '/common/header.php';
require_user_login();

$msg = '';
$error = '';

// Fetch Admin Payment Settings
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch() ?: ['admin_upi_id' => '', 'qr_code_image' => ''];

// Handle Forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'request_deposit') {
        $amount = (float)($_POST['amount'] ?? 0);
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');

        if ($amount <= 0 || empty($transaction_id)) {
            $error = 'Please enter a valid amount and transaction ID.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, transaction_id, status) VALUES (?, ?, ?, 'Pending')");
            if ($stmt->execute([$user['id'], $amount, $transaction_id])) {
                $msg = 'Deposit request submitted successfully! Pending verification.';
            } else {
                $error = 'Failed to submit deposit request.';
            }
        }
    } elseif ($action === 'request_withdrawal') {
        $amount = (float)($_POST['amount'] ?? 0);
        $upi_id = trim($user['upi_id'] ?? '');

        if (empty($upi_id)) {
            $error = 'Please save your UPI ID in Profile settings before requesting a withdrawal.';
        } elseif ($amount <= 0) {
            $error = 'Please enter a valid amount to withdraw.';
        } elseif ($amount > $user['wallet_balance']) {
            $error = 'Insufficient wallet balance for this withdrawal.';
        } else {
            $pdo->beginTransaction();
            try {
                // Deduct Balance Immediately
                $new_balance = $user['wallet_balance'] - $amount;
                $upd = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $upd->execute([$new_balance, $user['id']]);

                // Create Withdrawal Record
                $ins = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, upi_id, status) VALUES (?, ?, ?, 'Pending')");
                $ins->execute([$user['id'], $amount, $upi_id]);

                $pdo->commit();
                $msg = 'Withdrawal request submitted! Amount placed on hold.';
                $user = get_logged_user($pdo);
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Withdrawal request failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Transactions History
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

// Fetch User Pending Requests
$dep_stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? AND status = 'Pending' ORDER BY created_at DESC");
$dep_stmt->execute([$user['id']]);
$pending_deposits = $dep_stmt->fetchAll();

$with_stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? AND status = 'Pending' ORDER BY created_at DESC");
$with_stmt->execute([$user['id']]);
$pending_withdrawals = $with_stmt->fetchAll();
?>

<div class="space-y-4">
    <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-gray-700 p-6 rounded-3xl text-center shadow-2xl relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-yellow-500/10 rounded-full blur-xl"></div>
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Total Available Balance</p>
        <h1 class="text-4xl font-black text-yellow-400 tracking-tight mb-6"><?= format_currency($user['wallet_balance']) ?></h1>

        <div class="grid grid-cols-2 gap-3">
            <button onclick="document.getElementById('addMoneyModal').classList.remove('hidden')" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-extrabold py-2.5 rounded-xl text-xs shadow-lg transition flex items-center justify-center gap-1">
                <i class="fa-solid fa-plus-circle"></i> Add Money
            </button>
            <button onclick="document.getElementById('withdrawModal').classList.remove('hidden')" class="w-full bg-gray-800 hover:bg-gray-700 text-white font-extrabold py-2.5 rounded-xl text-xs border border-gray-700 transition flex items-center justify-center gap-1">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Withdraw
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="bg-green-500/10 border border-green-500/40 text-green-400 p-3 rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-400 p-3 rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($pending_deposits) || !empty($pending_withdrawals)): ?>
        <div class="bg-yellow-500/10 border border-yellow-500/30 p-3 rounded-2xl space-y-2">
            <h4 class="text-xs font-bold text-yellow-400 flex items-center gap-1">
                <i class="fa-solid fa-clock"></i> Active Requests
            </h4>
            <?php foreach ($pending_deposits as $pd): ?>
                <div class="flex justify-between items-center text-[11px] text-gray-300 bg-gray-950/60 p-2 rounded-xl">
                    <span>Deposit Pending (Txn: <?= htmlspecialchars($pd['transaction_id']) ?>)</span>
                    <span class="font-bold text-yellow-400">+<?= format_currency($pd['amount']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php foreach ($pending_withdrawals as $pw): ?>
                <div class="flex justify-between items-center text-[11px] text-gray-300 bg-gray-950/60 p-2 rounded-xl">
                    <span>Withdrawal Pending (UPI: <?= htmlspecialchars($pw['upi_id']) ?>)</span>
                    <span class="font-bold text-red-400">-<?= format_currency($pw['amount']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 class="font-bold text-sm text-gray-300 uppercase tracking-wider pt-2 flex items-center gap-2">
        <i class="fa-solid fa-list-check text-yellow-500"></i> Passbook History
    </h3>

    <?php if (empty($transactions)): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 text-center text-gray-500">
            <i class="fa-solid fa-receipt text-3xl mb-2"></i>
            <p class="text-xs">No transactions recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($transactions as $tx): ?>
                <div class="bg-gray-900 border border-gray-800/80 p-3.5 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm <?= $tx['type'] === 'credit' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
                            <i class="fa-solid <?= $tx['type'] === 'credit' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white"><?= htmlspecialchars($tx['description']) ?></p>
                            <p class="text-[10px] text-gray-500"><?= date('M d, Y - h:i A', strtotime($tx['created_at'])) ?></p>
                        </div>
                    </div>
                    <span class="text-xs font-black <?= $tx['type'] === 'credit' ? 'text-green-400' : 'text-red-400' ?>">
                        <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= format_currency($tx['amount']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="addMoneyModal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-gray-900 border border-gray-800 w-full max-w-sm rounded-3xl p-5 shadow-2xl relative">
        <button onclick="document.getElementById('addMoneyModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-3 text-center">Add Funds (Manual UPI)</h3>

        <div class="bg-gray-950 p-3 rounded-2xl border border-gray-800 text-center mb-4">
            <?php if (!empty($settings['qr_code_image'])): ?>
                <img src="<?= htmlspecialchars($settings['qr_code_image']) ?>" alt="UPI QR Code" class="w-40 h-40 object-contain mx-auto bg-white p-2 rounded-xl mb-2">
            <?php else: ?>
                <div class="w-36 h-36 bg-gray-900 rounded-xl flex items-center justify-center mx-auto mb-2 text-gray-600 text-xs">
                    No QR Code Set
                </div>
            <?php endif; ?>

            <span class="text-[10px] text-gray-400 block">UPI ID</span>
            <span class="text-xs font-bold text-yellow-400 font-mono select-all"><?= htmlspecialchars($settings['admin_upi_id'] ?: 'Not Set') ?></span>
        </div>

        <form method="POST" action="wallet.php" class="space-y-3">
            <input type="hidden" name="action" value="request_deposit">
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">Amount Paid (₹)</label>
                <input type="number" step="1" name="amount" required placeholder="e.g. 100" class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500">
            </div>
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">UPI Reference / Transaction ID</label>
                <input type="text" name="transaction_id" required placeholder="12-digit UPI Txn ID" class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500 font-mono">
            </div>
            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-extrabold py-3 rounded-xl text-xs transition">
                Submit Deposit Request
            </button>
        </form>
    </div>
</div>

<div id="withdrawModal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-gray-900 border border-gray-800 w-full max-w-sm rounded-3xl p-5 shadow-2xl relative">
        <button onclick="document.getElementById('withdrawModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-3 text-center">Withdraw Funds</h3>

        <div class="bg-gray-950 p-3 rounded-2xl border border-gray-800 mb-4">
            <span class="text-[10px] text-gray-400 block">Payout UPI ID</span>
            <?php if (!empty($user['upi_id'])): ?>
                <span class="text-xs font-bold text-yellow-400 font-mono"><?= htmlspecialchars($user['upi_id']) ?></span>
            <?php else: ?>
                <p class="text-xs text-red-400 mt-1">No UPI ID saved! <a href="profile.php" class="underline font-bold">Add in Profile</a></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="wallet.php" class="space-y-3">
            <input type="hidden" name="action" value="request_withdrawal">
            <div>
                <label class="block text-[11px] text-gray-400 mb-1">Amount to Withdraw (₹)</label>
                <input type="number" step="1" name="amount" required placeholder="e.g. 200" class="w-full bg-gray-950 border border-gray-800 text-white px-3 py-2 rounded-xl text-xs focus:outline-none focus:border-yellow-500">
            </div>
            <button type="submit" <?= empty($user['upi_id']) ? 'disabled' : '' ?> class="w-full bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-800 disabled:text-gray-500 text-gray-950 font-extrabold py-3 rounded-xl text-xs transition">
                Submit Withdrawal Request
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
