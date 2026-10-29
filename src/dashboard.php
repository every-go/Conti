<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$username = $_SESSION['username'];

// Conti principali (radice) dell'utente, cioè quelli con parent_conto_id NULL
$stmt = $pdo->prepare(
    'SELECT id, nome, anno FROM conti WHERE username = ? AND parent_conto_id IS NULL ORDER BY anno ASC'
);
$stmt->execute([$username]);
$radici = $stmt->fetchAll();

$annoCorrente = (int) date('Y');
$hasAnnoCorrente = false;
foreach ($radici as $r) {
    if ((int) $r['anno'] === $annoCorrente) {
        $hasAnnoCorrente = true;
        break;
    }
}

// Totale ricorsivo (record diretti + tutti i sotto-conti) per ogni conto principale
$conti = [];
foreach ($radici as $r) {
    $conti[] = [
        'id'      => $r['id'],
        'nome'    => $r['nome'],
        'anno'    => $r['anno'],
        'totale'  => getContoTotal($pdo, (int) $r['id']),
    ];
}

$totaleGlobale = 0.0;
foreach ($conti as $c) {
    $totaleGlobale += $c['totale'];
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Conti Famiglia</title>
<link rel="stylesheet" href="/style.css">
</head>
<body class="bg-slate-100 min-h-screen">
<nav class="bg-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 flex items-center justify-between h-14">
        <p class="font-semibold"><span aria-hidden="true">💶</span> Conti Famiglia</p>
        <div class="flex items-center gap-4 text-sm">
            <p lang="en" aria-current="page" class="text-slate-300">Home</p>
            <a href="inserisci.php" class="hover:text-slate-300">Nuovo record</a>
            <a href="storico.php" class="hover:text-slate-300">Storico</a>
            <a href="delete.php" class="hover:text-slate-300">Elimina account</a>
            <a href="logout.php" id="link-logout" class="hover:text-slate-300">Esci</a>
        </div>
    </div>
</nav>
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-slate-800">I tuoi conti</h1>
        <a href="crea_conto.php"
           class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700 transition">
            + Nuovo conto
        </a>
    </div>

    <?php if (!$hasAnnoCorrente): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 mb-6 flex flex-wrap items-center justify-between gap-2">
            <span>Non hai ancora un conto per il <?= $annoCorrente ?>.</span>
            <a href="crea_conto.php?anno=<?= $annoCorrente ?>" class="underline font-medium">Crealo ora</a>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
        <div class="text-sm text-slate-500 mb-1">Totale globale (tutti gli anni)</div>
        <div class="text-3xl font-bold <?= moneyColorClass($totaleGlobale) ?>"><?= formatMoney($totaleGlobale) ?></div>
    </div>

    <?php if (empty($conti)): ?>
        <p class="text-slate-500">Nessun conto ancora. Creane uno per iniziare a tracciare le tue spese.</p>
    <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($conti as $c): ?>
                <a href="conto.php?id=<?= $c['id'] ?>"
                   class="block bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md hover:border-slate-300 transition">
                    <div class="text-sm text-slate-500 mb-1"><?= htmlspecialchars((string) $c['anno']) ?></div>
                    <div class="text-lg font-semibold text-slate-800 mb-2"><?= htmlspecialchars($c['nome']) ?></div>
                    <div class="text-xl font-bold <?= moneyColorClass($c['totale']) ?>"><?= formatMoney($c['totale']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>

