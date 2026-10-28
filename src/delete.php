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
        <a href="dashboard.php" class="font-semibold" tabindex="-1" aria-hidden="true"><span aria-hidden="true">💶</span> Conti Famiglia</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="dashboard.php" lang="en" class="hover:text-slate-300">Home</a>
            <a href="inserisci.php" class="hover:text-slate-300">Nuovo record</a>
            <a href="storico.php" class="hover:text-slate-300">Storico</a>
            <p class="hover:text-slate-300">Elimina account</p>
            <a href="logout.php" id="link-logout" class="hover:text-slate-300">Esci</a>
        </div>
    </div>
</nav>
<div class="max-w-lg mx-auto px-4 py-8">

    <div class="text-sm text-slate-500 mb-4 flex flex-wrap gap-1 items-center">
        <a href="dashboard.php" class="hover:underline">Conti</a>
        <span aria-hidden="true">/</span>
        <span class="text-slate-700 font-medium">Elimina account</span>
    </div>

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
<dialog id="modaleEliminaAccount" aria-labelledby="titoloModaleElimina"
        class="m-auto p-0 bg-transparent overflow-visible w-[calc(100%-2rem)] max-w-sm backdrop:bg-slate-900/50">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 id="titoloModaleElimina" class="text-lg font-semibold text-slate-800 mb-2">Eliminare l'account?</h2>
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
</dialog>

<script>
const modaleEliminaAccount = document.getElementById('modaleEliminaAccount');

function apriModaleEliminaAccount() {
    modaleEliminaAccount.showModal();
}
function chiudiModaleEliminaAccount() {
    modaleEliminaAccount.close();
}

// Il tasto Esc è già gestito dal browser per i <dialog>.
// Il click sullo sfondo scuro (::backdrop) arriva al <dialog> stesso, non al suo contenuto.
modaleEliminaAccount.addEventListener('click', (e) => {
    if (e.target === modaleEliminaAccount) chiudiModaleEliminaAccount();
});
</script>

<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>
