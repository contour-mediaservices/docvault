<?php
declare(strict_types=1);

$current = basename($_SERVER['PHP_SELF']);
$path    = $_SERVER['PHP_SELF'];

$isTags        = str_contains($path, '/asset_tags/');
$isCategories  = str_contains($path, '/assets_categories/');
?>

<nav class="nav nav-tabs mb-4 align-items-center">

    <!-- Übersicht -->
    <a class="nav-link d-flex align-items-center gap-1 <?= ($current === 'index.php' && !$isTags && !$isCategories) ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/assets/index.php">
        <i class="bi bi-list-ul"></i>
        Übersicht
    </a>

    <!-- Neu -->
    <a class="nav-link d-flex align-items-center gap-1 <?= ($current === 'add.php') ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/assets/add.php">
        <i class="bi bi-plus-circle"></i>
        Neu
    </a>

    <!-- Kategorien -->
    <a class="nav-link d-flex align-items-center gap-1 <?= $isCategories ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/assets/assets_categories/index.php">
        <i class="bi bi-folder"></i>
        Kategorien
    </a>

    <!-- Tags -->
    <a class="nav-link d-flex align-items-center gap-1 <?= $isTags ? 'active fw-semibold' : '' ?>"
       href="/docvault/modules/asset_tags/index.php">
        <i class="bi bi-tags"></i>
        Tags
    </a>

</nav>