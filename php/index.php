<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Pagin di login</title>
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
                        <?php
                        if (isset($_SESSION['feedback'])) {
                            if ($_SESSION['feedback'] == false) {
                                ?>
                                <div class="alert alert-danger" role="alert">
                                    Credenziali errate.
                                </div>
                                <?php
                            }
                        }
                        ?>
                        <form id="myform" method="POST" action="lib/login.php">
                            <div class="mb-3">
                                <label for="exampleInputEmail1" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="exampleInputEmail1"
                                    aria-describedby="emailHelp" name='email'>
                            </div>
                            <div class="mb-3">
                                <label for="exampleInputPassword1" class="form-label">Password</label>
                                <input type="password" class="form-control" id="exampleInputPassword1" name="psw">
                            </div>
                            <button type="submit" class="btn btn-primary">Accedi</button>
                        </form>
                    </div>
                </div>
                <!-- <p class="text-center mt-3 text-muted small"></p> -->
            </div>
        </div>
    </div>
</body>

</html>