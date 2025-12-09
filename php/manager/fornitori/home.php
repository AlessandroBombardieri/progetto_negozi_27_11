<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$rows = get_all_fornitori();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Clienti</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Fornitori</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home manager</a>
                <a class="btn btn-primary" href="new_fornitore.php">Nuovo fornitore</a>
            </div>
        </div>
        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Partita IVA</th>
                        <th>Indirizzo</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['partita_iva']) ?></td>
                            <td><?= htmlspecialchars($r['indirizzo']) ?></td>
                            <td class="text-end">
                                <form method="post" action="catalogo.php" class="d-inline">
                                    <input type="hidden" name="partita_iva"
                                        value="<?= htmlspecialchars($r['partita_iva']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        Gestisci prodotti
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (!$rows): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nessun fornitore</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>