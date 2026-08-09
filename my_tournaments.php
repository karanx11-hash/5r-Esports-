<?php
require_once __DIR__ . '/common/header.php';
require_user_login();

$tab = $_GET['tab'] ?? 'upcoming';

if ($tab === 'completed') {
    $stmt = $pdo->prepare("
        SELECT t.* FROM tournaments t
        INNER JOIN participants p ON t.id = p.tournament_id
        WHERE p.user_id = ? AND t.status = 'Completed'
        ORDER BY t.match_time DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT t.* FROM tournaments t
        INNER JOIN participants p ON t.id = p.tournament_id
        WHERE p.user_id = ? AND t.status != 'Completed'
        ORDER BY t.match_time ASC
    ");
}

$stmt->execute([$user['id']]);
$tournaments = $stmt->fetchAll();
?>

<div class="space-y-4">
    <div class="flex bg-gray-900 border border-gray-800 p-1 rounded-2xl">
        <a href="my_tournaments.php?tab=upcoming" class="w-1/2 py-2.5 text-center text-xs font-bold rounded-xl transition <?= $tab !== 'completed' ? 'bg-yellow-500 text-gray-950 shadow' : 'text-gray-400' ?>">
            Upcoming / Live
        </a>
        <a href="my_tournaments.php?tab=completed" class="w-1/2 py-2.5 text-center text-xs font-bold rounded-xl transition <?= $tab === 'completed' ? 'bg-yellow-500 text-gray-950 shadow' : 'text-gray-400' ?>">
            Completed
        </a>
    </div>

    <?php if (empty($tournaments)): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 text-center text-gray-500">
            <i class="fa-solid fa-trophy text-3xl mb-2"></i>
            <p class="text-xs">No tournaments found in this section.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($tournaments as $t): ?>
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 shadow-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold uppercase bg-yellow-500/10 text-yellow-500 px-2 py-0.5 rounded border border-yellow-500/20">
                            <?= htmlspecialchars($t['game_name']) ?>
                        </span>
                        <span class="text-xs text-gray-400">
                            <?= date('M d, h:i A', strtotime($t['match_time'])) ?>
                        </span>
                    </div>

                    <h4 class="font-bold text-base text-white"><?= htmlspecialchars($t['title']) ?></h4>

                    <div class="grid grid-cols-2 gap-2 my-3 text-xs bg-gray-950/50 p-2.5 rounded-xl border border-gray-800">
                        <div>
                            <span class="text-gray-500 block text-[10px]">PRIZE POOL</span>
                            <span class="font-bold text-green-400"><?= format_currency($t['prize_pool']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-[10px]">ENTRY FEE</span>
                            <span class="font-bold text-yellow-500"><?= format_currency($t['entry_fee']) ?></span>
                        </div>
                    </div>

                    <?php if ($t['status'] === 'Completed'): ?>
                        <div class="pt-2 border-t border-gray-800 flex justify-between items-center text-xs">
                            <span class="text-gray-400">Result:</span>
                            <?php if ($t['winner_id'] == $user['id']): ?>
                                <span class="bg-green-500/20 text-green-400 font-extrabold px-3 py-1 rounded-full border border-green-500/40">
                                    <i class="fa-solid fa-crown mr-1"></i> WINNER
                                </span>
                            <?php else: ?>
                                <span class="bg-gray-800 text-gray-400 font-bold px-3 py-1 rounded-full border border-gray-700">
                                    Participated
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-950 p-3 rounded-xl border border-yellow-500/20 mt-3">
                            <div class="text-[11px] font-bold text-yellow-500 mb-2 flex items-center justify-between">
                                <span><i class="fa-solid fa-key mr-1"></i> Room Credentials</span>
                                <span class="text-[9px] bg-yellow-500/20 px-2 py-0.5 rounded text-yellow-400 uppercase">
                                    <?= $t['status'] ?>
                                </span>
                            </div>
                            <?php if (!empty($t['room_id'])): ?>
                                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                    <div class="bg-gray-900 p-2 rounded border border-gray-800">
                                        <span class="text-gray-500 text-[10px] block">ROOM ID</span>
                                        <span class="text-white font-bold"><?= htmlspecialchars($t['room_id']) ?></span>
                                    </div>
                                    <div class="bg-gray-900 p-2 rounded border border-gray-800">
                                        <span class="text-gray-500 text-[10px] block">PASS</span>
                                        <span class="text-white font-bold"><?= htmlspecialchars($t['room_password']) ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-[11px] text-gray-500 italic text-center py-1">
                                    Room ID & Password will be displayed 15 mins before match time.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
