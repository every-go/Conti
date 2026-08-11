<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$errore = '';

$parentId = null;
if (isset($_GET['parent_id'])) {
    $parentId = (int) $_GET['parent_id'];
} elseif (isset($_POST['parent_id']) && $_POST['parent_id'] !== '') {
    $parentId = (int) $_POST['parent_id'];
}
if ($parentId && !contoBelongsToUser($pdo, $parentId, $userId)) {
    $parentId = null;
}

$annoPrefill = $_GET['anno'] ?? (string) date('Y');
$alberoConti = getUserContiTree($pdo, $userId);

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
            if (!contoBelongsToUser($pdo, $pId, $userId)) {
                $errore = 'Conto padre non valido.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO conti (nome, anno, user_id, parent_conto_id) VALUES (?, NULL, ?, ?)'
                );
                $stmt->execute([$nome, $userId, $pId]);
                header('Location: conto.php?id=' . $pdo->lastInsertId());
                exit;
            }
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO conti (nome, anno, user_id, parent_conto_id) VALUES (?, ?, ?, NULL)'
            );
            $stmt->execute([$nome, (int) $annoPost, $userId]);
            header('Location: conto.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-lg mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Nuovo conto</h1>

    <?php if ($errore): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
            <input type="text" name="nome" required placeholder="es. 2026, Sardegna, Vacanza..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo di conto</label>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo_conto" value="principale" <?= $parentId ? '' : 'checked' ?> onchange="toggleContoFields()">
                    Conto principale (anno)
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo_conto" value="sotto" <?= $parentId ? 'checked' : '' ?> onchange="toggleContoFields()">
                    Sotto-conto
                </label>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Un sotto-conto (es. "Sardegna") eredita l'anno dal suo conto padre e può a sua volta avere altri sotto-conti.
            </p>
        </div>

        <div id="campo-anno">
            <label class="block text-sm font-medium text-slate-700 mb-1">Anno</label>
            <input type="number" name="anno" value="<?= htmlspecialchars($annoPrefill) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <div id="campo-padre" class="<?= $parentId ? '' : 'hidden' ?>">
            <label class="block text-sm font-medium text-slate-700 mb-1">Conto padre</label>
            <select name="parent_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
