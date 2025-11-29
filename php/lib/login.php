<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('functions.php');
session_start();
if (isset($_POST)) {
    if (!empty($_POST['email']) && !empty($_POST['psw'])) {
        $email = $_POST['email'];
        $psw = $_POST['psw'];
        $arr = check_login($email, $psw);
        $esito = $arr[0];
        $cf = $arr[1];
        $ruolo = $arr[2];
        if ($esito && $cf !== null) {
            $user = get_utente_by_codice_fiscale($cf);
            if ($user === null) {
                $_SESSION['feedback'] = false;
                redirect('../index.php');
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['utente'] = [
                'email' => $user['email'],
                'codice_fiscale' => $user['codice_fiscale'],
                'ruolo' => $user['ruolo'],
                'nome' => $user['nome'],
                'cognome' => $user['cognome'],
            ];
            $_SESSION['feedback'] = true;
            switch ($ruolo) {
                case 'manager':
                    redirect('../manager/home.php');
                    break;
                /*case 'utente':
                    redirect('../utente/home.php');
                    break;*/
                default:
                    redirect('../index.php');
                    break;
            }
        } else {
            redirect('../index.php');
        }
    } else {
        print ('I campi non possono essere vuoti');
    }
}