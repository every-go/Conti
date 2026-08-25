<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$contoId = (int) ($_GET['id'] ?? 0);

if (!$contoId || !contoBelongsToUser($pdo, $contoId, $userId)) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM conti WHERE id = ?');
$stmt->execute([$contoId]);
$conto = $stmt->fetch();

$breadcrumb = getContoBreadcrumb($pdo, $contoId);
$anno = getContoAnno($pdo, $contoId);
$totale = getContoTotal($pdo, $contoId);

// Voci aggregate per categoria, solo sui Record inseriti direttamente in questo conto
$stmt = $pdo->prepare(
    'SELECT nome, SUM(soldi) AS totale FROM record WHERE conto_id = ? GROUP BY nome ORDER BY totale DESC'
);
$stmt->execute([$contoId]);
$categorie = $stmt->fetchAll();

// Sotto-conti diretti (un livello sotto), ognuno con il proprio totale ricorsivo
$stmt = $pdo->prepare('SELECT id, nome FROM conti WHERE parent_conto_id = ? ORDER BY nome ASC');
$stmt->execute([$contoId]);
$figliRaw = $stmt->fetchAll();

$figli = [];
foreach ($figliRaw as $f) {
    $figli[] = [
        'id'     => $f['id'],
        'nome'   => $f['nome'],
        'totale' => getContoTotal($pdo, (int) $f['id']),
    ];
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-5xl mx-auto px-4 py-8">

    <nav class="text-sm text-slate-500 mb-4 flex flex-wrap gap-1 items-center">
        <a href="dashboard.php" class="hover:underline">Conti</a>
        <?php foreach ($breadcrumb as $i => $b): ?>
            <span>/</span>
            <?php if ($i === count($breadcrumb) - 1): ?>
                <span class="text-slate-700 font-medium"><?= htmlspecialchars($b['nome']) ?></span>
            <?php else: ?>
                <a href="conto.php?id=<?= $b['id'] ?>" class="hover:underline"><?= htmlspecialchars($b['nome']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($conto['nome']) ?></h1>
            <p class="text-slate-500 text-sm">Anno <?= htmlspecialchars((string) ($anno ?? '—')) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="inserisci.php?conto_id=<?= $contoId ?>"
               class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700 transition">
                + Nuovo record
            </a>
            <a href="crea_conto.php?parent_id=<?= $contoId ?>"
               class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition">
                + Sotto-conto
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
        <div class="text-sm text-slate-500 mb-1">Totale (record diretti + sotto-conti)</div>
        <div class="text-3xl font-bold <?= moneyColorClass($totale) ?>"><?= formatMoney($totale) ?></div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-sm border-separate border-spacing-y-2">
                <thead>
                    <tr>
                        <th>Voce</th>
                        <th>Somma</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($figli as $f): ?>
                    <tr class="border-b border-slate-100 last:border-0 bg-slate-50">
                        <td class="px-5 py-2">
                            <a href="conto.php?id=<?= $f['id'] ?>" class="text-slate-700 font-medium hover:underline">
                                <span aria-hidden="true">📁 </span><?= htmlspecialchars($f['nome']) ?>
                            </a>
                        </td>
                        <td class="px-5 py-2 text-right font-medium <?= moneyColorClass($f['totale']) ?>">
                            <?= formatMoney($f['totale']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ($categorie as $cat): ?>
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-5 py-2 text-slate-600"><?= htmlspecialchars($cat['nome']) ?></td>
                        <td class="px-5 py-2 text-right font-medium <?= moneyColorClass((float) $cat['totale']) ?>">
                            <?= formatMoney((float) $cat['totale']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categorie) && empty($figli)): ?>
                    <tr><td class="px-5 py-4 text-slate-400" colspan="2">Nessun movimento ancora.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="font-semibold text-slate-700 mb-3">Storico di questo conto</div>
            <p class="text-sm text-slate-500 mb-4">
                Vedi, filtra e modifica tutti i movimenti registrati direttamente in questo conto.
            </p>
            <a href="storico.php?conto_id=<?= $contoId ?>"
               class="inline-block bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700 transition">
                Apri storico <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
