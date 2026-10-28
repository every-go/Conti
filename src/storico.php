<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$username = $_SESSION['username'];
$conti = getUserContiTree($pdo, $username);

$filtroConto = $_GET['conto_id'] ?? '';
$filtroNome = trim($_GET['nome'] ?? '');
$filtroTipo = $_GET['tipo'] ?? '';
$filtroDa = $_GET['da'] ?? '';
$filtroA = $_GET['a'] ?? '';
$queryFiltri = http_build_query(array_filter([
    'conto_id' => $filtroConto,
    'nome'     => $filtroNome,
    'tipo'     => $filtroTipo,
    'da'       => $filtroDa,
    'a'        => $filtroA,
], fn ($v) => $v !== ''));

$sql = 'SELECT r.*, c.nome AS conto_nome FROM record r JOIN conti c ON r.conto_id = c.id WHERE c.username = ?';
$params = [$username];

if ($filtroConto !== '') {
    $sql .= ' AND r.conto_id = ?';
    $params[] = (int) $filtroConto;
}
if ($filtroNome !== '') {
    $sql .= ' AND r.nome LIKE ?';
    $params[] = '%' . $filtroNome . '%';
}
if ($filtroTipo !== '' && in_array($filtroTipo, ['Contanti', 'Bancomat'], true)) {
    $sql .= ' AND r.tipo = ?';
    $params[] = $filtroTipo;
}
if ($filtroDa !== '') {
    $sql .= ' AND r.data >= ?';
    $params[] = $filtroDa;
}
if ($filtroA !== '') {
    $sql .= ' AND r.data <= ?';
    $params[] = $filtroA;
}
$sql .= ' ORDER BY r.data DESC, r.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$totaleFiltrato = 0.0;
foreach ($records as $r) {
    $totaleFiltrato += (float) $r['soldi'];
}

$stmt = $pdo->prepare(
    'SELECT DISTINCT r.nome FROM record r JOIN conti c ON r.conto_id = c.id WHERE c.username = ? ORDER BY r.nome'
);
$stmt->execute([$username]);
$categorieEsistenti = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Se lo storico è filtrato su un conto specifico (e il conto è dell'utente),
// mostro il percorso completo fino a quel conto. Altrimenti mostro solo "Conti / Storico".
$breadcrumb = [];
if ($filtroConto !== '' && contoBelongsToUser($pdo, (int) $filtroConto, $username)) {
    $breadcrumb = getContoBreadcrumb($pdo, (int) $filtroConto);
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
        <a href="dashboard.php" class="font-semibold" tabindex="-1" aria-hidden="true"><span aria-hidden="true">💶</span> Conti Famiglia</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="dashboard.php" lang="en" class="hover:text-slate-300">Home</a>
            <a href="inserisci.php" class="hover:text-slate-300">Nuovo record</a>
            <p class="hover:text-slate-300">Storico</p>
            <a href="delete.php" class="hover:text-slate-300">Elimina account</a>
            <a href="logout.php" id="link-logout" class="hover:text-slate-300">Esci</a>
        </div>
    </div>
</nav>
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="text-sm text-slate-500 mb-4 flex flex-wrap gap-1 items-center">
        <a href="dashboard.php" class="hover:underline">Conti</a>
        <?php foreach ($breadcrumb as $b): ?>
            <span>/</span>
            <a href="conto.php?id=<?= $b['id'] ?>" class="hover:underline"><?= htmlspecialchars($b['nome']) ?></a>
        <?php endforeach; ?>
        <span>/</span>
        <span class="text-slate-700 font-medium">Storico</span>
    </div>

    <h1 class="text-2xl font-bold text-slate-800 mb-6">Storico movimenti</h1>

    <?php if (isset($_GET['modificato'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg px-4 py-3 mb-4 text-sm">
            Record modificato correttamente.
        </div>
    <?php elseif (isset($_GET['eliminato'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg px-4 py-3 mb-4 text-sm">
            Record eliminato.
        </div>
    <?php endif; ?>

    <form method="get" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="conto" class="block text-xs font-medium text-slate-500 mb-1">Conto</label>
            <select id="conto" name="conto_id" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Tutti</option>
                <?php foreach ($conti as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (string) $filtroConto === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', $c['depth']) . htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="nome" class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
            <input id="nome" type="text" name="nome" list="categorie" value="<?= htmlspecialchars($filtroNome) ?>" placeholder="es. Benzina"
                   class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
            <datalist id="categorie">
                <?php foreach ($categorieEsistenti as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div>
            <label for="tipo" class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
            <select id="tipo" name="tipo" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Tutti</option>
                <option value="Contanti" <?= $filtroTipo === 'Contanti' ? 'selected' : '' ?>>Contanti</option>
                <option value="Bancomat" <?= $filtroTipo === 'Bancomat' ? 'selected' : '' ?>>Bancomat</option>
            </select>
        </div>
        <div>
            <label for="data" class="block text-xs font-medium text-slate-500 mb-1">Da</label>
            <input id="data" type="date" name="da" value="<?= htmlspecialchars($filtroDa) ?>" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label for="aData" class="block text-xs font-medium text-slate-500 mb-1">A</label>
            <input id="aData" type="date" name="a" value="<?= htmlspecialchars($filtroA) ?>" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div class="sm:col-span-2 lg:col-span-5 flex gap-2">
            <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700 transition">
                Filtra
            </button>
            <a href="storico.php" class="bg-white border border-slate-300 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition">
                Reset
            </a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-slate-500 text-left">
                    <th class="px-4 py-2 font-medium">Data</th>
                    <th class="px-4 py-2 font-medium">Categoria</th>
                    <th class="px-4 py-2 font-medium">Conto</th>
                    <th class="px-4 py-2 font-medium">Tipo</th>
                    <th class="px-4 py-2 font-medium text-right">Importo</th>
                    <th class="px-4 py-2 font-medium">Note</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-2 whitespace-nowrap text-slate-600"><?= date('d/m/Y', strtotime($r['data'])) ?></td>
                    <td class="px-4 py-2 text-slate-700"><?= htmlspecialchars($r['nome']) ?></td>
                    <td class="px-4 py-2 text-slate-500"><?= htmlspecialchars($r['conto_nome']) ?></td>
                    <td class="px-4 py-2 text-slate-500"><?= htmlspecialchars($r['tipo']) ?></td>
                    <td class="px-4 py-2 text-right font-medium <?= moneyColorClass((float) $r['soldi']) ?>">
                        <?= formatMoney((float) $r['soldi']) ?>
                    </td>
                    <td class="px-4 py-2 text-slate-400 italic"><?= htmlspecialchars($r['note'] ?? '') ?></td>
                    <td class="px-4 py-2 text-right">
                        <a href="modifica.php?id=<?= $r['id'] ?>&ritorno=<?= urlencode($queryFiltri) ?>" class="text-slate-500 hover:text-slate-800 hover:underline">Modifica</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?>
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">Nessun movimento trovato.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>
