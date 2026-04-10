<?php
declare(strict_types=1);

$formConfig = $formConfig ?? [];

$backUrl         = $formConfig['back_url'] ?? 'index.php';
$showSaveNew     = $formConfig['show_save_new'] ?? true;
$showSaveClose   = $formConfig['show_save_close'] ?? true;
$isImportant     = $formConfig['important'] ?? false;

$saveClass = $isImportant ? 'btn-warning' : 'btn-success';
?>

<div class="card-footer">
    <div class="d-flex gap-2">

        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>

        <button type="submit" name="action" value="save" class="btn <?= $saveClass ?>">
            Speichern
        </button>

        <?php if ($showSaveNew): ?>
            <button type="submit" name="action" value="save_new" class="btn <?= $saveClass ?>">
                Speichern & Neu
            </button>
        <?php endif; ?>

        <?php if ($showSaveClose): ?>
            <button type="submit" name="action" value="save_close" class="btn <?= $saveClass ?>">
                Speichern & Schließen
            </button>
        <?php endif; ?>

    </div>
</div>