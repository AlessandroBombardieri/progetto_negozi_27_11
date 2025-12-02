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
$fornitori = get_all_fornitori();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    $partita_iva = $_POST['partita_iva'];
    if ($partita_iva === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $rows = get_storico_ordini_by_fornitore($partita_iva);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Storico ordini</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Storico ordini</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home manager</a>
            </div>
        </div>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Storico ordini per fornitore</h2>
                <form method="post" class="row g-2 mb-3">
                    <div class="col-md-8">
                        <select name="partita_iva" class="form-select" required>
                            <option value="" disabled selected>Seleziona un fornitore</option>
                            <?php foreach ($fornitori as $p): ?>
                                <option value="<?= htmlspecialchars($p['partita_iva']) ?>">
                                    <?= htmlspecialchars($p['partita_iva']) ?> —
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
                                <th>Numero ordine</th>
                                <th>Codice prodotto</th>
                                <th>Codice negozio</th>
                                <th>Quantità ordinata</th>
                                <th>Data ordine</th>
                                <th>Data consegna</th>
                                <th>Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['numero_ordine']) ?></td>
                                    <td><?= htmlspecialchars($r['codice_prodotto']) ?></td>
                                    <td><?= htmlspecialchars($r['codice_negozio']) ?></td>
                                    <td><?= htmlspecialchars($r['quantita_ordinata']) ?></td>
                                    <td><?= htmlspecialchars($r['data_ordine']) ?></td>
                                    <td><?= htmlspecialchars($r['data_consegna']) ?></td>
                                    <td><?= htmlspecialchars($r['totale']) ?></td>
                                </tr>
                            <?php endforeach;
                            if (!$rows): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nessun ordine</td>
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