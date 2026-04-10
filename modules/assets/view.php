<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'assets';

/* =====================================================
   ID prüfen
===================================================== */
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   Asset laden
===================================================== */
$stmt = $pdo->prepare("
SELECT 
    a.*,
    c.name AS category_name
FROM assets a
LEFT JOIN categories c ON c.id = a.category_id
WHERE a.id=?
");
$stmt->execute([$id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   TAGS
===================================================== */
$stmt = $pdo->prepare("
    SELECT t.name
    FROM asset_tag_assignments ata
    JOIN asset_tags t ON t.id = ata.tag_id
    WHERE ata.asset_id = ?
    ORDER BY t.name
");
$stmt->execute([$id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* =====================================================
   HIGHLIGHT FUNCTION
===================================================== */
function highlightText(string $text): string
{
    $text = htmlspecialchars($text);

    // Betrag (€)
    $text = preg_replace(
        '/(\d{1,3}(\.\d{3})*,\d{2}\s?€)/u',
        '<span style="font-weight:bold;color:#D7742C;">$1</span>',
        $text
    );

    // Datum
    $text = preg_replace(
        '/\b(\d{2}\.\d{2}\.\d{4})\b/',
        '<span style="font-weight:bold;">$1</span>',
        $text
    );

    return nl2br($text);
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'     => $asset["bezeichnung"] ?: $asset["name"],
    'show_back' => true,
    'back_url'  => '/docvault/modules/assets/index.php',
    'extra'     => [
        [
            'url'   => '/docvault/modules/assets/edit.php?id=' . (int)$id,
            'label' => 'Bearbeiten',
            'icon'  => 'bi-pencil',
            'class' => 'btn-warning'
        ]
    ]
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm mb-3">
<div class="card-body">

<div class="row g-4">

<div class="col-md-4">
<label class="form-label">Bezeichnung</label>
<div class="form-control">
<?= htmlspecialchars((string)($asset["bezeichnung"] ?? "")) ?>
</div>
</div>

<div class="col-md-4">
<label class="form-label">Kategorie</label>
<div class="form-control">
<?= htmlspecialchars((string)($asset["category_name"] ?? "")) ?>
</div>
</div>

<div class="col-md-4">
<label class="form-label">Jahr</label>
<div class="form-control">
<?= htmlspecialchars((string)($asset["year"] ?? "")) ?>
</div>
</div>

</div>

<div class="row g-4 mt-1">
<div class="col-12">

<label class="form-label">Tags</label>

<div class="d-flex flex-wrap gap-2">
<?php if (!empty($tags)): ?>
    <?php foreach ($tags as $t): ?>
        <span class="badge bg-light text-dark border">
            <?= htmlspecialchars((string)$t) ?>
        </span>
    <?php endforeach; ?>
<?php else: ?>
    <span class="text-muted small">Keine Tags</span>
<?php endif; ?>
</div>

</div>
</div>

</div>
</div>

<!-- PDF -->
<div class="card shadow-sm">
<div class="card-body p-0">

<?php if (!empty($asset["dokument_pfad"])): ?>

<iframe
src="/docvault/<?= htmlspecialchars($asset["dokument_pfad"]) ?>"
style="width:100%;height:85vh;border:0;">
</iframe>

<?php else: ?>

<div class="p-4 text-muted text-center">
Kein Dokument vorhanden
</div>

<?php endif; ?>

</div>
</div>

<!-- OCR TEXT -->
<?php if (!empty($asset["notes"])): ?>
<div class="card shadow-sm mt-3">
<div class="card-body">

<label class="form-label"><strong>Notizen / OCR</strong></label>

<div class="form-control" style="
white-space: pre-wrap;
line-height:1.6;
max-height:400px;
overflow:auto;
background:#f8f9fa;
">
<?= highlightText((string)$asset["notes"]) ?>
</div>

</div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>