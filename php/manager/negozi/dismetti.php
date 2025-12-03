<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
$codice_negozio = '';
$negozi = [];
$nuovo_codice_negozio = '';
if (!empty($_POST['codice_negozio'])) {
    $codice_negozio = $_POST['codice_negozio'];
    $negozi = get_negozi_non_dismessi();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $nuovo_codice_negozio = $_POST['nuovo_codice_negozio'] ?? '';
    if (!$err) {
        if (dismetti_negozio($codice_negozio, $nuovo_codice_negozio ?: NULL)) {
            $ok = "Negozio dismesso con successo";
            $negozi = get_negozi_non_dismessi();
        } else {
            $err = "Errore dismissione negozio";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Dismetti negozio</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Dismetti negozio</h1>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="btn btn-outline-secondary" href="home.php">← Torna a Negozi</a>
            </div>
        </div>

        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <form method="post" class="card p-3 shadow-sm">
            <input type="hidden" name="codice_negozio" value="<?= htmlspecialchars($codice_negozio) ?>">

            <div class="col-md-7">
                <label class="form-label">Trasferisci prodotti</label>
                <select name="nuovo_codice_negozio" class="form-select">
                    <option value="" selected>Seleziona un negozio (opzionale)</option>
                    <?php foreach ($negozi as $n): ?>
                        <?php if ($n['codice_negozio'] !== $codice_negozio): ?>
                            <option value="<?= htmlspecialchars($n['codice_negozio']) ?>">
                                <?= htmlspecialchars($n['codice_negozio']) ?> — <?= htmlspecialchars($n['indirizzo']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Se non selezioni alcun negozio, i prodotti in vendita verranno definitivamente
                    rimossi dal negozio dismesso.</div>
            </div><br>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-danger" name="submit_add" value="1">
                    Dismetti
                </button>
            </div>
        </form>
    </div>
</body>

</html>