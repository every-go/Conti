<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$username = $_SESSION['username'];
$errore = '';

$conti = getUserContiTree($pdo, $username);

// Categorie già usate in passato, per suggerirle nel campo "Nome" (autocomplete)
$stmt = $pdo->prepare(
    'SELECT DISTINCT r.nome FROM record r JOIN conti c ON r.conto_id = c.id WHERE c.username = ? ORDER BY r.nome'
);
$stmt->execute([$username]);
$categorieEsistenti = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Se arriviamo da un conto specifico (es. dal bottone "+ Nuovo record" dentro conto.php),
// preseleziona quello. Altrimenti (es. dal link "Nuovo record" nella nav principale),
// preseleziona il conto principale più recente, così l'inserimento rapido funziona
// subito senza dover scorrere il menu fino in fondo.
$contoPrincipalePiuRecente = null;
foreach ($conti as $c) {
    $eRadice = (int) $c['depth'] === 0;
    $ePiuRecente = $contoPrincipalePiuRecente === null || (int) $c['anno'] > (int) $contoPrincipalePiuRecente['anno'];
    if ($eRadice && $ePiuRecente) {
        $contoPrincipalePiuRecente = $c;
    }
}

$contoIdDefault = isset($_GET['conto_id'])
    ? (int) $_GET['conto_id']
    : ($contoPrincipalePiuRecente['id'] ?? 0);

