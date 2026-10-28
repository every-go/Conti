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
                    <div class="relative">
                        <input id="password" type="password" name="password" required minlength="6"
                            autocomplete="new-password"
                            aria-describedby="password-requisiti-titolo password-requisiti"
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
                    <p id="password-errore" class="text-xs text-red-600 mt-1 hidden"></p>

                    <p id="password-requisiti-titolo" class="text-xs text-slate-500 mt-2">La password deve contenere:</p>
                    <ul id="password-requisiti" class="mt-1 space-y-1 text-xs">
                        <li data-requisito="lunghezza" class="flex items-center gap-1.5 text-red-700">
                            <span aria-hidden="true" class="inline-block w-4 text-center font-bold">
                                <span data-icona="ok" hidden>✓</span>
                                <span data-icona="ko">✗</span>
                            </span>
                            <span>Almeno 6 caratteri <span class="sr-only" data-stato>(non soddisfatto)</span></span>
                        </li>
                        <li data-requisito="maiuscola" class="flex items-center gap-1.5 text-red-700">
                            <span aria-hidden="true" class="inline-block w-4 text-center font-bold">
                                <span data-icona="ok" hidden>✓</span>
                                <span data-icona="ko">✗</span>
                            </span>
                            <span>Una lettera maiuscola <span class="sr-only" data-stato>(non soddisfatto)</span></span>
                        </li>
                        <li data-requisito="minuscola" class="flex items-center gap-1.5 text-red-700">
                            <span aria-hidden="true" class="inline-block w-4 text-center font-bold">
                                <span data-icona="ok" hidden>✓</span>
                                <span data-icona="ko">✗</span>
                            </span>
                            <span>Una lettera minuscola <span class="sr-only" data-stato>(non soddisfatto)</span></span>
                        </li>
                        <li data-requisito="numero" class="flex items-center gap-1.5 text-red-700">
                            <span aria-hidden="true" class="inline-block w-4 text-center font-bold">
                                <span data-icona="ok" hidden>✓</span>
                                <span data-icona="ko">✗</span>
                            </span>
                            <span>Un numero <span class="sr-only" data-stato>(non soddisfatto)</span></span>
                        </li>
                        <li data-requisito="speciale" class="flex items-center gap-1.5 text-red-700">
                            <span aria-hidden="true" class="inline-block w-4 text-center font-bold">
                                <span data-icona="ok" hidden>✓</span>
                                <span data-icona="ko">✗</span>
                            </span>
                            <span>Un carattere speciale <span class="sr-only" data-stato>(non soddisfatto)</span></span>
                        </li>
                    </ul>
                    <p id="password-riepilogo" class="sr-only" aria-live="polite"></p>
                </div>
                <div>
                    <label for="confirm" class="block text-sm font-medium text-slate-700 mb-1">Conferma password</label>
                    <div class="relative">
                        <input id="confirm" type="password" name="conferma" required minlength="6"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 pl-3 pr-11 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
                        <button type="button" id="confirm-toggle" data-toggle-password="confirm"
                            aria-controls="confirm"
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
            const riepilogo = document.getElementById('password-riepilogo');

            // Requisiti password: usati sia per la checklist sia per la validazione all'invio
            const requisiti = [
                { id: 'lunghezza', test: function (v) { return v.length >= 6; } },
                { id: 'maiuscola', test: function (v) { return /[A-Z]/.test(v); } },
                { id: 'minuscola', test: function (v) { return /[a-z]/.test(v); } },
                { id: 'numero', test: function (v) { return /\d/.test(v); } },
                { id: 'speciale', test: function (v) { return /[^A-Za-z0-9]/.test(v); } }
            ];

            let ultimoConteggio = -1;

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

            function aggiornaChecklist(valore) {
                let soddisfatti = 0;

                requisiti.forEach(function (requisito) {
                    const voce = document.querySelector('[data-requisito="' + requisito.id + '"]');
                    const ok = requisito.test(valore);

                    // L'attributo hidden mostra solo l'icona corretta (✓ oppure ✗)
                    voce.querySelector('[data-icona="ok"]').hidden = !ok;
                    voce.querySelector('[data-icona="ko"]').hidden = ok;
                    voce.querySelector('[data-stato]').textContent = ok ? '(soddisfatto)' : '(non soddisfatto)';

                    voce.classList.toggle('text-green-700', ok);
                    voce.classList.toggle('text-red-700', !ok);

                    if (ok) {
                        soddisfatti++;
                    }
                });

                // La live region cambia solo quando cambia il numero di requisiti soddisfatti,
                // così lo screen reader non annuncia nulla a ogni singolo carattere digitato
                if (soddisfatti !== ultimoConteggio) {
                    ultimoConteggio = soddisfatti;
                    riepilogo.textContent = 'Requisiti password soddisfatti: ' + soddisfatti + ' su ' + requisiti.length + '.';
                }
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

                if (!requisiti.every(function (requisito) { return requisito.test(valore); })) {
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
                aggiornaChecklist(passwordInput.value);

                // La checklist già guida l'utente mentre scrive: il messaggio d'errore
                // compare solo all'invio e poi si aggiorna/sparisce mentre corregge
                if (!passwordErrore.classList.contains('hidden')) {
                    validaPassword();
                }

                if (confirmInput.value) {
                    validaConferma();
                }
            });

            confirmInput.addEventListener('input', validaConferma);

            // Mostra / nascondi password (uno per campo)
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

            form.addEventListener('submit', function (evento) {
                const usernameOk = validaUsername();
                const passwordOk = validaPassword();
                const confermaOk = validaConferma();

                if (!usernameOk || !passwordOk || !confermaOk) {
                    evento.preventDefault();
                }
            });

            aggiornaChecklist(passwordInput.value);
        })();
    </script>
<?php require_once __DIR__ . '/includes/logout-modal.php'; ?>
</body>
</html>
