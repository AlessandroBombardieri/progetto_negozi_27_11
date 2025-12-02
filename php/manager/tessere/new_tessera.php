<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
$prodotti = [];
$codice_negozio_non_dismesso = '';
$codice_fiscale = '';
$negozi_non_dismessi = get_negozi_non_dismessi();
$clienti = get_all_clienti();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $codice_negozio_non_dismesso = $_POST['codice_negozio_non_dismesso'];
    $codice_fiscale = $_POST['$codice_fiscale'];
    if ($codice_negozio_non_dismesso === '' || $codice_fiscale === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (
            add_tessera(
                $codice_negozio_non_dismesso,
                $codice_fiscale
            )
        ) {
            $ok = "Tessera creata con successo";
        } else {
            $err = "Errore creazione tessera";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Nuovo ordine</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Nuova tessera fedeltà</h1>
            <a class="btn btn-outline-secondary" href="home.php">← Torna a Tessere</a>
        </div>

        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <form method="post" class="card p-3 shadow-sm">
            <div class="col-md-7">
                <select name="codice_negozio_non_dismesso" class="form-select" required>
                    <option value="" disabled selected>Seleziona un negozio</option>
                    <?php foreach ($negozi_non_dismessi as $p): ?>
                        <option value="<?= htmlspecialchars($p['codice_negozio']) ?>">
                            <?= htmlspecialchars($p['codice_negozio']) ?> —
                            <?= htmlspecialchars($p['indirizzo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div><br>
            <div class="col-md-7">
                <select name="codice_fiscale" class="form-select" required>
                    <option value="" disabled selected>Seleziona un cliente</option>
                    <?php foreach ($clienti as $p): ?>
                        <option value="<?= htmlspecialchars($p['codice_fiscale']) ?>">
                            <?= htmlspecialchars($p['nome']) ?> —
                            <?= htmlspecialchars($p['cognome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-success">Crea</button>
            </div>
        </form>
    </div>
</body>

</html>