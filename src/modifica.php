<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$errore = '';
$recordId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$recordId || !recordBelongsToUser($pdo, $recordId, $userId)) {
    header('Location: storico.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM record WHERE id = ?');
$stmt->execute([$recordId]);
$record = $stmt->fetch();

$conti = getUserContiTree($pdo, $userId);

$stmt = $pdo->prepare(
    'SELECT DISTINCT r.nome FROM record r JOIN conti c ON r.conto_id = c.id WHERE c.user_id = ? ORDER BY r.nome'
);
$stmt->execute([$userId]);
$categorieEsistenti = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['elimina'])) {
        $stmt = $pdo->prepare('DELETE FROM record WHERE id = ?');
        $stmt->execute([$recordId]);
        header('Location: storico.php?eliminato=1');
        exit;
    }

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
        $errore = 'Inserisci un importo valido.';
    } elseif (!in_array($tipo, ['Contanti', 'Bancomat'], true)) {
        $errore = 'Seleziona il metodo di pagamento.';
    } elseif ($data === '') {
        $errore = 'Inserisci una data.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE record SET conto_id = ?, nome = ?, soldi = ?, tipo = ?, data = ?, note = ? WHERE id = ?'
        );
        $stmt->execute([$contoId, $nome, (float) $soldiRaw, $tipo, $data, $note !== '' ? $note : null, $recordId]);
        header('Location: storico.php?modificato=1');
        exit;
    }

    // Ripopola il form con i dati appena inviati, in caso di errore di validazione
    $record = [
        'id'       => $recordId,
        'conto_id' => $contoId,
        'nome'     => $nome,
        'soldi'    => $soldiRaw,
        'tipo'     => $tipo,
        'data'     => $data,
        'note'     => $note,
    ];
}

require __DIR__ . '/includes/header.php';
?>
<div class="max-w-lg mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Modifica record</h1>

    <?php if ($errore): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <input type="hidden" name="id" value="<?= $recordId ?>">

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Conto</label>
            <select name="conto_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <?php foreach ($conti as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $record['conto_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', $c['depth']) . htmlspecialchars($c['nome']) . ($c['anno'] ? ' (' . $c['anno'] . ')' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome / Categoria</label>
            <input type="text" name="nome" list="categorie" required value="<?= htmlspecialchars($record['nome']) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <datalist id="categorie">
                <?php foreach ($categorieEsistenti as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Importo (€)</label>
            <input type="text" inputmode="decimal" name="soldi" required value="<?= htmlspecialchars((string) $record['soldi']) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1">
                Utile anche per correzioni non programmate (es. rimborsi di cui non ricordi la transazione originale):
                usa una categoria tipo "Rettifica" o "Varie" e lascia una nota.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Metodo di pagamento</label>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo" value="Contanti" <?= $record['tipo'] === 'Contanti' ? 'checked' : '' ?> required>
                    Contanti
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo" value="Bancomat" <?= $record['tipo'] === 'Bancomat' ? 'checked' : '' ?>>
                    Bancomat
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
            <input type="date" name="data" value="<?= htmlspecialchars($record['data']) ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Note <span class="text-slate-400 font-normal">(opzionale)</span>
            </label>
            <input type="text" name="note" value="<?= htmlspecialchars($record['note'] ?? '') ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <button type="submit" class="w-full bg-slate-800 text-white py-2.5 rounded-lg font-medium hover:bg-slate-700 transition">
            Salva modifiche
        </button>
    </form>

    <form method="post" onsubmit="return confirm('Eliminare definitivamente questo record?');" class="mt-3">
        <input type="hidden" name="id" value="<?= $recordId ?>">
        <button type="submit" name="elimina" value="1"
                class="w-full bg-white border border-red-300 text-red-600 py-2.5 rounded-lg font-medium hover:bg-red-50 transition">
            Elimina record
        </button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
