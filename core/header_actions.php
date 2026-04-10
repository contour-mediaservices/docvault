<?php
declare(strict_types=1);

$headerConfig = $headerConfig ?? [];

$title    = $headerConfig['title'] ?? '';
$showBack = $headerConfig['show_back'] ?? false;
$backUrl  = $headerConfig['back_url'] ?? 'index.php';

$showNew  = $headerConfig['show_new'] ?? false;
$newUrl   = $headerConfig['new_url'] ?? 'add.php';

$extra    = $headerConfig['extra'] ?? [];

/* =====================================================
   URL HELFER
===================================================== */
function dv_header_resolve_url(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '#';
    }

    /* Bereits absolut oder extern */
    if (
        str_starts_with($url, '/')
        || preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)
        || str_starts_with($url, '#')
        || str_starts_with($url, '?')
    ) {
        return $url;
    }

    /* Relativer Dateiname innerhalb des aktuellen Modulordners */
    $currentDir = rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/\\');

    return $currentDir . '/' . ltrim($url, '/');
}

$backUrl = dv_header_resolve_url((string)$backUrl);
$newUrl  = dv_header_resolve_url((string)$newUrl);

foreach ($extra as $k => $btn) {
    $extra[$k]['url'] = dv_header_resolve_url((string)($btn['url'] ?? '#'));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <h1 class="fw-bold m-0"><?= htmlspecialchars($title) ?></h1>

    <div class="d-flex gap-2">

        <?php if ($showBack): ?>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Übersicht
            </a>
        <?php endif; ?>

        <?php if ($showNew): ?>
            <a href="<?= htmlspecialchars($newUrl) ?>" class="btn btn-warning">
                <i class="bi bi-plus-circle"></i> Neu
            </a>
        <?php endif; ?>

        <?php foreach ($extra as $btn): ?>
            <a href="<?= htmlspecialchars($btn['url'] ?? '#') ?>"
               class="btn <?= htmlspecialchars($btn['class'] ?? 'btn-outline-secondary') ?>"
               <?= !empty($btn['target']) ? 'target="'.htmlspecialchars((string)$btn['target']).'"' : '' ?>
               title="<?= htmlspecialchars((string)($btn['title'] ?? '')) ?>">

                <?php if (!empty($btn['icon'])): ?>
                    <i class="bi <?= htmlspecialchars((string)$btn['icon']) ?>"></i>
                <?php endif; ?>

                <?= htmlspecialchars((string)($btn['label'] ?? '')) ?>
            </a>
        <?php endforeach; ?>

    </div>

</div>