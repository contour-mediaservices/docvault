<?php
declare(strict_types=1);

/*
Parameter:

$title   = string
$actions = array

[
  [
    'url'   => '...',
    'label' => '...',
    'icon'  => 'bi-...',
    'class' => 'btn-success | btn-outline-dark | btn-outline-secondary',
    'target' => '_blank' (optional),
    'title'  => 'Tooltip' (optional)
  ]
]
*/
?>

<div class="d-flex align-items-center justify-content-between mb-4">

    <div class="d-flex align-items-center gap-2">
        <h2 class="fw-bold mb-0">
            <?= htmlspecialchars($title ?? '') ?>
        </h2>
    </div>

    <div class="d-flex gap-2">

        <?php if (!empty($actions) && is_array($actions)): ?>
            <?php foreach ($actions as $btn): ?>

                <a href="<?= htmlspecialchars($btn['url'] ?? '#') ?>"
                   class="btn <?= htmlspecialchars($btn['class'] ?? 'btn-outline-dark') ?> btn-sm"
                   title="<?= htmlspecialchars($btn['title'] ?? '') ?>"
                   <?= !empty($btn['target']) ? 'target="'.htmlspecialchars($btn['target']).'"' : '' ?>>

                    <?php if (!empty($btn['icon'])): ?>
                        <i class="bi <?= htmlspecialchars($btn['icon']) ?>"></i>
                    <?php endif; ?>

                    <?= htmlspecialchars($btn['label'] ?? '') ?>

                </a>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</div>