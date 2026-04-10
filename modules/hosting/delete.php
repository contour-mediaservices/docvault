<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {
    $pdo->prepare("DELETE FROM hosting_services WHERE id=?")->execute([$id]);
}

header("Location: index.php");
exit;