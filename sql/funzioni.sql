/* Funzioni. */

/* 3.2.2. Applicazione sconto sulla spesa. Al raggiungimento di determinate soglie di punti,
vengono sbloccati alcuni sconti. In particolare: a 100 punti si sblocca uno sconto del 5%,
a 200 punti del 15%, a 300 punti del 30%. Si noti che lo sconto non pu`o mai essere più elevato di 100 Euro.
L’applicazione dello sconto avviene su scelta del cliente, e lo sconto viene applicato sul totale della fattura
sulla quale viene applicato. In seguito all’applicazione dello sconto, il saldo punti della tessera fedeltà deve
essere decurtato del numero di punti usato per lo sconto. */

/* Proposta degli sconti applicabili in base al saldo punti relativo alla tessera fedeltà del cliente. */
CREATE OR REPLACE FUNCTION get_sconti_applicabili(_codice_fiscale VARCHAR)
RETURNS TABLE (
    _punti_necessari INT,
    _sconto_ottenuto NUMERIC
) AS $$
DECLARE
    _punti_correnti INT;
BEGIN
    -- Salvo i punti correnti dell'utente all'interno dell'attributo _punti_correnti
    SELECT saldo_punti INTO _punti_correnti
    FROM tessera_fedelta
    WHERE codice_fiscale = _codice_fiscale AND dismessa = FALSE;
    -- Se l'utente non ha tessere attive, non vi è alcuno sconto applicabile
    IF NOT FOUND THEN
        RETURN;
    END IF;
    -- Se i punti correnti sono almeno 100, allora proponi una sconto pari al 5%
    IF _punti_correnti >= 100 THEN
        RETURN QUERY SELECT 100, 5.0;
    END IF;
    -- Se i punti correnti sono almeno 200, allora proponi un ulteriore sconto pari al 15%
    IF _punti_correnti >= 200 THEN
        RETURN QUERY SELECT 200, 15.0;
    END IF;
    -- Se i punti correnti sono almeno 300, allora proponi un ulteriore sconto pari al 30%
    IF _punti_correnti >= 300 THEN
        RETURN QUERY SELECT 300, 30.0;
    END IF;
END;
$$ language 'plpgsql';

/* 3.2.5. Ordine prodotti da fornitore. Quando un prodotto deve essere rifornito di una certa quantità,
è necessario inserire un ordine presso un determinato fornitore. Il fornitore deve essere automaticamente
scelto sulla base del criterio di economicità (vale a dire, l’ordine viene automaticamente effettuato presso il fornitore che,
oltre ad avere disponibili`a di prodotto sufficiente, offre il costo minore). */
CREATE OR REPLACE FUNCTION ordina_prodotto_as_negozio(
    _codice_prodotto UUID,
    _quantita INT,
    _codice_negozio UUID
)
RETURNS UUID AS $$
DECLARE
    _partita_iva VARCHAR(11);
    _prezzo FLOAT8;
    _ordine_id UUID;
    _totale FLOAT8;
BEGIN
    -- Determino il fornitore il quale vende il prodotto di interesse in quantità sufficiente al minor prezzo unitario.
    SELECT vd.partita_iva, vd.prezzo
    INTO _partita_iva, _prezzo
    FROM venduto_da vd
    WHERE vd.codice_prodotto = _codice_prodotto AND vd.quantita >= _quantita
    ORDER BY vd.prezzo ASC
    LIMIT 1;
    -- Se non è stato individuato alcun fornitore allora segnala errore.
    IF _partita_iva IS NULL THEN
        RAISE EXCEPTION 'Nessun fornitore disponibile con scorte sufficienti';
    END IF;
    -- Calcola il totale dell'ordine e popola la tabella ordine inserendo la data odierna come data di ordinazione e NULL come data di consegna.
    _totale := _prezzo * _quantita;
    INSERT INTO ordine (data_ordine, data_consegna, totale, codice_negozio, codice_prodotto, partita_iva, quantita_ordinata)
    VALUES (CURRENT_DATE, NULL, _totale, _codice_negozio, _codice_prodotto, _partita_iva, _quantita)
    -- Ritorna il codice dell'ordine appena creato.
    RETURNING numero_ordine INTO _ordine_id;
    RETURN _ordine_id;
END;
$$ LANGUAGE plpgsql;

