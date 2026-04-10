<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = (int)($_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        DELETE FROM password_tag_assignments
        WHERE password_id = ?
    ")->execute([$id]);

    $pdo->prepare("
        DELETE FROM passwords
        WHERE id = ?
    ")->execute([$id]);

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Fehler beim Löschen.");
}

header("Location: index.php");
exit;