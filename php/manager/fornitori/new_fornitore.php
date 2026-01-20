<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partita_iva = $_POST['partita_iva'];
    $indirizzo = $_POST['indirizzo'];
    if (
        $partita_iva === '' || $indirizzo === ''
    ) {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (
            add_fornitore(
                $partita_iva,
                $indirizzo,
            )
        ) {
            $ok = "Fornitore creato con successo";
        } else {
            $err = "Errore creazione fornitore";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Nuovo fornitore</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Nuovo fornitore</h1>
            <a class="btn btn-outline-secondary" href="home.php">← Torna a Fornitori</a>
        </div>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
        <form method="post" class="card p-3 shadow-sm">
            <div class="col-md-4">
                <label class="form-label">Partita IVA</label>
                <input name="partita_iva" class="form-control" maxlength="11" required>
            </div><br>
            <div class="col-md-4">
                <label class="form-label">Indirizzo</label>
                <input name="indirizzo" class="form-control" maxlength="100" required>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Crea</button>
            </div>
        </form>
    </div>
</body>

</html>