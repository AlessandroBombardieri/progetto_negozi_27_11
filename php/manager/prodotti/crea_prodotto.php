<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$ok = $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $descrizione = $_POST['descrizione'];
    if (
        $nome === '' || $descrizione === ''
    ) {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (
            add_prodotto(
                $nome,
                $descrizione,
            )
        ) {
            $ok = "Prodotto creato con successo";
        } else {
            $err = "Errore creazione prodotto";
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Nuovo prodotto</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Nuovo prodotto</h1>
            <a class="btn btn-outline-secondary" href="home.php">← Torna a Prodotti</a>
        </div>

        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <form method="post" class="row g-3 card p-3 shadow-sm">
            <div class="col-md-4"><label class="form-label">Nome</label><input name="nome"
                    class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Descrizione</label><input name="descrizione"
                    class="form-control" required></div>
            <div class="col-12">
                <button class="btn btn-success">Crea</button>
            </div>
        </form>
    </div>
</body>

</html>