<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

$activeModule = 'assets';

$err = "";

/* POST */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "save";
    $name   = trim($_POST["name"] ?? "");

    if ($name === "") {
        $err = "Bitte Namen eingeben.";
    } else {

        $pdo->prepare("
            INSERT INTO categories (name)
            VALUES (?)
        ")->execute([$name]);

        if ($action === "save_close") {
            header("Location: index.php");
        } elseif ($action === "save_new") {
            header("Location: add.php");
        } else {
            header("Location: edit.php?id=" . $pdo->lastInsertId());
        }
        exit;
    }
}

/* LAYOUT */
require_once __DIR__ . "/../../../core/module_layout_start.php";

/* HEADER */
$headerConfig = [
    'title' => 'Neue Kategorie',
    'show_back' => true,
    'back_url' => 'index.php'
];

require_once __DIR__ . "/../../../core/header_actions.php";
?>

<?php if ($err): ?>
<div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
<div class="card-body">

<form method="post">

<div class="row g-4">

<div class="col-12">
<label class="form-label">Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url' => 'index.php',
    'show_save_new' => true,
    'show_save_close' => true,
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