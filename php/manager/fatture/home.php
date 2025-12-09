<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$codice_fiscale = $_SESSION['utente']['codice_fiscale'];
$rows = get_all_fatture();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Fatture</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Fatture</h1>
            <div>
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home manager</a>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Storico fatture</h2>
                <div class="table-responsive bg-white shadow-sm rounded">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Codice fattura</th>
                                <th>Codice fiscale</th>
                                <th>Codice negozio</th>
                                <th>Codice prodotto</th>
                                <th>Data acquisto</th>
                                <th>Totale</th>
                                <th>Totale pagato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['codice_fattura']) ?></td>
                                    <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
                                    <td><?= htmlspecialchars($r['codice_negozio']) ?></td>
                                    <td><?= htmlspecialchars($r['codice_prodotto']) ?></td>
                                    <td><?= htmlspecialchars($r['data_acquisto']) ?></td>
                                    <td><?= htmlspecialchars($r['totale']) ?></td>
                                    <td><?= htmlspecialchars($r['totale_pagato']) ?></td>
                                </tr>
                            <?php endforeach;
                            if (!$rows): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nessuna fattura</td>
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