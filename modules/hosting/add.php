<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'hosting';

/* =====================================================
   SERVICES
===================================================== */
$services = $pdo->query("
    SELECT id, name 
    FROM hosting_service_types 
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action       = $_POST["action"] ?? "save";

    $domain       = trim($_POST["domain"] ?? "");
    $customer     = trim($_POST["customer"] ?? "");
    $billing_date = $_POST["billing_date"] ?? date('Y-m-d');
    $billing_cycle= $_POST["billing_cycle"] ?? "yearly";
    $notes        = trim($_POST["notes"] ?? "");

    $selectedServices = $_POST["services"] ?? [];

    if ($domain !== "") {

        $pdo->beginTransaction();

        try {

            /* ==============================
               INSERT HOSTING
            ============================== */
            $pdo->prepare("
                INSERT INTO hosting_services
                (domain, customer, billing_date, billing_cycle, notes)
                VALUES (?,?,?,?,?)
            ")->execute([
                $domain,
                $customer,
                $billing_date,
                $billing_cycle,
                $notes
            ]);

            $hostingId = (int)$pdo->lastInsertId();

            /* ==============================
               SERVICES ZUORDNEN
            ============================== */
            if (!empty($selectedServices)) {

                $stmt = $pdo->prepare("
                    INSERT INTO hosting_service_assignments
                    (hosting_id, service_type_id)
                    VALUES (?,?)
                ");

                foreach ($selectedServices as $sid) {
                    $stmt->execute([$hostingId, (int)$sid]);
                }
            }

            $pdo->commit();

            /* ==============================
               REDIRECT
            ============================== */
            if ($action === "save_close") {
                header("Location: index.php");
            } elseif ($action === "save_new") {
                header("Location: add.php");
            } else {
                header("Location: edit.php?id=".$hostingId);
            }
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
        }
    }
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title' => 'Hosting hinzufügen',
    'show_back' => true,
    'back_url' => 'index.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm">
<div class="card-body">

<form method="post">

<div class="row g-4">

<!-- DOMAIN -->
<div class="col-md-6">
<label class="form-label">Domain</label>
<input type="text" name="domain" class="form-control" required>
</div>

<!-- KUNDE -->
<div class="col-md-6">
<label class="form-label">Kunde</label>
<input type="text" name="customer" class="form-control">
</div>

<!-- ABRECHNUNG -->
<div class="col-md-6">
<label class="form-label">Abrechnungsdatum</label>
<input type="date" name="billing_date" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<div class="col-md-6">
<label class="form-label">Zyklus</label>
<select name="billing_cycle" class="form-select">
<option value="monthly">monatlich</option>
<option value="yearly" selected>jährlich</option>
</select>
</div>

<!-- SERVICES -->
<div class="col-12">
<label class="form-label">Leistungen</label>

<div class="d-flex flex-wrap gap-2">

<?php foreach ($services as $s): ?>
<label class="badge bg-light text-dark border">
<input type="checkbox" name="services[]" value="<?= $s["id"] ?>">
<?= htmlspecialchars($s["name"]) ?>
</label>
<?php endforeach; ?>

</div>

</div>

<!-- NOTIZEN -->
<div class="col-12">
<label class="form-label">Notizen</label>
<textarea name="notes" class="form-control" rows="4"></textarea>
</div>

<!-- BUTTONS -->
<div class="col-12">
<?php
$formConfig = [
    'back_url' => 'index.php',
    'important' => true
];
require __DIR__ . "/../../core/form_actions.php";
?>
</div>

</div>

</form>

</div>
</div>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>