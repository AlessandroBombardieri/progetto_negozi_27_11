<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
$partita_iva = '';
$codice_prodotto = '';
if (!empty($_POST['partita_iva'] && !empty($_POST['codice_prodotto']))) {
    $partita_iva = $_POST['partita_iva'];
    $codice_prodotto = $_POST['codice_prodotto'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $prezzo = $_POST['prezzo'];
    if ($prezzo === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (update_prezzo_prodotto_as_fornitore($partita_iva, $codice_prodotto, $prezzo)) {
            $ok = "Prezzo prodotto modificato con successo";
        } else {
            $err = "Errore modifica prezzo prodotto";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Modifica prezzo prodotto</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Modifica prezzo prodotto</h1>
            <form method="post" action="catalogo.php" class="d-inline">
                <input type="hidden" name="partita_iva" value="<?= htmlspecialchars($partita_iva) ?>">
                <button type="submit" class="btn btn-outline-secondary">
                    ← Torna a catalogo
                </button>
            </form>
        </div>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
        <form method="post" class="card p-3 shadow-sm">
            <input type="hidden" name="partita_iva" value="<?= htmlspecialchars($partita_iva) ?>">
            <input type="hidden" name="codice_prodotto" value="<?= htmlspecialchars($codice_prodotto) ?>">
            <div class="col-md-1">
                <label class="form-label">Prezzo (€)</label>
                <input type="number" step="0.01" min="0" name="prezzo" class="form-control" required>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" name="submit_add" value="1">Aggiorna</button>
            </div>
        </form>
    </div>
</body>

</html>