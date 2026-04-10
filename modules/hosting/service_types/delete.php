<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

/* =====================================================
   ID
===================================================== */
$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {

    $stmt = $pdo->prepare("
        DELETE FROM hosting_service_types
        WHERE id = ?
    ");
    $stmt->execute([$id]);
}

/* =====================================================
   REDIRECT
===================================================== */
header("Location: index.php");
exit;