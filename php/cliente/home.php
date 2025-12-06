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
    <title>Area Cliente</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Area Cliente</h1>
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
                        <a class="btn btn-outline-primary" href="/cliente/password.php">Cambia password</a>
                    </div>
                </div>
            </div>

            <!-- Tessere -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Tessera</h5>
                        <p class="card-text text-muted">Vedi tessere attive e scadute, con saldo punti.</p>
                        <a class="btn btn-outline-primary" href="tessere/home.php">Gestisci tessere</a>
                    </div>
                </div>
            </div>

            <!-- Fatture -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Fatture</h5>
                        <p class="card-text text-muted">Storico dei tuoi acquisti.</p>
                        <a class="btn btn-outline-primary" href="fatture/home.php">Vedi fatture</a>
                    </div>
                </div>
            </div>

            <!-- Prodotti -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Catalogo prodotti</h5>
                        <p class="card-text text-muted">Sfoglia i prodotti e aggiungili al carrello.</p>
                        <a class="btn btn-outline-primary" href="/cliente/prodotti/home.php">Apri</a>
                    </div>
                </div>
            </div>

            <!-- Carrello -->
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Carrello</h5>
                        <p class="card-text text-muted">Rivedi articoli o procedi all’ordine.</p>
                        <a class="btn btn-outline-primary" href="/cliente/carrello/home.php">Apri</a>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-4"><a href="/logout.php">Logout</a></div>
    </div>
</body>

</html>