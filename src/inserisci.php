<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$errore = '';

$conti = getUserContiTree($pdo, $userId);

// Categorie già usate in passato, per suggerirle nel campo "Nome" (autocomplete)
$stmt = $pdo->prepare(
    'SELECT DISTINCT r.nome FROM record r JOIN conti c ON r.conto_id = c.id WHERE c.user_id = ? ORDER BY r.nome'
);
$stmt->execute([$userId]);
$categorieEsistenti = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Valori da mostrare nel form: di default vuoti/precompilati con la data odierna,
// ma se arriviamo da un errore di validazione vengono sovrascritti con quanto
// l'utente aveva già digitato, così non deve riscrivere tutto da capo.
$vals = [
    'conto_id' => (int) ($_GET['conto_id'] ?? 0),
    'nome'     => '',
    'soldi'    => '',
    'tipo'     => '',
    'data'     => date('Y-m-d'),
    'note'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['conto_id'] = (int) ($_POST['conto_id'] ?? 0);
    $vals['nome']     = trim($_POST['nome'] ?? '');
    $vals['soldi']    = trim($_POST['soldi'] ?? ''); // teniamo il testo originale per ripopolare il campo
    $vals['tipo']     = $_POST['tipo'] ?? '';
    $vals['data']     = $_POST['data'] ?? '';
    $vals['note']     = trim($_POST['note'] ?? '');

    $soldiNormalizzato = str_replace(',', '.', $vals['soldi']);

    if (!contoBelongsToUser($pdo, $vals['conto_id'], $userId)) {
        $errore = 'Conto non valido.';
    } elseif ($vals['nome'] === '') {
        $errore = 'Inserisci il nome/categoria.';
    } elseif (!preg_match('/^-?\d+(\.\d{1,2})?$/', $soldiNormalizzato)) {
        $errore = 'L\'importo può avere al massimo due cifre decimali (es. 8, 8,20, -12,50): niente arrotondamenti automatici, correggi il valore.';
    } elseif (!in_array($vals['tipo'], ['Contanti', 'Bancomat'], true)) {
        $errore = 'Seleziona il metodo di pagamento.';
    } elseif ($vals['data'] === '') {
        $errore = 'Inserisci una data.';
    } elseif (($annoConto = getContoAnno($pdo, $vals['conto_id'])) !== null && (int) substr($vals['data'], 0, 4) !== $annoConto) {
        $errore = "La data deve rientrare nell'anno {$annoConto}, l'anno del conto selezionato.";
    } else {
        // Non serve toccare la tabella "conti": il totale è sempre calcolato
        // al volo con una SUM(), quindi è sempre aggiornato automaticamente.
        $stmt = $pdo->prepare(
            'INSERT INTO record (conto_id, nome, soldi, tipo, data, note) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vals['conto_id'],
            $vals['nome'],
            (float) $soldiNormalizzato,
            $vals['tipo'],
            $vals['data'],
            $vals['note'] !== '' ? $vals['note'] : null,
        ]);
        header('Location: conto.php?id=' . $vals['conto_id'] . '&aggiunto=1');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
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
            <select name="conto_id" id="conto_id" required onchange="aggiornaLimitiData()" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <?php foreach ($conti as $c): ?>
                    <option value="<?= $c['id'] ?>" data-anno="<?= $c['anno'] ?? '' ?>" <?= $vals['conto_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', $c['depth']) . htmlspecialchars($c['nome']) . ($c['anno'] ? ' (' . $c['anno'] . ')' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome / Categoria</label>
            <input type="text" name="nome" list="categorie" required placeholder="es. Cibo, Benzina, Stipendio..."
                   value="<?= htmlspecialchars($vals['nome']) ?>"
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
            <input type="text" inputmode="decimal" name="soldi" required
                   placeholder="-25,50 se è un costo, 100 se un'entrata"
                   value="<?= htmlspecialchars($vals['soldi']) ?>"
                   pattern="-?[0-9]+([.,][0-9]{1,2})?"
                   title="Massimo due cifre decimali, es. 8, 8,20 o -12,50"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1">
                Metti il segno "-" davanti se è una spesa. Massimo 2 cifre decimali, senza arrotondamenti.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Metodo di pagamento</label>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo" value="Contanti" <?= $vals['tipo'] === 'Contanti' ? 'checked' : '' ?> required>
                    Contanti
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="tipo" value="Bancomat" <?= $vals['tipo'] === 'Bancomat' ? 'checked' : '' ?>>
                    Bancomat
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
            <input type="date" name="data" id="data" value="<?= htmlspecialchars($vals['data']) ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1" id="nota-anno-data"></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Note <span class="text-slate-400 font-normal">(opzionale)</span>
            </label>
            <input type="text" name="note" placeholder="es. non ricordo da quale transazione"
                   value="<?= htmlspecialchars($vals['note']) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>

        <button type="submit" class="w-full bg-slate-800 text-white py-2.5 rounded-lg font-medium hover:bg-slate-700 transition">
            Salva record
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

            // Se la data già inserita esce dall'anno del conto, la riporto dentro i limiti
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
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
