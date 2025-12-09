<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$rows = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['codice_negozio'])) {
    $codice_negozio = $_POST['codice_negozio'];
    $rows = get_prodotti_by_negozio($codice_negozio);
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Prodotti</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Prodotti</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="home.php">← Negozi</a>
                <form method="post" action="ordine.php" class="d-inline">
                    <input type="hidden" name="codice_negozio" value="<?= htmlspecialchars($codice_negozio) ?>">
                    <button class="btn btn-primary">Nuovo ordine</button>
                </form>
            </div>
        </div>
        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Codice prodotto</th>
                        <th>nome</th>
                        <th>descrizione</th>
                        <th>prezzo</th>
                        <th>quantita</th>
                        <th class="text-end">Azioni</th>
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
                            <td class="text-end">
                                <form method="post" action="update_prezzo_prodotto.php" class="d-inline">
                                    <input type="hidden" name="codice_negozio" value="<?= htmlspecialchars($codice_negozio) ?>">
                                    <input type="hidden" name="codice_prodotto" value="<?= htmlspecialchars($r['codice_prodotto']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        Modifica prezzo
                                    </button>
                                </form>
                        </tr>
                    <?php endforeach;
                    if (!$rows): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nessun prodotto</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>