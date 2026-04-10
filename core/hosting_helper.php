<?php
declare(strict_types=1);

/* =====================================================
   HOSTING – ÜBERFÄLLIG
===================================================== */
function getHostingOverdueCount(): int
{
    global $pdo;

    try {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM hosting_services
            WHERE billing_date < CURDATE()
        ");

        return (int)$stmt->fetchColumn();

    } catch (Throwable $e) {
        return 0;
    }
}


/* =====================================================
   HOSTING – ERINNERUNG (30 TAGE VORHER)
===================================================== */
function getHostingDueCount(): int
{
    global $pdo;

    try {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM hosting_services
            WHERE billing_date >= CURDATE()
            AND billing_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");

        return (int)$stmt->fetchColumn();

    } catch (Throwable $e) {
        return 0;
    }
}