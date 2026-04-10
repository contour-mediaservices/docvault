<?php
require_once __DIR__ . "/../../config.php";

$categoryId = (int)($_GET["category_id"] ?? 0);

$stmt = $pdo->prepare("
    SELECT id, name 
    FROM subcategories
    WHERE category_id = ?
    ORDER BY name
");
$stmt->execute([$categoryId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));