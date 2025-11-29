<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../lib/functions.php');
session_start();
if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}
$u = $_SESSION['utente'];
$msg = $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pw = $_POST['old_pw'];
    $new_pw = $_POST['new_pw'];
    $rep_pw = $_POST['rep_pw'];
    if ($old_pw === '' || $new_pw === '' || $rep_pw === '') {
        $err = "Compila tutti i campi";
    }
    if (!$err && $new_pw !== $rep_pw) {
        $err = "Le nuove password non coincidono";
    }
    if (!$err && strlen($new_pw) > 20) {
        $err = "La nuova password non può superare 20 caratteri di lunghezza";
    }
    if (!$err) {
        $cf = $u['codice_fiscale'];
        if (change_password($cf, $old_pw, $new_pw)) {
            $msg = "Password aggiornata con successo";
        } else {
            $err = "Password inserita non corretta";
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cambia password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Cambia password</h1>
            <a class="btn btn-outline-secondary" href="home.php">← Home manager</a>
        </div>
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <form method="post" class="card p-3 shadow-sm">
            <div class="mb-3">
                <label class="form-label">Password attuale</label>
                <input type="password" name="old_pw" class="form-control" maxlength="20" required
                    autocomplete="current-password">
            </div>
            <div class="mb-3">
                <label class="form-label">Nuova password</label>
                <input type="password" name="new_pw" class="form-control" maxlength="20" required
                    autocomplete="new-password">
                <div class="form-text">Max 20 caratteri.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Ripeti nuova password</label>
                <input type="password" name="rep_pw" class="form-control" maxlength="20" required
                    autocomplete="new-password">
            </div>
            <button class="btn btn-primary">Aggiorna</button>
        </form>
    </div>
</body>

</html>