<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

/* Sub löschen */
if (isset($_GET['sub'])) {

    $id  = (int)$_GET['sub'];
    $cat = (int)($_GET['cat'] ?? 0);

    $pdo->prepare("DELETE FROM subcategories WHERE id=?")->execute([$id]);

    header("Location: edit.php?id=".$cat);
    exit;
}

/* Kategorie löschen */
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
}

header("Location: index.php");
exit;