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
    $conferma = $_POST['conferma'] ?? '';

    if (strlen($username) < 3) {
        $errore = 'Lo username deve avere almeno 3 caratteri.';
    } elseif (strlen($username) > 16) {
        $errore = 'Lo username non può superare 16 caratteri.';
    } elseif (preg_match('/\s/', $username)) {
        $errore = 'Lo username non può contenere spazi.';
    } elseif (strlen($password) < 6) {
        $errore = 'La password deve avere almeno 6 caratteri.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errore = 'La password deve contenere almeno una lettera maiuscola, una minuscola, un numero e un carattere speciale.';
    } elseif ($password !== $conferma) {
        $errore = 'Le due password non coincidono.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM utenti WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $errore = 'Username già in uso.';
        }
        else {
            // Password SEMPRE hashata, mai salvata in chiaro
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO utenti (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            session_regenerate_id(true);
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
<link rel="stylesheet" href="/style.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <h1 class="text-2xl font-bold text-slate-800 text-center mb-6"><span aria-hidden="true">💶</span> Conti Famiglia</h1>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-700 mb-4">Crea account</h2>

            <?php if ($errore): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
                    <?= htmlspecialchars($errore) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="form-registrazione" class="space-y-4" novalidate>
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input id="username" type="text" name="username" required minlength="3" autofocus
                        autocomplete="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <p id="username-errore" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input id="password" type="password" name="password" required minlength="6"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <p id="password-errore" class="text-xs text-red-600 mt-1 hidden"></p>
                    <p class="text-xs text-slate-500 mt-1">Almeno 6 caratteri, con 1 maiuscola, 1 minuscola, 1 numero e 1 carattere speciale.</p>
                </div>
                <div>
                    <label for="confirm" class="block text-sm font-medium text-slate-700 mb-1">Conferma password</label>
                    <input id="confirm" type="password" name="conferma" required minlength="6"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <p id="confirm-errore" class="text-xs text-red-600 mt-1 hidden"></p>
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

    <script>
        (function () {
            const form = document.getElementById('form-registrazione');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm');

            const usernameErrore = document.getElementById('username-errore');
            const passwordErrore = document.getElementById('password-errore');
            const confirmErrore = document.getElementById('confirm-errore');

            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/;

            function mostraErrore(elemento, messaggioElemento, messaggio) {
                elemento.classList.add('border-red-400');
                messaggioElemento.textContent = messaggio;
                messaggioElemento.classList.remove('hidden');
            }

            function nascondiErrore(elemento, messaggioElemento) {
                elemento.classList.remove('border-red-400');
                messaggioElemento.textContent = '';
                messaggioElemento.classList.add('hidden');
            }

            function validaUsername() {
                const valore = usernameInput.value;

                if (/\s/.test(valore)) {
                    mostraErrore(usernameInput, usernameErrore, 'Lo username non può contenere spazi.');
                    return false;
                }

                if (valore.length < 3) {
                    mostraErrore(usernameInput, usernameErrore, 'Lo username deve avere almeno 3 caratteri e massimo 16.');
                    return false;
                }

                if (valore.length > 16) {
                    mostraErrore(usernameInput, usernameErrore, 'Lo username non può superare 16 caratteri.');
                    return false;
                }

                nascondiErrore(usernameInput, usernameErrore);
                return true;
            }

            function validaPassword() {
                const valore = passwordInput.value;

                if (valore.length < 6) {
                    mostraErrore(passwordInput, passwordErrore, 'La password deve avere almeno 6 caratteri.');
                    return false;
                }

                if (!passwordRegex.test(valore)) {
                    mostraErrore(passwordInput, passwordErrore, 'Serve almeno 1 maiuscola, 1 minuscola, 1 numero e 1 carattere speciale.');
                    return false;
                }

                nascondiErrore(passwordInput, passwordErrore);
                return true;
            }

            function validaConferma() {
                if (confirmInput.value !== passwordInput.value) {
                    mostraErrore(confirmInput, confirmErrore, 'Le due password non coincidono.');
                    return false;
                }

                nascondiErrore(confirmInput, confirmErrore);
                return true;
            }

            usernameInput.addEventListener('input', function () {
                // impedisce fisicamente di digitare spazi nello username
                usernameInput.value = usernameInput.value.replace(/\s/g, '');
                validaUsername();
            });

            passwordInput.addEventListener('input', function () {
                validaPassword();
                if (confirmInput.value) {
                    validaConferma();
                }
            });

            confirmInput.addEventListener('input', validaConferma);

            form.addEventListener('submit', function (evento) {
                const usernameOk = validaUsername();
                const passwordOk = validaPassword();
                const confermaOk = validaConferma();

                if (!usernameOk || !passwordOk || !confermaOk) {
                    evento.preventDefault();
                }
            });
        })();
    </script>
<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>
