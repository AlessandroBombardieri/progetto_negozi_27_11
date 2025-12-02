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

/**
 * M
 * Crea un nuovo cliente.
 */
function add_cliente($cf, $email, $password, $nome, $cognome, $provincia, $citta, $indirizzo, $civico)
{
    $db = open_pg_connection();
    $params = array($cf, $email, $password, $nome, $cognome, $provincia, $citta, $indirizzo, $civico);
    $sql = "CALL add_cliente($1, $2, $3, $4, $5, $6, $7, $8, $9);";
    $result = pg_prepare($db, 'add_cliente', $sql);
    $result = @pg_execute($db, 'add_cliente', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i prodotti.
 */
function get_all_prodotti(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_all_prodotti();";
    $result = pg_prepare($db, 'list_prodotti', $sql);
    $result = pg_execute($db, 'list_prodotti', array());
    $prodotti = [];
    while ($row = pg_fetch_assoc($result)) {
        $prodotti[] = $row;
    }
    close_pg_connection($db);
    return $prodotti;
}

/**
 * M
 * Crea un nuovo prodotto.
 */
function add_prodotto($nome, $descrizione)
{
    $db = open_pg_connection();
    $params = array($nome, $descrizione);
    $sql = "CALL add_prodotto($1, $2);";
    $result = pg_prepare($db, 'add_prodotto', $sql);
    $result = @pg_execute($db, 'add_prodotto', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i fornitori.
 */
function get_all_fornitori(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_all_fornitori();";
    $result = pg_prepare($db, 'list_fornitori', $sql);
    $result = pg_execute($db, 'list_fornitori', array());
    $fornitori = [];
    while ($row = pg_fetch_assoc($result)) {
        $fornitori[] = $row;
    }
    close_pg_connection($db);
    return $fornitori;
}

/**
 * M
 * Crea un nuovo fornitore.
 */
function add_fornitore($partita_iva, $indirizzo)
{
    $db = open_pg_connection();
    $params = array($partita_iva, $indirizzo);
    $sql = "CALL add_fornitore($1, $2);";
    $result = pg_prepare($db, 'add_fornitore', $sql);
    $result = @pg_execute($db, 'add_fornitore', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i prodotti a catalogo presso un dato fornitore.
 */
function get_prodotti_by_fornitore($partita_iva)
{
    $db = open_pg_connection();
    $params = array($partita_iva);
    $sql = "SELECT * FROM get_prodotti_by_fornitore($1);";
    $result = pg_prepare($db, 'prodotti', $sql);
    $result = pg_execute($db, 'prodotti', $params);
    $prodotti = [];
    while ($row = pg_fetch_assoc($result)) {
        $prodotti[] = $row;
    }
    close_pg_connection($db);
    return $prodotti;
}

/**
 * M
 * Restituisce tutti i prodotti non ancora in catalogo per un dato fornitore.
 */
function get_prodotti_fuori_catalogo_by_fornitore(string $partita_iva): array
{
    $db = open_pg_connection();
    $params = array($partita_iva);
    $sql = "SELECT * FROM get_prodotti_fuori_catalogo_by_fornitore($1);";
    $result = pg_prepare($db, 'prodotti_fuori_catalogo', $sql);
    $result = pg_execute($db, 'prodotti_fuori_catalogo', $params);
    $prodotti = [];
    while ($row = pg_fetch_assoc($result)) {
        $prodotti[] = $row;
    }
    close_pg_connection($db);
    return $prodotti;
}

/**
 * M
 * Aggiungi un prodotto all'inventario di un dato fornitore.
 */
function add_prodotto_as_fornitore($partita_iva, $codice_prodotto, $prezzo, $quantita)
{
    $db = open_pg_connection();
    $params = array($partita_iva, $codice_prodotto, $prezzo, $quantita);
    $sql = "CALL add_prodotto_as_fornitore($1, $2, $3, $4);";
    $result = pg_prepare($db, 'add_prodotto_as_fornitore', $sql);
    $result = @pg_execute($db, 'add_prodotto_as_fornitore', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Incrementa le scorte di un prodotto in vendita presso un dato fornitore.
 */
function update_quantita_prodotto_as_fornitore($partita_iva, $codice_prodotto, $quantita)
{
    $db = open_pg_connection();
    $params = array($partita_iva, $codice_prodotto, $quantita);
    $sql = "CALL update_quantita_prodotto_as_fornitore($1, $2, $3);";
    $result = pg_prepare($db, 'update_quantita_prodotto_as_fornitore', $sql);
    $result = @pg_execute($db, 'update_quantita_prodotto_as_fornitore', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Modifica il prezzo di un prodotto in vendita presso un dato fornitore.
 */
function update_prezzo_prodotto_as_fornitore($partita_iva, $codice_prodotto, $prezzo)
{
    $db = open_pg_connection();
    $params = array($partita_iva, $codice_prodotto, $prezzo);
    $sql = "CALL update_prezzo_prodotto_as_fornitore($1, $2, $3);";
    $result = pg_prepare($db, 'update_prezzo_prodotto_as_fornitore', $sql);
    $result = @pg_execute($db, 'update_prezzo_prodotto_as_fornitore', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i negozi.
 */
function get_all_negozi(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_all_negozi();";
    $result = pg_prepare($db, 'get_all_negozi', $sql);
    $result = pg_execute($db, 'get_all_negozi', array());
    $negozi = [];
    while ($row = pg_fetch_assoc($result)) {
        $negozi[] = $row;
    }
    close_pg_connection($db);
    return $negozi;
}

/**
 * M
 * Crea un nuovo negozio.
 */
function add_negozio($indirizzo, $orario_apertura, $responsabile)
{
    $db = open_pg_connection();
    $params = array($indirizzo, $orario_apertura, $responsabile);
    $sql = "CALL add_negozio($1, $2, $3);";
    $result = pg_prepare($db, 'add_negozio', $sql);
    $result = @pg_execute($db, 'add_negozio', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i prodotti in vendita presso un dato negozio.
 */
function get_prodotti_by_negozio(string $codice_negozio): array
{
    $db = open_pg_connection();
    $params = array($codice_negozio);
    $sql = "SELECT * FROM get_prodotti_by_negozio($1);";
    $result = pg_prepare($db, 'get_prodotti_by_negozio', $sql);
    $result = pg_execute($db, 'get_prodotti_by_negozio', $params);
    $prodotti = [];
    while ($row = pg_fetch_assoc($result)) {
        $prodotti[] = $row;
    }
    close_pg_connection($db);
    return $prodotti;
}

/**
 * M
 * Effettua l'ordine di un prodotto scelto presso un determinato fornitore scelto automaticamente in funzione del costo.
 */
function ordina_prodotto_as_negozio($codice_prodotto, $quantita, $codice_negozio)
{
    $db = open_pg_connection();
    $params = array($codice_prodotto, $quantita, $codice_negozio);
    $sql = "SELECT * FROM ordina_prodotto_as_negozio($1, $2, $3) AS numero_ordine;";
    $result = pg_prepare($db, 'ordina_prodotto_as_negozio', $sql);
    $result = @pg_execute($db, 'ordina_prodotto_as_negozio', $params);
    if ($result === false) {
        close_pg_connection($db);
        return ['ok' => false, 'msg' => 'Nessun fornitore ha scorte sufficienti per questa quantità.'];
    }
    $row = pg_fetch_assoc($result);
    close_pg_connection($db);
    return ['ok' => true, 'numero_ordine' => $row['numero_ordine']];
}

/**
 * M
 * Modifica il prezzo di un prodotto in vendita presso un dato negozio.
 */
function update_prezzo_prodotto_as_negozio($codice_negozio, $codice_prodotto, $nuovo_prezzo)
{
    $db = open_pg_connection();
    $params = array($codice_negozio, $codice_prodotto, $nuovo_prezzo);
    $sql = "CALL update_prezzo_prodotto_as_negozio($1, $2, $3);";
    $result = pg_prepare($db, 'update_prezzo_prodotto_as_negozio', $sql);
    $result = @pg_execute($db, 'update_prezzo_prodotto_as_negozio', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce tutti i negozi non dismessi.
 */
function get_negozi_non_dismessi(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_negozi_non_dismessi();";
    $result = pg_prepare($db, 'get_negozi_non_dismessi', $sql);
    $result = pg_execute($db, 'get_negozi_non_dismessi', array());
    $negozi = [];
    while ($row = pg_fetch_assoc($result)) {
        $negozi[] = $row;
    }
    close_pg_connection($db);
    return $negozi;
}

/**
 * M
 * Restituisce tutti i negozi dismessi.
 */
function get_negozi_dismessi(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_negozi_dismessi();";
    $result = pg_prepare($db, 'get_negozi_dismessi', $sql);
    $result = pg_execute($db, 'get_negozi_dismessi', array());
    $negozi = [];
    while ($row = pg_fetch_assoc($result)) {
        $negozi[] = $row;
    }
    close_pg_connection($db);
    return $negozi;
}

/**
 * M
 * Restituisce i dati di utenti e tessere associate ad un dato negozo.
 */
function get_tesserati_by_negozio($codice_negozio): array
{
    $db = open_pg_connection();
    $params = array($codice_negozio);
    $sql = "SELECT * FROM get_tesserati_by_negozio($1);";
    $result = pg_prepare($db, 'get_tesserati_by_negozio', $sql);
    $result = pg_execute($db, 'get_tesserati_by_negozio', $params);
    if ($result === false) {
        close_pg_connection($db);
        return [];
    }
    $tessere = [];
    while ($row = pg_fetch_assoc($result)) {
        $tessere[] = $row;
    }
    close_pg_connection($db);
    return $tessere;
}

/**
 * M
 * Restituisce i dati di utenti e tessere associate ad un dato negozo dismesso.
 */
function get_tesserati_by_negozio_dismesso($codice_negozio): array
{
    $db = open_pg_connection();
    $params = array($codice_negozio);
    $sql = "SELECT * FROM get_tesserati_by_negozio_dismesso($1);";
    $result = pg_prepare($db, 'get_tesserati_by_negozio_dismesso', $sql);
    $result = pg_execute($db, 'get_tesserati_by_negozio_dismesso', $params);
    if ($result === false) {
        close_pg_connection($db);
        return [];
    }
    $tessere = [];
    while ($row = pg_fetch_assoc($result)) {
        $tessere[] = $row;
    }
    close_pg_connection($db);
    return $tessere;
}

/**
 * M
 * Crea una nuova tessera fedeltà.
 */
function add_tessera($codice_negozio, $codice_fiscale)
{
    $db = open_pg_connection();
    $params = array($codice_negozio, $codice_fiscale);
    $sql = "CALL add_tessera($1, $2, CURRENT_DATE);";
    $result = pg_prepare($db, 'add_tessera', $sql);
    $result = pg_execute($db, 'add_tessera', $params);
    close_pg_connection($db);
    return $result;
}

/**
 * M
 * Restituisce i dati di tessere associate a clienti premium, ovvero con un saldo punti superiore a 300 punti.
 */
function get_tesserati_premium(): array
{
    $db = open_pg_connection();
    $sql = "SELECT * FROM view_clienti_almeno_300_punti;";
    $result = pg_prepare($db, 'get_clienti_premium', $sql);
    $result = pg_execute($db, 'get_clienti_premium', array());
    if ($result === false) {
        close_pg_connection($db);
        return [];
    }
    $tessere = [];
    while ($row = pg_fetch_assoc($result)) {
        $tessere[] = $row;
    }
    close_pg_connection($db);
    return $tessere;
}

/**
 * M
 * Restituisce i dati degli ordini effettuati presso un dato fornitore.
 */
function get_storico_ordini_by_fornitore($partita_iva): array
{
    $db = open_pg_connection();
    $params = array($partita_iva);
    $sql = "SELECT * FROM get_storico_ordini_by_fornitore($1);";
    $result = pg_prepare($db, 'get_storico_ordini_by_fornitore', $sql);
    $result = pg_execute($db, 'get_storico_ordini_by_fornitore', $params);
    if ($result === false) {
        close_pg_connection($db);
        return [];
    }
    $ordini = [];
    while ($row = pg_fetch_assoc($result)) {
        $ordini[] = $row;
    }
    close_pg_connection($db);
    return $ordini;
}

/**
 * M
 * Modifica il prezzo di un prodotto in vendita presso un dato negozio.
 */
function update_data_consegna_ordine($numero_ordine, $data_consegna)
{
    $db = open_pg_connection();
    $params = array($numero_ordine, $data_consegna);
    $sql = "CALL update_data_consegna_ordine($1, $2);";
    $result = pg_prepare($db, 'update_data_consegna_ordine', $sql);
    $result = @pg_execute($db, 'update_data_consegna_ordine', $params);
    close_pg_connection($db);
    return $result;
}