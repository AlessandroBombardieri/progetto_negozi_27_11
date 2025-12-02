<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$negozi_non_dismessi = [];
$negozi_non_dismessi = get_negozi_non_dismessi();
$err = null;
$rows = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $codice_negozio = $_POST['codice_negozio'];
    if ($codice_negozio === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $rows = get_tesserati_by_negozio($codice_negozio);
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
                <a class="btn btn-success" href="new_fornitore.php">Nuova tessera fedeltà</a>
            </div>
        </div>

        <form method="post" class="card p-3 shadow-sm">
            <div class="col-md-7">
                <label class="form-label">Negozio</label>
                <select name="codice_negozio" class="form-select" required>
                    <option value="" disabled selected>Seleziona un negozio</option>
                    <?php foreach ($negozi_non_dismessi as $p): ?>
                        <option value="<?= htmlspecialchars($p['codice_negozio']) ?>">
                            <?= htmlspecialchars($p['codice_negozio']) ?> — <?= htmlspecialchars($p['indirizzo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-success" name="submit_add" value="1">Mostra</button>
            </div><br>
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
                            <td colspan="5" class="text-center text-muted py-4">Nessun cliente</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>