<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$username = $_SESSION['username'];
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM utenti WHERE username = ?');
    $stmt->execute([$username]);
    $utente = $stmt->fetch();

    if (!$utente || !password_verify($password, $utente['password'])) {
        $errore = 'Password non corretta.';
    } else {
        deleteAccount($pdo, $username);

        $_SESSION = [];
        session_destroy();

        header('Location: login.php?eliminato=1');
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
<link rel="stylesheet" href="/style.css">
</head>
<body class="bg-slate-100 min-h-screen">
<nav class="bg-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 flex items-center justify-between h-14">
        <a href="dashboard.php" class="font-semibold"><span aria-hidden="true">💶</span> Conti Famiglia</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="inserisci.php" class="hover:text-slate-300">Nuovo record</a>
            <a href="storico.php" class="hover:text-slate-300">Storico</a>
            <a href="logout.php" id="link-logout" class="hover:text-slate-300">Esci</a>
        </div>
    </div>
</nav>
<div class="max-w-lg mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Il tuo account</h1>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="text-sm text-slate-500 mb-1">Username</div>
        <div class="text-lg font-medium text-slate-800"><?= htmlspecialchars($_SESSION['username']) ?></div>
    </div>

    <?php if ($errore): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
        <p class="text-sm text-slate-500 mb-4">
            Eliminando l'account perdi in modo permanente i tuoi dati.
            Questa azione non può essere annullata.
        </p>
        <button type="button" onclick="apriModaleEliminaAccount()"
                class="bg-white border border-red-300 text-red-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-50 transition">
            Elimina account
        </button>
    </div>
</div>

<!-- Modale di conferma eliminazione account -->
<div id="modaleEliminaAccount" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center px-4 z-50">
    <div class="bg-white rounded-xl shadow-lg max-w-sm w-full p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-2">Eliminare l'account?</h3>
        <p class="text-sm text-slate-500 mb-4">
            Questa azione non può essere annullata.
        </p>
        <form method="post" id="formElimina" class="space-y-4">
            <label for="password">Inserisci la password per confermare</label>
            <input id="password" type="password" name="password" required autofocus placeholder="Password"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
            <div class="flex gap-3">
                <button type="button" onclick="chiudiModaleEliminaAccount()"
                        class="flex-1 border border-slate-300 text-slate-700 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition">
                    Annulla
                </button>
                <button type="submit"
                        class="flex-1 bg-red-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                    Elimina definitivamente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function apriModaleEliminaAccount() {
    document.getElementById('modaleEliminaAccount').classList.remove('hidden');
}
function chiudiModaleEliminaAccount() {
    document.getElementById('modaleEliminaAccount').classList.add('hidden');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') chiudiModaleEliminaAccount();
});
document.getElementById('modaleEliminaAccount').addEventListener('click', (e) => {
    if (e.target.id === 'modaleEliminaAccount') chiudiModaleEliminaAccount();
});
</script>

<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>

