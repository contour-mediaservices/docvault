<?php
declare(strict_types=1);

/* =====================================================
   HELPER LADEN (KORREKTER PFAD!)
===================================================== */
require_once __DIR__ . '/core/dashboard_helper.php';
require_once __DIR__ . '/core/hosting_helper.php';

/* Aktives Modul */
$activeModule = $activeModule ?? 'assets';

/* =====================================================
   ASSETS COUNT
===================================================== */
$newCount = getNewAssetsCount();

/* =====================================================
   HOSTING STATUS
===================================================== */
$hostingOverdue = getHostingOverdueCount();
$hostingSoon    = getHostingDueCount();

$hostingTotal = $hostingOverdue + $hostingSoon;

/* Active Helper */
function navActive(string $module, string $activeModule): string {
    return $module === $activeModule ? 'active fw-semibold' : '';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
  <div class="container-fluid">

    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2"
       href="/docvault/index.php">
      <i class="bi bi-folder2-open"></i>
      <span>DocVault</span>
    </a>

    <button class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">

      <ul class="navbar-nav me-auto align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link <?= navActive('assets',$activeModule) ?>"
             href="/docvault/modules/assets/index.php">
            <i class="bi bi-archive"></i>
            Assets
            <?php if ($newCount > 0): ?>
              <span class="badge bg-warning text-dark ms-1">
                <?= $newCount ?>
              </span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= navActive('projects',$activeModule) ?>"
             href="/docvault/modules/projects/index.php">
            <i class="bi bi-kanban"></i>
            Projekte
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= navActive('hosting',$activeModule) ?>"
             href="/docvault/modules/hosting/index.php">
            <i class="bi bi-hdd-network"></i>
            Hosting
            <?php if ($hostingTotal > 0): ?>
              <span class="badge <?= $hostingOverdue > 0 ? 'bg-danger' : 'bg-warning text-dark' ?> ms-1">
                <?= $hostingTotal ?>
              </span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= navActive('passwords',$activeModule) ?>"
             href="/docvault/modules/passwords/index.php">
            <i class="bi bi-shield-lock"></i>
            Passwörter
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= navActive('system',$activeModule) ?>"
             href="/docvault/modules/system/monitor.php">
            <i class="bi bi-gear"></i>
            System
          </a>
        </li>

      </ul>

      <!-- SUCHE -->
      <form class="d-flex mx-auto"
            style="width: 500px; max-width: 45%;"
            method="get"
            action="/docvault/modules/search/index.php">

        <input class="form-control form-control-sm text-center"
               type="search"
               name="q"
               placeholder="Zentrale Suche..."
               required>

        <input type="hidden" name="type" value="all">
      </form>

      <!-- RECHTS -->
      <ul class="navbar-nav ms-auto align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link"
             href="/docvault/modules/search/index.php">
            <i class="bi bi-search"></i>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link text-warning fw-semibold"
             href="/docvault/logout.php"
             onclick="return confirm('Wirklich abmelden?');">
            <i class="bi bi-box-arrow-right"></i>
          </a>
        </li>

      </ul>

    </div>
  </div>
</nav>