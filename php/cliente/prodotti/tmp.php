<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
$rows = [];
$negozi = get_negozi_non_dismessi();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $codice_negozio = $_POST['codice_negozio'];
    if ($codice_negozio === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $rows = get_prodotti_by_negozio($codice_negozio);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Catalogo prodotti</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Catalogo prodotti</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home cliente</a>
                <a class="btn btn-outline-primary" href="../carrello/home.php">Carrello</a>
            </div>
        </div>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Prodotti per negozio</h2>
                <form method="post" class="row g-2 mb-3">
                    <div class="col-md-8">
                        <select name="codice_negozio" class="form-select" required>
                            <option value="" disabled selected>Seleziona un negozio</option>
                            <?php foreach ($negozi as $r): ?>
                                <option value="<?= htmlspecialchars($r['codice_negozio']) ?>">
                                    <?= htmlspecialchars($r['codice_negozio']) ?> —
                                    <?= htmlspecialchars($r['indirizzo']) ?>
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
                                <th>Codice prodotto</th>
                                <th>Nome</th>
                                <th>Descrizione</th>
                                <th>Prezzo</th>
                                <th>Quantità disponibile</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['codice_prodotto']) ?></td>
                                    <td><?= htmlspecialchars($r['nome']) ?></td>
                                    <td><?= htmlspecialchars($r['descrizione']) ?></td>
                                    <td><?= htmlspecialchars($r['prezzo']) ?></td>
                                    <td><?= htmlspecialchars($r['quantita']) ?></td>
                                </tr>
                            <?php endforeach;
                            if (!$rows): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nessun prodotto</td>
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