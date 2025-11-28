<?php
    ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('functions.php');
    session_start();
    if (isset($_POST)){
        if (!empty($_POST['email']) && !empty($_POST['psw'])) {
            $email = $_POST['email'];
            $psw = $_POST['psw'];
            $arr = check_login($email, $psw);
            $esito = $arr[0];
            $cf = $arr[1]['id'];
            $ruolo = $arr[2]['ruolo'];
            if (!is_null($cf)){
                $_SESSION['id'] = $cf;
            }
            $_SESSION['feedback'] = $esito;
            if ($esito) {
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
            }else{
                redirect('../index.php');
            }
        }else{
            print('I campi non possono essere vuoti');
        }
    }