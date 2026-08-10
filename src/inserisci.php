<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$errore = '';
$contoIdPrefill = (int) ($_GET['conto_id'] ?? $_POST['conto_id'] ?? 0);

$conti = getUserContiTree($pdo, $userId);

// Categorie già usate in passato, per suggerirle nel campo "Nome" (autocomplete)
$stmt = $pdo->prepare(
    'SELECT DISTINCT r.nome FROM record r JOIN conti c ON r.conto_id = c.id WHERE c.user_id = ? ORDER BY r.nome'
);
$stmt->execute([$userId]);
$categorieEsistenti = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contoId = (int) ($_POST['conto_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $soldiRaw = str_replace(',', '.', trim($_POST['soldi'] ?? ''));
    $tipo = $_POST['tipo'] ?? '';
    $data = $_POST['data'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (!contoBelongsToUser($pdo, $contoId, $userId)) {
        $errore = 'Conto non valido.';
    } elseif ($nome === '') {
        $errore = 'Inserisci il nome/categoria.';
    } elseif (!is_numeric($soldiRaw)) {
        $errore = 'Inserisci un importo valido (usa "-" davanti se è un costo).';
    } elseif (!in_array($tipo, ['Contanti', 'Bancomat'], true)) {
        $errore = 'Seleziona il metodo di pagamento.';
    } elseif ($data === '') {
        $errore = 'Inserisci una data.';
    } else {
        // Non serve toccare la tabella "conti": il totale è sempre calcolato
        // al volo con una SUM(), quindi è sempre aggiornato automaticamente.
        $stmt = $pdo->prepare(
            'INSERT INTO record (conto_id, nome, soldi, tipo, data, note) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$contoId, $nome, (float) $soldiRaw, $tipo, $data, $note !== '' ? $note : null]);
        header('Location: conto.php?id=' . $contoId . '&aggiunto=1');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="max-w-lg mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Nuovo record</h1>

    <?php if ($errore): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($conti)): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-sm">
            Devi prima <a href="crea_conto.php" class="underline font-medium">creare un conto</a>.
        </div>
    <?php else: ?>
    <form method="post" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Conto</label>
            <select name="conto_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <?php foreach ($conti as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $contoIdPrefill == $c['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', $c['depth']) . htmlspecialchars($c['nome']) . ($c['anno'] ? ' (' . $c['anno'] . ')' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome / Categoria</label>
            <input type="text" name="nome" list="categorie" required placeholder="es. Cibo, Benzina, Stipendio..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <datalist id="categorie">
                <?php foreach ($categorieEsistenti as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
            </datalist>
            <p class="text-xs text-slate-400 mt-1">
                Se il nome esiste già (es. "Cibo"), l'importo si somma automaticamente nella vista aggregata.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Importo (€)</label>
            <input type="text" inputmode="decimal" name="soldi" required placeholder="-25.50 se è un costo, 100 se un'entrata"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1">Metti il segno "-" davanti se è una spesa.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Metodo di pagamento</label>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo" value="Contanti" required> Contanti
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo" value="Bancomat"> Bancomat
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
            <input type="date" name="data" value="<?= date('Y-m-d') ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Note <span class="text-slate-400 font-normal">(opzionale)</span>
            </label>
            <input type="text" name="note" placeholder="es. non ricordo da quale transazione"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <button type="submit" class="w-full bg-slate-800 text-white py-2.5 rounded-lg font-medium hover:bg-slate-700 transition">
            Salva record
        </button>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
