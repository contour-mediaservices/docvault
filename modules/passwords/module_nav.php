<?php
declare(strict_types=1);

$current = basename($_SERVER['PHP_SELF']);
$path    = $_SERVER['PHP_SELF'];

$isTags = str_contains($path, '/password_tags/');
?>

<nav class="nav nav-tabs mb-4 align-items-center">

    <!-- Übersicht -->
    <a class="nav-link d-flex align-items-center gap-1 <?= ($current === 'index.php' && !$isTags) ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/passwords/index.php">
        <i class="bi bi-list-ul"></i>
        Übersicht
    </a>

    <!-- Neu -->
    <a class="nav-link d-flex align-items-center gap-1 <?= ($current === 'add.php') ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/passwords/add.php">
        <i class="bi bi-plus-circle"></i>
        Neu
    </a>

    <!-- Tags -->
    <a class="nav-link d-flex align-items-center gap-1 <?= $isTags ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/password_tags/index.php">
        <i class="bi bi-tags"></i>
        Tags
    </a>

</nav>