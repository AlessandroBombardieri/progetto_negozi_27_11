<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('functions.php');
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $psw = $_POST['psw'];
    if ($email === '' || $psw === '') {
        $_SESSION['login_error'] = 'I campi non possono essere vuoti.';
        redirect('../index.php');
    }
    $arr = check_login($email, $psw);
    $esito = $arr[0] ?? false;
    $cf = $arr[1];
    $ruolo = $arr[2];
    if ($esito && $cf !== null) {
        $user = get_utente_by_codice_fiscale($cf);
        if ($user === null) {
            $_SESSION['login_error'] = 'Credenziali errate.';
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
        unset($_SESSION['login_error']);
        switch ($ruolo) {
            case 'manager':
                redirect('../manager/home.php');
                break;
            default:
                redirect('../cliente/home.php');
                break;
        }
    } else {
        $_SESSION['login_error'] = 'Credenziali errate.';
        redirect('../index.php');
    }
}