// Valori da mostrare nel form: di default vuoti/precompilati con la data odierna,
// ma se arriviamo da un errore di validazione vengono sovrascritti con quanto
// l'utente aveva già digitato, così non deve riscrivere tutto da capo.
$vals = [
    'conto_id'   => $contoIdDefault,
    'nome'       => '',
    'soldi'      => '',
    'tipo'       => '',
    'tipo-soldi' => '',
    'data'       => date('Y-m-d'),
    'note'       => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['conto_id']   = (int) ($_POST['conto_id'] ?? 0);
    $vals['nome']       = trim($_POST['nome'] ?? '');
    $vals['soldi']      = trim($_POST['soldi'] ?? ''); // teniamo il testo originale per ripopolare il campo
    $vals['tipo-soldi'] = $_POST['tipo-soldi'] ?? '';
    $vals['tipo']       = $_POST['tipo'] ?? '';
    $vals['data']       = $_POST['data'] ?? '';
    $vals['note']       = trim($_POST['note'] ?? '');
    $ritorno = $_GET['ritorno'] ?? $_POST['ritorno'] ?? '';

    $soldiNormalizzato = str_replace(',', '.', $vals['soldi']);

    if (!contoBelongsToUser($pdo, $vals['conto_id'], $username)) {
        $errore = 'Conto non valido.';
    } elseif ($vals['nome'] === '') {
        $errore = 'Inserisci il nome/categoria.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $soldiNormalizzato)) {
    $errore = 'L\'importo può avere al massimo due cifre decimali (es. 8, 8,20): niente arrotondamenti automatici, correggi il valore.';
    } elseif(!in_array($vals['tipo-soldi'], ['Entrata', 'Uscita'], true)) {
        $errore = "Inserisci il tipo di transazione.";
    } elseif (!in_array($vals['tipo'], ['Contanti', 'Bancomat'], true)) {
        $errore = 'Seleziona il metodo.';
    } elseif ($vals['data'] === '') {
        $errore = 'Inserisci una data.';
    } elseif (($annoConto = getContoAnno($pdo, $vals['conto_id'])) !== null && (int) substr($vals['data'], 0, 4) !== $annoConto) {
        $errore = "La data deve rientrare nell'anno {$annoConto}, l'anno del conto selezionato.";
    } else {
        $importo = $vals['tipo-soldi'] === 'Uscita' ? -abs((float) $soldiNormalizzato) : abs((float) $soldiNormalizzato);

        $stmt = $pdo->prepare(
            'INSERT INTO record (conto_id, nome, soldi, tipo, data, note) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vals['conto_id'],
            $vals['nome'],
            $importo,
            $vals['tipo'],
            $vals['data'],
            $vals['note'] !== '' ? $vals['note'] : null,
        ]);
        header('Location: conto.php?id=' . $vals['conto_id'] . '&aggiunto=1');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Conti Famiglia</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
<nav class="bg-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 flex items-center justify-between h-14">
        <a href="dashboard.php" class="font-semibold"><span aria-hidden="true">💶</span> Conti Famiglia</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="storico.php" class="hover:text-slate-300">Storico</a>
            <a href="delete.php" class="hover:text-slate-300">Elimina account</a>
            <a href="logout.php" id="link-logout" class="hover:text-slate-300">Esci</a>
        </div>
    </div>
</nav>
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
            <label for="conto_id" class="block text-sm font-medium text-slate-700 mb-1">Conto</label>
            <select name="conto_id" id="conto_id" required onchange="aggiornaLimitiData()" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <?php foreach ($conti as $c): ?>
                    <option value="<?= $c['id'] ?>" data-anno="<?= $c['anno'] ?? '' ?>" <?= $vals['conto_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', $c['depth']) . htmlspecialchars($c['nome']) . ($c['anno'] ? ' (' . $c['anno'] . ')' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="nome" class="block text-sm font-medium text-slate-700 mb-1">Nome / Categoria</label>
            <input type="text" name="nome" id="nome" list="categorie" required placeholder="es. Cibo, Benzina, Stipendio..."
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

        <fieldset class="mb-4">
            <legend class="block text-sm font-medium text-slate-700 mb-2">Tipo di transazione</legend>
            <div class="flex gap-4 text-sm">
                <label for="tipo-entrata" class="flex items-center gap-2">
                    <input type="radio" name="tipo-soldi" id="tipo-entrata" value="Entrata" <?= $vals['tipo-soldi'] === 'Entrata' ? 'checked' : '' ?> required>
                    Entrata
                </label>
                <label for="tipo-uscita" class="flex items-center gap-2">
                    <input type="radio" name="tipo-soldi" id="tipo-uscita" value="Uscita" <?= $vals['tipo-soldi'] === 'Uscita' ? 'checked' : '' ?>>
                    Uscita
                </label>
            </div>
        </fieldset>

        <div>
            <label for="soldi" class="block text-sm font-medium text-slate-700 mb-1">Importo <span aria-hidden="true">(€)</span><span class="sr-only">in euro</span></label>

            <input type="text" inputmode="decimal" name="soldi" id="soldi" required autocomplete="off"
                placeholder="es. 25,50"
                value="<?= htmlspecialchars($vals['soldi']) ?>"
                pattern="[0-9]+([.,][0-9]{1,2})?"
                title="Massimo due cifre decimali, es. 8 oppure 8,20"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1">
                Massimo 2 cifre decimali, senza arrotondamenti.
            </p>
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-slate-700 mb-2">Metodo</legend>
            <div class="flex gap-4 text-sm">
                <label for="tipo-contanti" class="flex items-center gap-2">
                    <input type="radio" name="tipo" id="tipo-contanti" value="Contanti" <?= $vals['tipo'] === 'Contanti' ? 'checked' : '' ?> required>
                    Contanti
                </label>
                <label for="tipo-bancomat" class="flex items-center gap-2">
                    <input type="radio" name="tipo" id="tipo-bancomat" value="Bancomat" <?= $vals['tipo'] === 'Bancomat' ? 'checked' : '' ?>>
                    Bancomat
                </label>
            </div>
        </fieldset>

        <div>
            <label for="data" class="block text-sm font-medium text-slate-700 mb-1">Data</label>
            <input type="date" name="data" id="data" value="<?= htmlspecialchars($vals['data']) ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <p class="text-xs text-slate-400 mt-1" id="nota-anno-data"></p>
        </div>

        <div>
            <label for="note" class="block text-sm font-medium text-slate-700 mb-1">
                Note <span class="text-slate-400 font-normal">(opzionale)</span>
            </label>
            <input id="note" type="text" name="note" placeholder="es. non ricordo da quale transazione" autocomplete="off"
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

<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>

