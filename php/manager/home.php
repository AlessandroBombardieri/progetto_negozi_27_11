<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../index.php');
}
$u = $_SESSION['utente'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Area Manager</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Area Manager</h1>
            <div class="text-muted small">
                <?php
                echo htmlspecialchars($u['nome']) . ' ' .
                    htmlspecialchars($u['cognome']) . ' — ' .
                    htmlspecialchars($u['email']);
                ?>
            </div>
        </div>

        <div class="row g-3">
            <!-- Account -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Account</h5>
                        <p class="card-text text-muted">Gestisci la tua password.</p>
                        <a class="btn btn-outline-primary" href="password.php">Cambia password</a>
                    </div>
                </div>
            </div>

            <!-- Clienti -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Clienti</h5>
                        <p class="card-text text-muted">Visualizza e crea utenze.</p>
                        <a class="btn btn-outline-primary" href="clienti/home.php">Gestisci utenti</a>
                    </div>
                </div>
            </div>

            <!-- Tessere -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Tessere</h5>
                        <p class="card-text text-muted">Associa tessere a clienti/negozi.</p>
                        <a class="btn btn-outline-primary" href="tessere/home.php">Gestisci tessere</a>
                    </div>
                </div>
            </div>

            <!-- Negozi -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Negozi</h5>
                        <p class="card-text text-muted">Inserisci, chiudi e gestisci i negozi della catena.</p>
                        <div class="d-grid gap-2 d-md-block">
                            <a class="btn btn-outline-primary me-1 mb-1" href="negozi/home.php">Gestisci negozi</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fornitori -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Fornitori</h5>
                        <p class="card-text text-muted">Elenco e cataloghi fornitori.</p>
                        <a class="btn btn-outline-primary" href="fornitori/home.php">Gestisci fornitori</a>
                    </div>
                </div>
            </div>

            <!-- Prodotti -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Prodotti</h5>
                        <p class="card-text text-muted">Elenco globale dei prodotti.</p>
                        <a class="btn btn-outline-primary" href="prodotti/home.php">Gestisci prodotti</a>
                    </div>
                </div>
            </div>

            <!-- Ordini -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Storico ordini</h5>
                        <p class="card-text text-muted">Storico ordini effettuati presso fornitori.</p>
                        <a class="btn btn-outline-primary mb-1" href="ordini/home.php">Visualizza storico ordini</a>
                    </div>
                </div>
            </div>

            <!-- Fatture -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Storico fatture</h5>
                        <p class="card-text text-muted">Storici per cliente/prodotto/negozio.</p>
                        <a class="btn btn-outline-primary" href="fatture/index.php">Visualizza storico fatture</a>
                    </div>
                </div>
            </div>

        <div class="mt-4"><a href="../logout.php">Logout</a></div>
    </div>
</body>

</html>