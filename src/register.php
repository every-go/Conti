<?php

require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $conferma = $_POST['conferma'] ?? '';

    if (strlen($username) < 3) {
        $errore = 'Lo username deve avere almeno 3 caratteri.';
    } elseif (strlen($password) < 6) {
        $errore = 'La password deve avere almeno 6 caratteri.';
    } elseif ($password !== $conferma) {
        $errore = 'Le due password non coincidono.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM utenti WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $errore = 'Username già in uso.';
        } else {
            // Password SEMPRE hashata, mai salvata in chiaro
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO utenti (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $userId = (int) $pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            header('Location: dashboard.php');
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
<title>Registrati — Conti Famiglia</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <h1 class="text-2xl font-bold text-slate-800 text-center mb-6">💶 Conti Famiglia</h1>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-700 mb-4">Crea account</h2>

            <?php if ($errore): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
                    <?= htmlspecialchars($errore) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input type="text" name="username" required minlength="3" autofocus
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Conferma password</label>
                    <input type="password" name="conferma" required minlength="6"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                </div>
                <button type="submit" class="w-full bg-slate-800 text-white py-2.5 rounded-lg font-medium hover:bg-slate-700 transition">
                    Registrati
                </button>
            </form>

            <p class="text-sm text-slate-500 text-center mt-4">
                Hai già un account?
                <a href="login.php" class="text-slate-800 font-medium hover:underline">Accedi</a>
            </p>
        </div>
    </div>
</body>
</html>
