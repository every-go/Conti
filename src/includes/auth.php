<?php

/**
 * Se l'utente non ha fatto login, lo rimanda alla pagina di accesso
 * e interrompe l'esecuzione della pagina corrente.
 */
function requireLogin(): void
{
    if (empty($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }
}
