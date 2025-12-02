<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
$prodotti_disponibili = [];
$partita_iva = '';
if (!empty($_POST['partita_iva'])) {
    $partita_iva = $_POST['partita_iva'];
    $prodotti_disponibili = get_prodotti_fuori_catalogo_by_fornitore($partita_iva);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $partita_iva = $_POST['partita_iva'];
    $codice_prodotto = $_POST['codice_prodotto'];
    $prezzo = $_POST['prezzo'];
    $quantita = $_POST['quantita'];
    if ($partita_iva === '' || $codice_prodotto === '' || $prezzo === '' || $quantita === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (add_prodotto_as_fornitore($partita_iva, $codice_prodotto, $prezzo, $quantita)) {
            $ok = "Prodotto aggiunto con successo";
            $prodotti_disponibili = get_prodotti_fuori_catalogo_by_fornitore($partita_iva);
        } else {
            $err = "Errore aggiunta prodotto";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Nuovo prodotto</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Aggiungi prodotto</h1>
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
            <div class="col-md-7">
                <select name="codice_prodotto" class="form-select" required>
                    <option value="" disabled selected>Seleziona un prodotto</option>
                    <?php foreach ($prodotti_disponibili as $p): ?>
                        <option value="<?= htmlspecialchars($p['codice_prodotto']) ?>">
                            <?= htmlspecialchars($p['codice_prodotto']) ?> — <?= htmlspecialchars($p['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div><br>
            <div class="col-md-2">
                <label class="form-label">Prezzo di vendita (€)</label>
                <input type="number" step="0.01" min="0" name="prezzo" class="form-control" required>
            </div><br>
            <div class="col-md-2">
                <label class="form-label">Quantità disponibile</label>
                <input type="number" step="1" min="0" name="quantita" class="form-control" required>
            </div><br>
            <div class="d-flex gap-2">
                <button class="btn btn-success" name="submit_add" value="1">Aggiungi</button>
            </div>
        </form>
    </div>
</body>

</html>