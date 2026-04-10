<?php
declare(strict_types=1);

/* =====================================================
   ASSETS – NEU COUNT
===================================================== */
function getNewAssetsCount(): int
{
    global $pdo;

    try {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM assets
            WHERE status = 'neu'
        ");

        return (int)$stmt->fetchColumn();

    } catch (Throwable $e) {
        return 0;
    }
}