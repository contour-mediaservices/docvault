<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM hosting_service_types WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "save";
    $name   = trim($_POST["name"] ?? "");

    if ($name !== "") {

        $pdo->prepare("
            UPDATE hosting_service_types
            SET name = ?
            WHERE id = ?
        ")->execute([$name, $id]);

        if ($action === "save_close") {
            header("Location: index.php");
        } elseif ($action === "save_new") {
            header("Location: add.php");
        } else {
            header("Location: edit.php?id=" . $id);
        }
        exit;
    }
}

$activeModule = 'hosting';
require_once __DIR__ . "/../../../core/module_layout_start.php";

/* HEADER */
$headerConfig = [
    'title' => 'Leistung bearbeiten',
    'show_back' => true,
    'back_url' => 'index.php'
];

require_once __DIR__ . "/../../../core/header_actions.php";
?>

<div class="card shadow-sm">
<div class="card-body">

<form method="post">

<div class="row g-4">

<div class="col-12">
<label class="form-label">Bezeichnung</label>
<input type="text"
       name="name"
       class="form-control"
       value="<?= htmlspecialchars((string)$row["name"]) ?>"
       required>
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url' => 'index.php',
    'important' => true
];
require __DIR__ . "/../../../core/form_actions.php";
?>
</div>

</div>

</form>

</div>
</div>

<?php require_once __DIR__ . "/../../../core/layout_end.php"; ?>