<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$rows = get_all_clienti();
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
            <h1 class="h4 mb-0">Clienti</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home manager</a>
                <a class="btn btn-primary" href="new_cliente.php">Nuovo cliente</a>
            </div>
        </div>

        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>CF</th>
                        <th>Email</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Provincia</th>
                        <th>Città</th>
                        <th>Via</th>
                        <th>Civico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['nome']) ?></td>
                            <td><?= htmlspecialchars($r['cognome']) ?></td>
                            <td><?= htmlspecialchars($r['provincia']) ?></td>
                            <td><?= htmlspecialchars($r['citta']) ?></td>
                            <td><?= htmlspecialchars($r['via']) ?></td>
                            <td><?= htmlspecialchars($r['civico']) ?></td>
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