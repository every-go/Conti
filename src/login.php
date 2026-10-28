<?php

require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT username, password FROM utenti WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    }

    $errore = 'Username o password non corretti.';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accedi — Conti Famiglia</title>
<link rel="stylesheet" href="/style.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <?php if (isset($_GET['eliminato'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg px-4 py-3 mb-4 text-sm">
                Account eliminato correttamente.
            </div>
        <?php endif; ?>
        <h1 class="text-2xl font-bold text-slate-800 text-center mb-6"><span aria-hidden="true">💶</span> Conti Famiglia</h1>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-700 mb-4">Accedi</h2>

            <?php if ($errore): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
                    <?= htmlspecialchars($errore) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input id="username" type="text" name="username" required autofocus
                        autocomplete="username"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-slate-300 pl-3 pr-11 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                        <button type="button" id="password-toggle" data-toggle-password="password"
                            aria-controls="password"
                            tabindex="-1"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-600 hover:text-slate-800 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-slate-400">
                            <span data-icona-occhio="aperto" aria-hidden="true" hidden><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg></span>
                            <span data-icona-occhio="chiuso" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg></span>
                            <span class="sr-only" data-toggle-testo>Mostra password</span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-slate-800 text-white py-2.5 rounded-lg font-medium hover:bg-slate-700 transition">
                    Accedi
                </button>
            </form>

            <p class="text-sm text-slate-500 text-center mt-4">
                Non hai un account?
                <a href="register.php" class="text-slate-800 font-medium hover:underline">Registrati</a>
            </p>
        </div>
    </div>

    <script>
        (function () {
            // Mostra / nascondi password
            document.querySelectorAll('[data-toggle-password]').forEach(function (pulsante) {
                const campo = document.getElementById(pulsante.dataset.togglePassword);
                const testo = pulsante.querySelector('[data-toggle-testo]');
                const occhioAperto = pulsante.querySelector('[data-icona-occhio="aperto"]');
                const occhioChiuso = pulsante.querySelector('[data-icona-occhio="chiuso"]');

                // Con il mouse/touch il pulsante non deve rubare il focus all'input,
                // così si può continuare a scrivere subito dopo aver cliccato l'occhio
                pulsante.addEventListener('mousedown', function (evento) {
                    evento.preventDefault();
                });

                pulsante.addEventListener('click', function (evento) {
                    const mostra = campo.type === 'password';
                    const inizioSelezione = campo.selectionStart;
                    const fineSelezione = campo.selectionEnd;

                    campo.type = mostra ? 'text' : 'password';

                    // evento.detail è 0 quando il click arriva dalla tastiera (Invio/Spazio):
                    // in quel caso il focus resta sul pulsante, per non disorientare chi naviga con Tab.
                    // Con il mouse/touch, invece, riporto il focus nell'input con il cursore dov'era.
                    if (evento.detail !== 0) {
                        const ripristinaFocus = function () {
                            campo.focus({ preventScroll: true });
                            campo.setSelectionRange(inizioSelezione, fineSelezione);
                        };

                        ripristinaFocus();

                        // Alcuni browser (Safari/iOS soprattutto) tolgono il focus in modo asincrono
                        // dopo il cambio di type: riprovo appena il browser ha finito
                        setTimeout(ripristinaFocus, 0);
                    }

                    // L'icona mostra lo stato attuale: occhio aperto = password visibile,
                    // occhio barrato = password nascosta
                    occhioAperto.hidden = !mostra;
                    occhioChiuso.hidden = mostra;
                    testo.textContent = mostra ? 'Nascondi password' : 'Mostra password';
                });
            });
        })();
    </script>
<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>
