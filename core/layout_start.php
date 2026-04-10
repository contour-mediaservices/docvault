<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/auth.php";
?>
<!DOCTYPE html>
<html lang="de">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($moduleTitle ?? "DocVault") ?></title>

<!-- Bootstrap -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<!-- Bootstrap Icons -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Admin Layout -->
<link rel="stylesheet"
href="/docvault/css/docvault.css">

</head>


<body class="bg-light">

<?php require_once __DIR__ . "/../navbar.php"; ?>

<div class="container-fluid mt-4">

<div class="row">

<!-- SIDEBAR -->
<div class="col-lg-2">

<?php
if (!empty($activeModule)) {

$sidebarFile = __DIR__ . "/../modules/" . $activeModule . "/module_nav.php";

if (file_exists($sidebarFile)) {
require $sidebarFile;
}

}
?>

</div>

<!-- CONTENT -->
<div class="col-lg-10 p-4">