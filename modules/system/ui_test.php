<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

$activeModule = 'system';

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$title = "UI Testseite";

$actions = [
    [
        'url'   => '/docvault/modules/hosting/index.php',
        'label' => 'Übersicht',
        'icon'  => 'bi-arrow-left',
        'class' => 'btn-outline-secondary'
    ]
];

require_once __DIR__ . "/../../core/header.php";
?>

<div class="card shadow-sm">
<div class="card-body">

<h5 class="fw-bold mb-3">Testformular</h5>

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Domain</label>
<input type="text" class="form-control" value="example.de">
</div>

<div class="col-md-6">
<label class="form-label">Kunde</label>
<input type="text" class="form-control" value="Max Mustermann">
</div>

<div class="col-md-4">
<label class="form-label">Datum</label>
<input type="date" class="form-control" value="2026-03-26">
</div>

<div class="col-md-4">
<label class="form-label">Zyklus</label>
<select class="form-select">
<option>monatlich</option>
<option selected>jährlich</option>
</select>
</div>

</div>

</div>

<div class="card-footer">

<div class="d-flex gap-2">

<a href="#" class="btn btn-outline-secondary">
<i class="bi bi-arrow-left"></i> Zurück
</a>

<button class="btn btn-success">
Speichern
</button>

<button class="btn btn-success">
Speichern & Neu
</button>

<button class="btn btn-success">
Speichern & Schließen
</button>

</div>

</div>

</div>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>