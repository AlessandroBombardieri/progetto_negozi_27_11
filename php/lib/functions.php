<?php
/**
 * Redirect tra le pagine php.
 */
function redirect($url, $permanent = false) {
    header("Location: $url", true, $permanent ? 301 : 302);
    exit();
}

/**
 * Debug (temporaneo).
 */
function parseError($error) {
    $startPos = strpos($error, "ERROR:");
    $endPos1 = strpos($error, "DETAIL"); // end position for "default" errors
    $endPos2 = strpos($error, "CONTEX"); // end position for custom trigger exceptions
    $endPos1 = $endPos1 ? $endPos1 : PHP_INT_MAX;
    $endPos2 = $endPos2 ? $endPos2 : PHP_INT_MAX;
    return substr($error, $startPos + 7, min($endPos1, $endPos2) - $startPos - 8);
}

/**
 * Apre la connessione con il server db.
 */
function open_pg_connection(){
    include_once('../conf/conf.php');
    $conn = "host=".DB_HOST." dbname=".DB_NAME." user=".DB_USER." password=".DB_PASS;
    return pg_connect($conn);
}

/**
 * Chiude la connessione con il server db.
 */
function close_pg_connection($db){
    return pg_close($db);
}

/**
 * Controlla il login (email e password).
 * 
 * Restituisce un flag true ed il codice fiscale dell'utente se il login è andato correttamente, altrimenti false.
 */
function check_login($usr, $psw){
    $db = open_pg_connection();
    $params = array($usr, $psw);
    $sql = "SELECT * FROM check_login($1, $2)";
    $result = pg_prepare($db, 'login', $sql);
    $result = pg_execute($db, 'login', $params);
    close_pg_connection($db);
    $row = pg_fetch_assoc($result);
    $cf = $row['_codice_fiscale'];
    $ruolo = $row($result)['_ruolo'];
    if ($cf && $ruolo) return array(true, $cf, $ruolo);
    else return array(false, null, null);
    /*if ($id = pg_fetch_assoc($result)) return array(true, $id); // trovato il record
    else return array(false, null);*/
}
