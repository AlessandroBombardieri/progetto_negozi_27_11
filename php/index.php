<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
session_start();
$login_error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Pagina di login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height:90vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3 text-center">Login</h1>
                        <?php if ($login_error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($login_error) ?>
                            </div>
                        <?php endif; ?>
                        <form id="myform" method="POST" action="lib/login.php" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" maxlength="70" required>
                            </div>
                            <div class="mb-3">
                                <label for="psw" class="form-label">Password</label>
                                <input type="password" class="form-control" id="psw" name="psw" maxlength="20" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Accedi</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>