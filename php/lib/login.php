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
            if (!is_null($cf)){
                $_SESSION['id'] = $cf;
            }
            $_SESSION['feedback'] = $esito;

            redirect('../cliente/home.php');
            /*if ($esito) {
                switch ($tipo) {
                    case 'studente':
                        redirect('../studente/home.php');
                        break;
                    case 'docente':
                        redirect('../docente/home.php');
                        break;
                    case 'segretario':
                        redirect('../segreteria/home.php');
                        break;
                    case 'ex_studente':
                        redirect('../ex_studente/home.php');
                        break;
                    default:
                        redirect('../index.php');
                        break;
                }
            }else{
                redirect('../index.php');
            }*/
        }else{
            print('I campi non possono essere vuoti');
        }
    }