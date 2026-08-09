<?php
require_once __DIR__ . '/common/config.php';

// Ensure user is logged in
require_user_login();

// Fetch Logged in User Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-950 text-white min-h-screen p-4 flex flex-col justify-between">

<div class="max-w-md mx-auto w-full space-y-4">
    
    <div class="flex items-center justify-between bg-gray-900 border border-gray-800 p-4 rounded-2xl">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-500/20 text-yellow-400 font-bold rounded-xl flex items-center justify-center border border-yellow-500/30">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <h2 class="text-xs text-gray-400">Welcome Back,</h2>
                <h1 class="text-sm font-bold text-white"><?= htmlspecialchars($user['username']) ?></h1>
            </div>
        </div>

        <a href="logout.php" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 font-bold px-3 py-2 rounded-xl text-xs transition flex items-center gap-1">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>

    <div class="bg-gradient-to-r from-yellow-500/10 via-gray-900 to-gray-900 border border-yellow-500/20 p-5 rounded-3xl text-center">
        <span class="text-xs text-gray-400 uppercase tracking-wider block">Wallet Balance</span>
        <span class="text-3xl font-black text-yellow-400 mt-1 block">₹<?= number_format($user['wallet_balance'], 2) ?></span>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-yellow-500/10 text-yellow-400 rounded-2xl flex items-center justify-center text-lg font-bold border border-yellow-500/20 shrink-0">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-white">Invite Friends</h3>
                <p class="text-xs text-gray-400">Doston ko app join karayein</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <button onclick="shareOnWhatsApp()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-2xl text-xs transition flex items-center justify-center gap-2">
                <i class="fa-brands fa-whatsapp text-sm"></i> WhatsApp
            </button>

            <button onclick="shareAppLink()" class="bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-bold py-3 px-4 rounded-2xl text-xs transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-share-nodes text-sm"></i> Share Link
            </button>
        </div>
    </div>

</div>

<script>
const appShareUrl = window.location.origin + "/login.php";
const inviteMsg = "Hey! Is awesome app ko join karein aur account banayein:\n" + appShareUrl;

function shareOnWhatsApp() {
    const waUrl = "https://api.whatsapp.com/send?text=" + encodeURIComponent(inviteMsg);
    window.open(waUrl, '_blank');
}

function shareAppLink() {
    if (navigator.share) {
        navigator.share({
            title: 'Join App',
            text: 'Is app par  ff ke turnament khele minimum entry fee se jaldi jaye or account banayein:',
            url: appShareUrl,
        }).catch((err) => console.log('Canceled', err));
    } else {
        navigator.clipboard.writeText(appShareUrl).then(() => {
            alert("App Link Copied!");
        });
    }
}
</script>

</body>
</html>

<div class="space-y-4">
    <div class="bg-gradient-to-r from-yellow-500 to-amber-600 rounded-2xl p-4 text-gray-950 shadow-lg flex justify-between items-center">
        <div>
            <h2 class="font-extrabold text-lg leading-tight">Esports Arena</h2>
            <p class="text-xs font-medium opacity-90">Join matches, win cash & rewards!</p>
        </div>
        <i class="fa-solid fa-gamepad text-4xl opacity-80"></i>
    </div>

    <?php if ($msg): ?>
        <div class="<?= $msg_type === 'success' ? 'bg-green-500/10 border-green-500/50 text-green-400' : 'bg-red-500/10 border-red-500/50 text-red-400' ?> border p-3 rounded-xl text-xs flex items-center justify-between">
            <span><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>

    <h3 class="font-bold text-sm text-gray-300 uppercase tracking-wider flex items-center gap-2">
        <i class="fa-solid fa-fire text-yellow-500"></i> Upcoming Matches
    </h3>

    <?php if (empty($tournaments)): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 text-center text-gray-500">
            <i class="fa-solid fa-ghost text-3xl mb-2"></i>
            <p class="text-xs">No active matches found right now.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($tournaments as $t): 
                $is_joined = in_array($t['id'], $joined_ids);
            ?>
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 shadow-xl hover:border-gray-700 transition">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="bg-yellow-500/10 text-yellow-500 border border-yellow-500/30 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase">
                                <?= htmlspecialchars($t['game_name']) ?>
                            </span>
                            <h4 class="font-bold text-base text-white mt-1"><?= htmlspecialchars($t['title']) ?></h4>
                        </div>
                        <span class="text-[11px] text-gray-400 bg-gray-800 px-2 py-1 rounded-lg border border-gray-700">
                            <i class="fa-regular fa-clock mr-1 text-yellow-500"></i>
                            <?= date('M d, h:i A', strtotime($t['match_time'])) ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 bg-gray-950/60 p-3 rounded-xl text-center border border-gray-800/80 mb-3">
                        <div>
                            <span class="text-[10px] text-gray-400 block">PRIZE POOL</span>
                            <span class="text-sm font-black text-green-400"><?= format_currency($t['prize_pool']) ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 block">ENTRY FEE</span>
                            <span class="text-sm font-black text-yellow-500"><?= format_currency($t['entry_fee']) ?></span>
                        </div>
                    </div>

                    <?php if ($is_joined): ?>
                        <button disabled class="w-full bg-gray-800 text-green-400 border border-green-500/30 font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 cursor-not-allowed">
                            <i class="fa-solid fa-circle-check"></i> Already Joined
                        </button>
                    <?php else: ?>
                        <form method="POST" action="index.php">
                            <input type="hidden" name="tournament_id" value="<?= $t['id'] ?>">
                            <button type="submit" name="join_tournament" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-950 font-extrabold py-2.5 rounded-xl text-xs shadow-lg transition uppercase tracking-wider">
                                Join Now
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
