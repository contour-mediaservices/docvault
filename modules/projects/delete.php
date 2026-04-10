<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   PARAMETER
===================================================== */
$fileId    = (int)($_POST["id"] ?? 0);
$projectId = (int)($_POST["project_id"] ?? 0);

if ($fileId <= 0 || $projectId <= 0) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   DATEI LADEN
===================================================== */
$stmt = $pdo->prepare("SELECT * FROM project_files WHERE id=?");
$stmt->execute([$fileId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    header("Location: view.php?id=".$projectId);
    exit;
}

/* =====================================================
   PROJEKT
===================================================== */
$stmt = $pdo->prepare("SELECT created_at FROM projects WHERE id=?");
$stmt->execute([$projectId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$year = date('Y', strtotime($row["created_at"] ?? 'now'));

/* =====================================================
   PFAD
===================================================== */
$basePath = '/volume1/web/docvault/';
$filePath = $basePath . "archiv_projekt/$year/$projectId/" . $file["filename"];

/* =====================================================
   LÖSCHEN
===================================================== */
if (file_exists($filePath)) {
    unlink($filePath);
}

/* DB */
$pdo->prepare("DELETE FROM project_files WHERE id=?")->execute([$fileId]);

/* =====================================================
   REDIRECT
===================================================== */
header("Location: view.php?id=".$projectId);
exit;