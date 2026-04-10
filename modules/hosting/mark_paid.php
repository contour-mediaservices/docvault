<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   ID
===================================================== */
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   DATUM HOLEN
===================================================== */
$stmt = $pdo->prepare("SELECT billing_date, billing_cycle FROM hosting_services WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   NEUES DATUM
===================================================== */
$current = $row["billing_date"];

if ($row["billing_cycle"] === "monthly") {
    $next = date('Y-m-d', strtotime("+1 month", strtotime($current)));
} else {
    $next = date('Y-m-d', strtotime("+1 year", strtotime($current)));
}

/* =====================================================
   UPDATE
===================================================== */
$pdo->prepare("
    UPDATE hosting_services
    SET billing_date = ?
    WHERE id = ?
")->execute([$next, $id]);

/* =====================================================
   WICHTIG: SAUBERER REDIRECT
===================================================== */
header("Location: /docvault/modules/hosting/index.php");
exit;