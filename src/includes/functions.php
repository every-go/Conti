<?php

/**
 * Formatta un importo in euro, stile italiano: € 1.234,56
 */
function formatMoney(float $amount): string
{
    return '€ ' . number_format($amount, 2, ',', '.');
}

/**
 * Classe Tailwind per colorare un importo: verde se > 0, rosso se <= 0.
 */
function moneyColorClass(float $amount): string
{
    return $amount > 0 ? 'text-emerald-600' : 'text-red-600';
}

/**
 * Ritorna gli ID di tutti i conti discendenti (figli, nipoti, ...) di un conto.
 * NON include l'id del conto di partenza. Funziona a livelli infiniti,
 * senza bisogno di CTE ricorsive SQL (compatibile con qualsiasi versione di MySQL).
 */
function getDescendantContoIds(PDO $pdo, int $contoId): array
{
    $ids = [];
    $frontier = [$contoId];

    while (!empty($frontier)) {
        $placeholders = implode(',', array_fill(0, count($frontier), '?'));
        $stmt = $pdo->prepare("SELECT id FROM conti WHERE parent_conto_id IN ($placeholders)");
        $stmt->execute($frontier);
        $children = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if (empty($children)) {
            break;
        }

        $ids = array_merge($ids, $children);
        $frontier = $children;
    }

    return $ids;
}

/**
 * Somma di tutti i Record appartenenti a un conto E a tutti i suoi sotto-conti,
 * a qualunque livello di profondità.
 */
function getContoTotal(PDO $pdo, int $contoId): float
{
    $ids = array_merge([$contoId], getDescendantContoIds($pdo, $contoId));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(soldi), 0) FROM record WHERE conto_id IN ($placeholders)");
    $stmt->execute($ids);

    return (float) $stmt->fetchColumn();
}

/**
 * Risale la catena dei genitori fino alla radice per trovare l'anno del conto
 * (i sotto-conti non hanno un anno proprio: lo ereditano dal conto principale).
 */
function getContoAnno(PDO $pdo, int $contoId): ?int
{
    $stmt = $pdo->prepare('SELECT anno, parent_conto_id FROM conti WHERE id = ?');
    $current = $contoId;
    $visited = [];

    while ($current !== null) {
        if (isset($visited[$current])) {
            break; // sicurezza anti-loop, non dovrebbe mai succedere
        }
        $visited[$current] = true;

        $stmt->execute([$current]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if ($row['anno'] !== null) {
            return (int) $row['anno'];
        }
        $current = $row['parent_conto_id'] !== null ? (int) $row['parent_conto_id'] : null;
    }

    return null;
}

/**
 * Costruisce il percorso (breadcrumb) dalla radice fino al conto indicato,
 * es. [2022] -> [Sardegna] -> [Trasporti].
 */
function getContoBreadcrumb(PDO $pdo, int $contoId): array
{
    $stmt = $pdo->prepare('SELECT id, nome, parent_conto_id FROM conti WHERE id = ?');
    $chain = [];
    $current = $contoId;
    $visited = [];

    while ($current !== null) {
        if (isset($visited[$current])) {
            break;
        }
        $visited[$current] = true;

        $stmt->execute([$current]);
        $row = $stmt->fetch();
        if (!$row) {
            break;
        }
        array_unshift($chain, $row);
        $current = $row['parent_conto_id'] !== null ? (int) $row['parent_conto_id'] : null;
    }

    return $chain;
}

/**
 * Verifica che un conto appartenga davvero all'utente indicato.
 * Va sempre chiamata prima di leggere/scrivere un conto specifico.
 */
function contoBelongsToUser(PDO $pdo, int $contoId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT user_id FROM conti WHERE id = ?');
    $stmt->execute([$contoId]);
    $row = $stmt->fetch();

    return $row && (int) $row['user_id'] === $userId;
}

/**
 * Verifica che un record appartenga (tramite il suo conto) all'utente indicato.
 */
function recordBelongsToUser(PDO $pdo, int $recordId, int $userId): bool
{
    $stmt = $pdo->prepare(
        'SELECT c.user_id FROM record r JOIN conti c ON r.conto_id = c.id WHERE r.id = ?'
    );
    $stmt->execute([$recordId]);
    $row = $stmt->fetch();

    return $row && (int) $row['user_id'] === $userId;
}

/**
 * Elenco di tutti i conti di un utente, in ordine gerarchico, con un campo
 * "depth" (profondità) utile per indentare le voci nelle <select>.
 */
function getUserContiTree(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, anno, parent_conto_id FROM conti WHERE user_id = ? ORDER BY anno ASC, nome ASC'
    );
    $stmt->execute([$userId]);
    $all = $stmt->fetchAll();

    $byParent = [];
    foreach ($all as $row) {
        $key = $row['parent_conto_id'] !== null ? (int) $row['parent_conto_id'] : 0;
        $byParent[$key][] = $row;
    }

    $result = [];
    $walk = function (int $parentKey, int $depth) use (&$walk, &$byParent, &$result) {
        if (empty($byParent[$parentKey])) {
            return;
        }
        foreach ($byParent[$parentKey] as $row) {
            $row['depth'] = $depth;
            $result[] = $row;
            $walk((int) $row['id'], $depth + 1);
        }
    };
    $walk(0, 0);

    return $result;
}