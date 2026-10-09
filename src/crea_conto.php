<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$username = $_SESSION['username'];
$errore = '';

$parentId = null;
if (isset($_GET['parent_id'])) {
    $parentId = (int) $_GET['parent_id'];
} elseif (isset($_POST['parent_id']) && $_POST['parent_id'] !== '') {
    $parentId = (int) $_POST['parent_id'];
}
if ($parentId && !contoBelongsToUser($pdo, $parentId, $username)) {
    $parentId = null;
}

$annoPrefill = $_GET['anno'] ?? (string) date('Y');
$alberoConti = getUserContiTree($pdo, $username);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $tipoConto = $_POST['tipo_conto'] ?? 'principale';
    $parentPost = $_POST['parent_id'] ?? '';
    $annoPost = $_POST['anno'] ?? '';

    if ($nome === '') {
        $errore = 'Il nome del conto è obbligatorio.';
    } elseif ($tipoConto === 'sotto' && $parentPost === '') {
        $errore = 'Seleziona il conto padre per un sotto-conto.';
    } elseif ($tipoConto === 'principale' && !ctype_digit((string) $annoPost)) {
        $errore = 'Inserisci un anno valido.';
    } else {
        if ($tipoConto === 'sotto') {
            $pId = (int) $parentPost;
            if (!contoBelongsToUser($pdo, $pId, $username)) {
                $errore = 'Conto padre non valido.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO conti (nome, anno, username, parent_conto_id) VALUES (?, NULL, ?, ?)'
                );
                $stmt->execute([$nome, $username, $pId]);
                header('Location: conto.php?id=' . $pdo->lastInsertId());
                exit;
            }
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO conti (nome, anno, username, parent_conto_id) VALUES (?, ?, ?, NULL)'
            );
            $stmt->execute([$nome, (int) $annoPost, $username]);
            header('Location: conto.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
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
        <a href="dashboard.php" class="font-semibold"><span aria-hidden="true">💶</span> Conti Famiglia</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="inserisci.php" class="hover:text-slate-300">Nuovo record</a>
            <a href="storico.php" class="hover:text-slate-300">Storico</a>
            <a href="delete.php" class="hover:text-slate-300">Elimina account</a>
            <a href="logout.php" id="link-logout" class="hover:text-slate-300">Esci</a>
        </div>
    </div>
</nav>
<div class="max-w-lg mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Nuovo conto</h1>

    <?php if ($errore): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <div>
            <label for="nome" class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
            <input id="nome" type="text" name="nome" required placeholder="es. 2026, Sardegna, Vacanza..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-slate-700 mb-2">Tipo di conto</legend>
            <div class="flex gap-4 text-sm">
                <label for="conto_principale" class="flex items-center gap-2">
                    <input id="conto_principale" type="radio" name="tipo_conto" value="principale" <?= $parentId ? '' : 'checked' ?> onchange="toggleContoFields()">
                    Conto principale (anno)
                </label>
                <label for="sotto_conto" class="flex items-center gap-2">
                    <input id="sotto_conto" type="radio" name="tipo_conto" value="sotto" <?= $parentId ? 'checked' : '' ?> onchange="toggleContoFields()">
                    Sotto-conto
                    Conto principale (anno)
                </label>
                <p class="text-xs text-slate-400 mt-1">
                Un sotto-conto (es. "Sardegna") eredita l'anno dal suo conto padre e può a sua volta avere altri sotto-conti.
            </p>
            </div>
        </fieldset>

        <div id="campo-anno">
            <label for="anno" class="block text-sm font-medium text-slate-700 mb-1">Anno</label>
            <input id="anno" type="number" name="anno" value="<?= htmlspecialchars($annoPrefill) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <div id="campo-padre" class="<?= $parentId ? '' : 'hidden' ?>">
            <label for="conto_padre" class="block text-sm font-medium text-slate-700 mb-1">Conto padre</label>
            <select id="conto_padre" name="parent_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <option value="">— seleziona —</option>
                <?php foreach ($alberoConti as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $parentId == $c['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', $c['depth']) . htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="w-full bg-slate-800 text-white py-2.5 rounded-lg font-medium hover:bg-slate-700 transition">
            Crea conto
        </button>
    </form>
</div>

<script>
function toggleContoFields() {
    const isSotto = document.querySelector('input[name="tipo_conto"]:checked').value === 'sotto';
    document.getElementById('campo-anno').classList.toggle('hidden', isSotto);
    document.getElementById('campo-padre').classList.toggle('hidden', !isSotto);
}
toggleContoFields();
</script>


<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>

