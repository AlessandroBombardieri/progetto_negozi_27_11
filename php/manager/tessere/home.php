<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$err = null;
$rows = [];
$rows_ = [];
$negozi_non_dismessi = get_negozi_non_dismessi();
$negozi_dismessi = get_negozi_dismessi();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $codice_negozio_non_dismesso = $_POST['codice_negozio_non_dismesso'];
    if ($codice_negozio_non_dismesso === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $rows = get_tesserati_by_negozio($codice_negozio_non_dismesso);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add_'])) {
    $codice_negozio_dismesso = $_POST['codice_negozio_dismesso'];
    if ($codice_negozio_dismesso === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $rows_ = get_tesserati_by_negozio($codice_negozio_dismesso);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Tessere fedeltà</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Tessere fedeltà</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home manager</a>
                <a class="btn btn-success" href="new_tessera.php">Nuova tessera fedeltà</a>
            </div>
        </div>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Tesserati per negozio</h2>
                <form method="post" class="row g-2 mb-3">
                    <div class="col-md-8">
                        <select name="codice_negozio_non_dismesso" class="form-select" required>
                            <option value="" disabled selected>Seleziona un negozio</option>
                            <?php foreach ($negozi_non_dismessi as $p): ?>
                                <option value="<?= htmlspecialchars($p['codice_negozio']) ?>">
                                    <?= htmlspecialchars($p['codice_negozio']) ?> —
                                    <?= htmlspecialchars($p['indirizzo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" name="submit_add" value="1">Mostra</button>
                    </div>
                </form>
                <div class="table-responsive bg-white shadow-sm rounded">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>CF</th>
                                <th>Email</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Saldo punti</th>
                                <th>Data di rilascio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
                                    <td><?= htmlspecialchars($r['email']) ?></td>
                                    <td><?= htmlspecialchars($r['nome']) ?></td>
                                    <td><?= htmlspecialchars($r['cognome']) ?></td>
                                    <td><?= htmlspecialchars($r['saldo_punti']) ?></td>
                                    <td><?= htmlspecialchars($r['data_rilascio']) ?></td>
                                </tr>
                            <?php endforeach;
                            if (!$rows): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nessun cliente</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Storico tesserati negozi dismessi</h2>
                <form method="post" class="row g-2 mb-3">
                    <div class="col-md-8">
                        <select name="codice_negozio_dismesso" class="form-select" required>
                            <option value="" disabled selected>Seleziona un negozio</option>
                            <?php foreach ($negozi_dismessi as $p): ?>
                                <option value="<?= htmlspecialchars($p['codice_negozio']) ?>">
                                    <?= htmlspecialchars($p['codice_negozio']) ?> —
                                    <?= htmlspecialchars($p['indirizzo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" name="submit_add_" value="1">Mostra</button>
                    </div>
                </form>

                <div class="table-responsive bg-white shadow-sm rounded">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>CF</th>
                                <th>Email</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Saldo punti</th>
                                <th>Data di rilascio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows_ as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
                                    <td><?= htmlspecialchars($r['email']) ?></td>
                                    <td><?= htmlspecialchars($r['nome']) ?></td>
                                    <td><?= htmlspecialchars($r['cognome']) ?></td>
                                    <td><?= htmlspecialchars($r['saldo_punti']) ?></td>
                                    <td><?= htmlspecialchars($r['data_rilascio']) ?></td>
                                </tr>
                            <?php endforeach;
                            if (!$rows_): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nessun cliente</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>