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
    } elseif (($annoConto = getContoAnno($pdo, $contoId)) !== null && (int) substr($data, 0, 4) !== $annoConto) {
        $errore = "La data deve rientrare nell'anno {$annoConto}, l'anno del conto selezionato.";
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

require_once __DIR__ . '/includes/header.php';
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
            <select name="conto_id" id="conto_id" required onchange="aggiornaLimitiData()" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <?php foreach ($conti as $c): ?>
                    <option value="<?= $c['id'] ?>" data-anno="<?= $c['anno'] ?? '' ?>" <?= $record['conto_id'] == $c['id'] ? 'selected' : '' ?>>
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
            <input type="date" name="data" id="data" value="<?= htmlspecialchars($record['data']) ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1" id="nota-anno-data"></p>
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

    <script>
    function aggiornaLimitiData() {
        const select = document.getElementById('conto_id');
        const campoData = document.getElementById('data');
        const nota = document.getElementById('nota-anno-data');
        const anno = select.options[select.selectedIndex]?.dataset.anno;

        if (anno) {
            campoData.min = anno + '-01-01';
            campoData.max = anno + '-12-31';
            nota.textContent = 'Il conto selezionato è del ' + anno + ': la data deve rientrare in quell\'anno.';

            const annoAttuale = campoData.value ? campoData.value.slice(0, 4) : '';
            if (annoAttuale && annoAttuale !== anno) {
                campoData.value = anno + '-01-01';
            }
        } else {
            campoData.removeAttribute('min');
            campoData.removeAttribute('max');
            nota.textContent = '';
        }
    }
    aggiornaLimitiData();
    </script>

    <form method="post" id="formElimina" class="mt-3">
        <input type="hidden" name="id" value="<?= $recordId ?>">
        <input type="hidden" name="elimina" value="1">
        <button type="button" onclick="apriModaleElimina()"
                class="w-full bg-white border border-red-300 text-red-600 py-2.5 rounded-lg font-medium hover:bg-red-50 transition">
            Elimina record
        </button>
    </form>

    <!-- Modale di conferma eliminazione -->
    <div id="modaleElimina" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center px-4 z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Eliminare il record?</h3>
            <p class="text-sm text-slate-500 mb-6">Questa azione non può essere annullata.</p>
            <div class="flex gap-3">
                <button type="button" onclick="chiudiModaleElimina()"
                        class="flex-1 border border-slate-300 text-slate-700 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition">
                    Annulla
                </button>
                <button type="button" onclick="document.getElementById('formElimina').submit()"
                        class="flex-1 bg-red-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                    Elimina
                </button>
            </div>
        </div>
    </div>

    <script>
    function apriModaleElimina() {
        document.getElementById('modaleElimina').classList.remove('hidden');
    }
    function chiudiModaleElimina() {
        document.getElementById('modaleElimina').classList.add('hidden');
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') chiudiModaleElimina();
    });
    document.getElementById('modaleElimina').addEventListener('click', (e) => {
        if (e.target.id === 'modaleElimina') chiudiModaleElimina();
    });
    </script>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
