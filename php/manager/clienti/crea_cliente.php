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
    $codice_fiscale = $_POST['codice_fiscale'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $provincia = $_POST['provincia'];
    $citta = $_POST['citta'];
    $via = $_POST['via'];
    $civico = $_POST['civico'];
    if (
        $codice_fiscale === '' || $email === '' || $password === '' ||
        $nome === '' || $cognome === '' || $provincia === '' ||
        $citta === '' || $via === '' || $civico === ''
    ) {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        if (
            add_cliente(
                $codice_fiscale,
                $email,
                $password,
                $nome,
                $cognome,
                $provincia,
                $citta,
                $via,
                $civico
            )
        ) {
            $ok = "Cliente creato con successo";
        } else {
            $err = "Errore creazione cliente";
        }
    }
}

?>
<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <title>Nuovo cliente</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Nuovo cliente</h1>
            <a class="btn btn-outline-secondary" href="home.php">← Torna a Clienti</a>
        </div>

        <?php if ($ok): ?>
            <div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <form method="post" class="row g-3 card p-3 shadow-sm">
            <div class="col-md-4"><label class="form-label">Codice Fiscale</label><input name="codice_fiscale"
                    class="form-control" maxlength="16" required></div>
            <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email"
                    class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Password</label><input name="password" type="password"
                    class="form-control" maxlength="20" required></div>
            <div class="col-md-4"><label class="form-label">Nome</label><input name="nome" class="form-control"
                    required></div>
            <div class="col-md-4"><label class="form-label">Cognome</label><input name="cognome" class="form-control"
                    required></div>
            <div class="col-md-4"><label class="form-label">Provincia</label><input name="provincia"
                    class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Città</label><input name="citta" class="form-control"
                    required></div>
            <div class="col-md-4"><label class="form-label">Via</label><input name="via" class="form-control" required>
            </div>
            <div class="col-md-1"><label class="form-label">Civico</label><input name="civico" class="form-control"
                    maxlength="3" required></div>

            <div class="col-12">
                <button class="btn btn-success">Crea</button>
            </div>
        </form>
    </div>
</body>

</html>