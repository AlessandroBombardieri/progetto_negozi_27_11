<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$rows = get_all_negozi();
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
            <h1 class="h4 mb-0">Negozi</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home manager</a>
                <a class="btn btn-success" href="new_negozio.php">Nuovo negozio</a>
            </div>
        </div>

        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Codice negozio</th>
                        <th>indirizzo</th>
                        <th>Orario di apertura</th>
                        <th>Nominativo responsabile</th>
                        <th>Cessata attività</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['codice_negozio']) ?></td>
                            <td><?= htmlspecialchars($r['indirizzo']) ?></td>
                            <td><?= htmlspecialchars($r['orario_apertura']) ?></td>
                            <td><?= htmlspecialchars($r['nominativo_responsabile']) ?></td>
                            <td><?= htmlspecialchars($r['dismesso']) ?></td>
                            <td class="text-end">
                                <form method="post" action="catalogo.php" class="d-inline">
                                    <input type="hidden" name="codice_negozio"
                                        value="<?= htmlspecialchars($r['codice_negozio']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        Gestisci prodotti
                                    </button>
                                </form>
                                <form method="post" action="dismetti.php" class="d-inline">
                                    <input type="hidden" name="codice_negozio"
                                        value="<?= htmlspecialchars($r['codice_negozio']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Dismetti negozio
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (!$rows): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nessun negozio</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>