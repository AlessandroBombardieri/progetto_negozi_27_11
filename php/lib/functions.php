<?php
/**
 * C
 * Redirect tra le pagine php.
 */
function redirect($url, $permanent = false)
{
    header("Location: $url", true, $permanent ? 301 : 302);
    exit();
}

/**
 * C
 * Debug (temporaneo).
 */
function parseError($error)
{
    $startPos = strpos($error, "ERROR:");
    $endPos1 = strpos($error, "DETAIL"); // end position for "default" errors
    $endPos2 = strpos($error, "CONTEX"); // end position for custom trigger exceptions
    $endPos1 = $endPos1 ? $endPos1 : PHP_INT_MAX;
    $endPos2 = $endPos2 ? $endPos2 : PHP_INT_MAX;
    return substr($error, $startPos + 7, min($endPos1, $endPos2) - $startPos - 8);
}

/**
 * C
 * Apre la connessione con il server db.
 */
function open_pg_connection()
{
    include_once(__DIR__ . '/../conf/conf.php');
    $conn = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS;
    return pg_connect($conn);
}

/**
 * C
 * Chiude la connessione con il server db.
 */
function close_pg_connection($db)
{
    return pg_close($db);
}

/**
 * M
 * Verifica il login tramite email e password.
 * 
 * Restituisce un flag true, il codice fiscale dell'utente ed il suo ruolo se il login ha avuto esito positivo, altrimenti false.
 */
function check_login($usr, $psw)
{
    $db = open_pg_connection();
    $params = array($usr, $psw);
    $sql = "SELECT * FROM check_login($1, $2)";
    $result = pg_prepare($db, 'login', $sql);
    $result = pg_execute($db, 'login', $params);
    $row = pg_fetch_assoc($result);
    close_pg_connection($db);
    if ($row) {
        $cf = $row['_codice_fiscale'];
        $ruolo = $row['_ruolo'];
        return array(true, $cf, $ruolo);
    } else {
        return array(false, null, null);
    }
}

/**
 * M
 * Restituisce le credenziali dell'utente dato il codice fiscale.
 */
function get_utente_by_codice_fiscale($cf)
{
    $db = open_pg_connection();
    $params = array($cf);
    $sql = "SELECT * FROM get_utente_by_codice_fiscale($1);";
    $result = pg_prepare($db, 'credenziali', $sql);
    $result = pg_execute($db, 'credenziali', $params);
    close_pg_connection($db);
    return pg_fetch_assoc($result);
}

/**
 * M
 * Cambia la password dell'utente dato il codice fiscale, la vecchia password e la nuova password.
 */
function change_password($cf, $oldpw, $newpw)
{
    $db = open_pg_connection();
    $params = array($cf, $oldpw, $newpw);
    $sql = "CALL change_password($1, $2, $3);";
    $result = pg_prepare($db, 'change_pw', $sql);
    $result = @pg_execute($db, 'change_pw', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i clienti.
 */
function get_all_clienti(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_all_clienti();";
    $result = pg_prepare($db, 'list_clienti', $sql);
    $result = pg_execute($db, 'list_clienti', array());
    $clienti = [];
    while ($row = pg_fetch_assoc($result)) {
        $clienti[] = $row;
    }
    close_pg_connection($db);
    return $clienti;
}