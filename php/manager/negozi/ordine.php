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
$codice_negozio = '';
if (!empty($_POST['codice_negozio'])) {
    $codice_negozio = $_POST['codice_negozio'];
    $prodotti = get_all_prodotti();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $codice_prodotto = $_POST['codice_prodotto'];
    $quantita = $_POST['quantita'];
    $codice_negozio = $_POST['codice_negozio'];
    if ($codice_prodotto === '' || $quantita === '' || $codice_negozio === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $esito = ordina_prodotto_as_negozio($codice_prodotto, $quantita, $codice_negozio);
        if ($esito['ok']) {
            $ok = "Prodotto ordinato con successo, numero ordine: " . htmlspecialchars($esito['numero_ordine']);
            $prodotti = get_all_prodotti();
        } else {
            $err = $esito['msg'];
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
            <h1 class="h4 mb-0">Effettua ordine</h1>
            <form method="post" action="catalogo.php" class="d-inline">
                <input type="hidden" name="codice_negozio" value="<?= htmlspecialchars($codice_negozio) ?>">
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
            <input type="hidden" name="codice_negozio" value="<?= htmlspecialchars($codice_negozio) ?>">
            <div class="col-md-7">
                <label class="form-label">Prodotto</label>
                <select name="codice_prodotto" class="form-select" required>
                    <option value="" disabled selected>Seleziona un prodotto</option>
                    <?php foreach ($prodotti as $p): ?>
                        <option value="<?= htmlspecialchars($p['codice_prodotto']) ?>">
                            <?= htmlspecialchars($p['codice_prodotto']) ?> — <?= htmlspecialchars($p['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div><br>
            <div class="col-md-2">
                <label class="form-label">Quantità</label>
                <input type="number" step="1" min="0" name="quantita" class="form-control" required>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-success" name="submit_add" value="1">Aggiungi</button>
            </div>
        </form>
    </div>
</body>

</html>