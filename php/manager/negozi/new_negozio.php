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
    $indirizzo = $_POST['indirizzo'];
    $orario_apertura = $_POST['orario_apertura'];
    $nominativo_responsabile = $_POST['nominativo_responsabile'];
    if (
        $indirizzo === '' || $orario_apertura === '' || $nominativo_responsabile === ''
    ) {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (
            add_negozio(
                $indirizzo,
                $orario_apertura,
                $nominativo_responsabile
            )
        ) {
            $ok = "Negozio creato con successo";
        } else {
            $err = "Errore creazione negozio";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Nuovo negozio</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Nuovo negozio</h1>
            <a class="btn btn-outline-secondary" href="home.php">← Torna a Negozi</a>
        </div>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
        <form method="post" class="card p-3 shadow-sm">
            <div class="col-md-4">
                <label class="form-label">Indirizzo</label>
                <input name="indirizzo" class="form-control" required>
            </div><br>
            <div class="col-md-4-3">
                <label class="form-label">Orario di apertura</label>
                <textarea name="orario_apertura" class="form-control" rows="4" maxlength="300"></textarea>
            </div><br>
            <div class="col-md-4">
                <label class="form-label">Nominativo responsabile</label>
                <input name="nominativo_responsabile" class="form-control" required>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Crea</button>
            </div>
        </form>
    </div>
</body>

</html>