<?php
    include_once ('lib/functions.php'); 
    session_start();
    // Eliminazione di tutte le variabili di sessione salvate.
    unset($_SESSION);
    redirect('index.php');
?>