/* 3.2.6. Lista tesserati. Dato un negozio, è necessario conoscere la lista dei clienti ai quali il negozio ha emesso la tessera fedeltà. */
CREATE OR REPLACE FUNCTION get_tesserati_by_negozio(_codice_negozio uuid)
RETURNS TABLE (
    codice_fiscale varchar,
    nome varchar,
    cognome varchar,
    email varchar,
    saldo_punti bigint,
    data_richiesta date
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.codice_fiscale,
        u.nome,
        u.cognome,
        u.email,
        t.saldo_punti,
        t.data_richiesta
    FROM tessera_fedelta AS t JOIN utente AS u ON u.codice_fiscale = t.codice_fiscale
    WHERE t.codice_negozio = _codice_negozio AND t.dismessa = FALSE
    ORDER BY u.cognome, u.nome;
END;
$$ LANGUAGE plpgsql;

/* 3.2.7. Storico ordini a fornitori. Dato un fornitore, è necessario conoscere tutti gli ordini che sono stati effettuati presso di lui. */
CREATE OR REPLACE FUNCTION get_storico_ordini_by_fornitore(_partita_iva VARCHAR)
RETURNS TABLE (
    numero_ordine UUID,
    codice_prodotto UUID,
    nome VARCHAR,
    codice_negozio UUID,
    indirizzo VARCHAR,
    quantita_ordinata INT8,
    data_ordine DATE,
    data_consegna DATE,
    totale FLOAT8
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        o.numero_ordine,
        o.codice_prodotto,
        p.nome,
        o.codice_negozio,
        n.indirizzo,
        o.quantita_ordinata,
        o.data_ordine,
        o.data_consegna,
        o.totale
    FROM ordine o
    JOIN prodotto p ON p.codice_prodotto = o.codice_prodotto
    JOIN negozio n ON n.codice_negozio = o.codice_negozio
    WHERE o.partita_iva = _partita_iva
    ORDER BY o.data_ordine DESC, o.numero_ordine DESC;
END;
$$ LANGUAGE plpgsql;

/* Permette di verificare la validità delle credenziali di login fornite. */
/*CREATE OR REPLACE FUNCTION check_login(
    _email VARCHAR,
    _password VARCHAR
)
RETURNS BOOLEAN AS $$
DECLARE
    esiste BOOLEAN := FALSE;
BEGIN
    SELECT TRUE INTO esiste
    FROM utente
    WHERE email = _email AND password = _password;
    RETURN esiste;
END;
$$ LANGUAGE plpgsql;*/

/* Permette di verificare la validità delle credenziali di login fornite,
restituendo il codice fiscale dell'utente ed il suo ruolo qualora risultino valide. */
CREATE OR REPLACE FUNCTION check_login(
    _email VARCHAR,
    _password VARCHAR
)
RETURNS TABLE (
    _codice_fiscale VARCHAR,
    _ruolo VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT codice_fiscale, ruolo
    FROM utente
    WHERE email = _email AND password = _password;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti gli utenti. */
CREATE OR REPLACE FUNCTION get_all_utenti()
RETURNS TABLE (
    codice_fiscale VARCHAR,
    email VARCHAR,
    ruolo VARCHAR,
    nome VARCHAR,
    cognome VARCHAR,
    provincia VARCHAR,
    citta VARCHAR,
    via VARCHAR,
    civico VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.codice_fiscale,
        u.email,
        u.ruolo,
        u.nome,
        u.cognome,
        u.provincia,
        u.citta,
        u.via,
        u.civico
    FROM utente AS u
    ORDER BY u.cognome, u.nome;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti i clienti. */
CREATE OR REPLACE FUNCTION get_all_clienti()
RETURNS TABLE (
    codice_fiscale VARCHAR,
    email VARCHAR,
    ruolo VARCHAR,
    nome VARCHAR,
    cognome VARCHAR,
    provincia VARCHAR,
    citta VARCHAR,
    via VARCHAR,
    civico VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.codice_fiscale,
        u.email,
        u.ruolo,
        u.nome,
        u.cognome,
        u.provincia,
        u.citta,
        u.via,
        u.civico
    FROM utente AS u
    WHERE u.ruolo = 'cliente'
    ORDER BY u.cognome, u.nome;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi ad un utente identificato tramite codice fiscale. */
CREATE OR REPLACE FUNCTION get_utente_by_codice_fiscale(_codice_fiscale VARCHAR)
RETURNS TABLE (
    codice_fiscale VARCHAR,
    email VARCHAR,
    ruolo VARCHAR,
    password VARCHAR,
    nome VARCHAR,
    cognome VARCHAR,
    provincia VARCHAR,
    citta VARCHAR,
    via VARCHAR,
    civico VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.codice_fiscale,
        u.email,
        u.password,
        u.ruolo,
        u.nome,
        u.cognome,
        u.provincia,
        u.citta,
        u.via,
        u.civico
    FROM utente u
    WHERE u.codice_fiscale = _codice_fiscale;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi ad un utente identificato tramite email. */
CREATE OR REPLACE FUNCTION get_utente_by_email(_email VARCHAR)
RETURNS TABLE (
    codice_fiscale VARCHAR,
    email VARCHAR,
    ruolo VARCHAR,
    password VARCHAR,
    nome VARCHAR,
    cognome VARCHAR,
    provincia VARCHAR,
    citta VARCHAR,
    via VARCHAR,
    civico VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.codice_fiscale,
        u.email,
        u.password,
        u.manager,
        u.nome,
        u.cognome,
        u.provincia,
        u.citta,
        u.via,
        u.civico
    FROM utente u
    WHERE u.email = _email;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti i negozi. */
CREATE OR REPLACE FUNCTION get_all_negozi()
RETURNS TABLE (
    codice_negozio UUID,
	indirizzo VARCHAR,
	orario_apertura VARCHAR,
	nominativo_responsabile VARCHAR,
	dismesso BOOL
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        n.codice_negozio,
        n.indirizzo,
        n.orario_apertura,
        n.nominativo_responsabile,
        n.dismesso
    FROM negozio n;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti i negozi non dismessi. */
CREATE OR REPLACE FUNCTION get_negozi_non_dismessi()
RETURNS TABLE (
    codice_negozio UUID,
	indirizzo VARCHAR,
	orario_apertura VARCHAR,
	nominativo_responsabile VARCHAR,
	dismesso BOOL
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        n.codice_negozio,
        n.indirizzo,
        n.orario_apertura,
        n.nominativo_responsabile,
        n.dismesso
    FROM negozio n
    WHERE n.dismesso = FALSE;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti i negozi dismessi. */
CREATE OR REPLACE FUNCTION get_negozi_dismessi()
RETURNS TABLE (
    codice_negozio UUID,
	indirizzo VARCHAR,
	orario_apertura VARCHAR,
	nominativo_responsabile VARCHAR,
	dismesso BOOL
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        n.codice_negozio,
        n.indirizzo,
        n.orario_apertura,
        n.nominativo_responsabile,
        n.dismesso
    FROM negozio n
    WHERE n.dismesso = TRUE;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti i prodotti. */
CREATE OR REPLACE FUNCTION get_all_prodotti()
RETURNS TABLE (
    codice_prodotto UUID,
    nome VARCHAR,
    descrizione VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        p.codice_prodotto,
        p.nome,
        p.descrizione
    FROM prodotto p
    ORDER BY p.nome;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi ai prodotti in vendita presso un negozio. */
CREATE OR REPLACE FUNCTION get_prodotti_by_negozio(_codice_negozio UUID)
RETURNS TABLE (
    codice_prodotto UUID,
    nome VARCHAR,
    descrizione VARCHAR,
    prezzo FLOAT8,
    quantita INT8
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        p.codice_prodotto,
        p.nome,
        p.descrizione,
        v.prezzo,
        v.quantita
    FROM vende v
    JOIN prodotto p ON p.codice_prodotto = v.codice_prodotto
    WHERE v.codice_negozio = _codice_negozio;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi ai prodotti in vendita presso un fornitore. */
CREATE OR REPLACE FUNCTION get_prodotti_by_fornitore(_partita_iva VARCHAR)
RETURNS TABLE (
    codice_prodotto UUID,
    nome VARCHAR,
    descrizione VARCHAR,
    prezzo FLOAT8,
    quantita INT8
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        p.codice_prodotto,
        p.nome,
        p.descrizione,
        v.prezzo,
        v.quantita
    FROM venduto_da v JOIN prodotto p ON p.codice_prodotto = v.codice_prodotto
    WHERE v.partita_iva = _partita_iva;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi ai prodotti non in vendita presso un fornitore. */
CREATE OR REPLACE FUNCTION get_prodotti_fuori_catalogo_by_fornitore(_partita_iva VARCHAR)
RETURNS TABLE (
    codice_prodotto UUID,
    nome           VARCHAR,
    descrizione    VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT p.codice_prodotto, p.nome, p.descrizione
    FROM prodotto p
    WHERE NOT EXISTS (
        SELECT 1
        FROM venduto_da v
        WHERE v.codice_prodotto = p.codice_prodotto AND v.partita_iva = _partita_iva
    );
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi a tutti i fornitori. */
CREATE OR REPLACE FUNCTION get_all_fornitori()
RETURNS TABLE (
    partita_iva VARCHAR,
    indirizzo VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT f.partita_iva, f.indirizzo
    FROM fornitore f;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi agli ordini effettuati da parte di un negozio. */
CREATE OR REPLACE FUNCTION get_ordini_by_negozio(_codice_negozio UUID)
RETURNS TABLE (
    numero_ordine UUID,
    data_ordine DATE,
    data_consegna DATE,
    totale FLOAT8,
    codice_prodotto UUID,
    partita_iva VARCHAR,
    quantita_ordinata INT8
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        o.numero_ordine,
        o.data_ordine,
        o.data_consegna,
        o.totale,
        o.codice_prodotto,
        o.partita_iva,
        o.quantita_ordinata
    FROM ordine AS o
    WHERE o.codice_negozio = _codice_negozio
    ORDER BY o.data_ordine DESC, o.numero_ordine DESC;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi agli ordini riguardanti un prodotto. */
CREATE OR REPLACE FUNCTION get_ordini_by_prodotto(_codice_prodotto UUID)
RETURNS TABLE (
    numero_ordine UUID,
    data_ordine DATE,
    data_consegna DATE,
    totale FLOAT8,
    codice_negozio UUID,
    partita_iva VARCHAR,
    quantita_ordinata INT8
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        o.numero_ordine,
        o.data_ordine,
        o.data_consegna,
        o.totale,
        o.codice_negozio,
        o.partita_iva,
        o.quantita_ordinata
    FROM ordine AS o
    WHERE o.codice_prodotto = _codice_prodotto
    ORDER BY o.data_ordine DESC, o.numero_ordine DESC;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi alle fatture che coinvolgono un utente. */
CREATE OR REPLACE FUNCTION get_fatture_by_utente(_codice_fiscale VARCHAR)
RETURNS TABLE (
    codice_fattura UUID,
    data_acquisto DATE,
    totale FLOAT8,
    sconto_percentuale FLOAT8,
    totale_pagato FLOAT8
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        f.codice_fattura,
        f.data_acquisto,
        f.totale,
        f.sconto_percentuale,
        f.totale_pagato
    FROM fattura AS f
    WHERE f.codice_fiscale = _codice_fiscale;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi alle fatture che coinvolgono un negozio. */
CREATE OR REPLACE FUNCTION get_fatture_by_negozio(_codice_negozio UUID)
RETURNS TABLE (
    codice_fattura UUID,
    codice_fiscale VARCHAR,
    codice_prodotto UUID,
    quantita_acquistata INT8,
    prezzo FLOAT8,
    data_acquisto DATE,
    totale FLOAT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.codice_fattura,
        f.codice_fiscale,
        e.codice_prodotto,
        e.quantita_acquistata,
        e.prezzo,
        f.data_acquisto,
        f.totale
    FROM emette e JOIN fattura f ON f.codice_fattura = e.codice_fattura
    WHERE e.codice_negozio = _codice_negozio;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi alle fatture che coinvolgono un prodotto. */
CREATE OR REPLACE FUNCTION get_fatture_by_prodotto(_codice_prodotto UUID)
RETURNS TABLE (
    codice_fattura UUID,
    codice_fiscale VARCHAR,
    codice_negozio UUID,
    quantita_acquistata INT8,
    prezzo FLOAT8,
    data_acquisto DATE,
    totale FLOAT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.codice_fattura,
        f.codice_fiscale,
        e.codice_negozio,
        e.quantita_acquistata,
        e.prezzo,
        f.data_acquisto,
        f.totale
    FROM emette e JOIN fattura f ON f.codice_fattura = e.codice_fattura
    WHERE e.codice_prodotto = _codice_prodotto;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi alle tessere fedeltà di un utente. */
CREATE OR REPLACE FUNCTION get_tessere_by_utente(_codice_fiscale VARCHAR)
RETURNS TABLE (
    codice_tessera UUID,
    saldo_punti    INT8,
    codice_negozio UUID,
    data_richiesta  DATE,
    dismessa       BOOLEAN
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        t.codice_tessera,
        t.saldo_punti,
        t.codice_negozio,
        t.data_richiesta,
        t.dismessa
    FROM tessera_fedelta AS t
    WHERE t.codice_fiscale = _codice_fiscale
    ORDER BY t.data_richiesta DESC;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi alla tessere fedeltà non dismessa di un utente. */
CREATE OR REPLACE FUNCTION get_tessera_non_dismessa_by_utente(_codice_fiscale VARCHAR)
RETURNS TABLE (
    codice_tessera UUID,
    saldo_punti    INT8,
    codice_negozio UUID,
    data_richiesta  DATE
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        t.codice_tessera,
        t.saldo_punti,
        t.codice_negozio,
        t.data_richiesta
    FROM tessera_fedelta AS t
    WHERE t.codice_fiscale = _codice_fiscale AND t.dismessa = FALSE;
END;
$$ LANGUAGE plpgsql;

/* Permette di ottenere i dati relativi alle tessere fedeltà emesse da un negozio dismesso. */
CREATE OR REPLACE FUNCTION get_tesserati_by_negozio_dismesso(_codice_negozio uuid)
RETURNS TABLE (
    codice_fiscale varchar,
    nome varchar,
    cognome varchar,
    email varchar,
    saldo_punti bigint,
    data_richiesta date
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.codice_fiscale,
        u.nome,
        u.cognome,
        u.email,
        t.saldo_punti,
        t.data_richiesta
    FROM view_tessere_dismesse AS t JOIN utente AS u ON u.codice_fiscale = t.codice_fiscale
    WHERE t.codice_negozio = _codice_negozio
    ORDER BY u.cognome, u.nome;
END;
$$ LANGUAGE plpgsql;

/* ... */
CREATE OR REPLACE FUNCTION add_fattura_by_carrello(
    _codice_fiscale varchar,
    _codice_negozio uuid,
    _prodotti uuid[],
    _quantita int8[]
) RETURNS uuid
LANGUAGE plpgsql
AS $$
DECLARE
    i int;
    _prezzo float8;
    _totale float8 := 0;
    _codice_fattura uuid;
BEGIN
    IF _prodotti IS NULL OR _quantita IS NULL THEN
        RAISE EXCEPTION 'Lista prodotti o quantità nulla';
    END IF;
    IF array_length(_prodotti, 1) IS DISTINCT FROM array_length(_quantita, 1) THEN
        RAISE EXCEPTION 'Array prodotti e quantità di lunghezza diversa';
    END IF;
    -- Calcolo del totale
    FOR i IN 1..array_length(_prodotti, 1) LOOP
        SELECT prezzo INTO _prezzo
        FROM vende
        WHERE codice_negozio = _codice_negozio
          AND codice_prodotto = _prodotti[i];
        IF NOT FOUND THEN
            RAISE EXCEPTION 'Prodotto % non venduto dal negozio %', _prodotti[i], _codice_negozio;
        END IF;
        _totale := _totale + _prezzo * _quantita[i];
    END LOOP;
    INSERT INTO fattura (data_acquisto, totale, sconto_percentuale, totale_pagato, codice_fiscale)
    VALUES (CURRENT_DATE, _totale, NULL, _totale, _codice_fiscale)
    RETURNING codice_fattura INTO _codice_fattura;
    FOR i IN 1..array_length(_prodotti, 1) LOOP
        SELECT prezzo INTO _prezzo
        FROM vende
        WHERE codice_negozio = _codice_negozio
          AND codice_prodotto = _prodotti[i];
        INSERT INTO emette (codice_negozio, codice_prodotto, codice_fattura, prezzo, quantita_acquistata)
        VALUES (_codice_negozio, _prodotti[i], _codice_fattura, _prezzo, _quantita[i]);
        UPDATE vende
        SET quantita = quantita - _quantita[i]
        WHERE codice_negozio = _codice_negozio
          AND codice_prodotto = _prodotti[i];
    END LOOP;
    RETURN _codice_fattura;
END;
$$;