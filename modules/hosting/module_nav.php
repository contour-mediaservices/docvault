<?php
declare(strict_types=1);

$current = basename($_SERVER['PHP_SELF']);
$path    = $_SERVER['PHP_SELF'];
?>

<nav class="nav nav-tabs mb-4 align-items-center">

    <!-- Übersicht -->
    <a class="nav-link d-flex align-items-center gap-1 <?= $current === 'index.php' ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/hosting/index.php">
        <i class="bi bi-list-ul"></i>
        Übersicht
    </a>

    <!-- Neu -->
    <a class="nav-link d-flex align-items-center gap-1 <?= $current === 'add.php' ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/hosting/add.php">
        <i class="bi bi-plus-circle"></i>
        Neu
    </a>

    <!-- Service-Typen -->
    <a class="nav-link d-flex align-items-center gap-1 <?= str_contains($path, 'service_types') ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/hosting/service_types/index.php">
        <i class="bi bi-gear"></i>
        Service-Typen
    </a>

</nav>