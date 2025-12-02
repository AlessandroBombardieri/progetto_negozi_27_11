<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
$numero_ordine = '';
if (!empty($_POST['numero_ordine'])) {
    $codice_negozio = $_POST['numero_ordine'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $data_consegna = $_POST['data_consegna'];
    if ($data_consegna === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (update_data_consegna_ordine($numero_ordine, $data_consegna)) {
            $ok = "Data consegna inserita con successo";
        } else {
            $err = "La data della consegna non può essere precedente rispetto alla data dell'ordine";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Inserisci data consegna</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Inserisci data consegna</h1>
            <a class="btn btn-outline-secondary me-2" href="home.php">← Torna a storico ordini</a>
        </div>

        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <form method="post" class="card p-3 shadow-sm">
            <input type="hidden" name="numero_ordine" value="<?= htmlspecialchars($numero_ordine) ?>">
            <div class="col-md-4">
                <label class="form-label">Data consegna</label>
                <input type="date" name="data_consegna" class="form-control"
                    value="<?= htmlspecialchars($_POST['data_consegna'] ?? date('Y-m-d')) ?>" required>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-success" name="submit_add" value="1">Inserisci</button>
            </div>
        </form>
    </div>
</body>

</html>