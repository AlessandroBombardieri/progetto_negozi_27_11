/* Procedure. */

/* Password. */

/* Permette di modificare la password relativa ad un utente con una nuova, a patto che la vecchia password fornita sia corretta. */
CREATE OR REPLACE PROCEDURE change_password(
    _codice_fiscale VARCHAR,
    _vecchia_password VARCHAR,
    _nuova_password VARCHAR
) AS $$
DECLARE
    count INT;
BEGIN
    -- Controllo esplicito: quante righe corrispondono a CF + vecchia password
    SELECT COUNT(*)
    INTO count
    FROM utente
    WHERE codice_fiscale = _codice_fiscale AND password = _vecchia_password;
    -- Se l'utente non viene trovato oppure la vecchia password inserita non corrisponde allora segnala errore.
    IF count = 0 THEN
        RAISE EXCEPTION 'Password inserita errata';
    END IF;
    -- Aggiorna la password dell'utente con la nuova da lui scelta.
    UPDATE utente
    SET password = _nuova_password
    WHERE codice_fiscale = _codice_fiscale;
END;
$$ LANGUAGE plpgsql;

/* Utenti/Clienti */

/* Permette di creare un nuovo cliente. */
CREATE OR REPLACE PROCEDURE add_cliente(
    _codice_fiscale VARCHAR,
    _email VARCHAR,
    _password VARCHAR,
    _nome VARCHAR,
    _cognome VARCHAR,
    _provincia VARCHAR,
    _citta VARCHAR,
    _via VARCHAR,
    _civico VARCHAR
) AS $$
BEGIN
    INSERT INTO utente(codice_fiscale, email, password, ruolo, nome, cognome, provincia, citta, via, civico)
    VALUES (_codice_fiscale, _email, _password, 'cliente', _nome, _cognome, _provincia, _citta, _via, _civico);
END;
$$ LANGUAGE plpgsql;

/* Prodotti. */

/* Permette di creare un nuovo prodotto. */
CREATE OR REPLACE PROCEDURE add_prodotto(
    _nome VARCHAR,
    _descrizione VARCHAR
) AS $$
BEGIN
    INSERT INTO prodotto(nome, descrizione)
    VALUES (_nome, _descrizione);
END;
$$ LANGUAGE plpgsql;

/* Permette di aggiungere un nuovo prodotto all'inventario di un fornitore. */
CREATE OR REPLACE PROCEDURE add_prodotto_as_fornitore(
    _partita_iva VARCHAR,
    _codice_prodotto UUID,
    _prezzo FLOAT8,
    _quantita INT
) AS $$
BEGIN
    INSERT INTO venduto_da(partita_iva, codice_prodotto, prezzo, quantita)
    VALUES (_partita_iva, _codice_prodotto, _prezzo, _quantita);
END;
$$ LANGUAGE plpgsql;

/* Permette di aggiungere alle scorte una determinata quantità di un prodotto presente nell'inventario di un fornitore. */
CREATE OR REPLACE PROCEDURE update_quantita_prodotto_as_fornitore(
    _partita_iva VARCHAR,
    _codice_prodotto UUID,
    _quantita INT
) AS $$
BEGIN
    IF _quantita < 0 THEN
        RAISE EXCEPTION 'La quantità da aggiungere non può essere negativa';
    END IF;
    UPDATE venduto_da
    SET quantita = quantita + _quantita
    WHERE partita_iva = _partita_iva AND codice_prodotto = _codice_prodotto;
END;
$$ LANGUAGE plpgsql;

/* Permette di modificare il prezzo di vendita di un prodotto presente nell'inventario di un fornitore. */
CREATE OR REPLACE PROCEDURE update_prezzo_prodotto_as_fornitore(
    _partita_iva VARCHAR,
    _codice_prodotto UUID,
    _prezzo FLOAT8
) AS $$
BEGIN
    UPDATE venduto_da
    SET prezzo = _prezzo
    WHERE partita_iva = _partita_iva AND codice_prodotto = _codice_prodotto;
END;
$$ LANGUAGE plpgsql;

/* Permette di modificare il prezzo di vendita di un prodotto in vendita presso un negozio. */
CREATE OR REPLACE PROCEDURE update_prezzo_prodotto_as_negozio(
    _codice_negozio UUID,
    _codice_prodotto UUID,
    _nuovo_prezzo FLOAT8
) AS $$
BEGIN
    IF _nuovo_prezzo < 0 THEN
        RAISE EXCEPTION 'Prezzo negativo non ammesso';
    END IF;
    UPDATE vende
    SET prezzo = _nuovo_prezzo
    WHERE codice_negozio = _codice_negozio
      AND codice_prodotto = _codice_prodotto;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Prodotto % non presente nel negozio %', _codice_prodotto, _codice_negozio;
    END IF;
END;
$$ LANGUAGE plpgsql;

/* Fornitori. */

/* Permette di creare un nuovo fornitore. */
CREATE OR REPLACE PROCEDURE add_fornitore(
    _partita_iva VARCHAR,
    _indirizzo VARCHAR
) AS $$
BEGIN
    INSERT INTO fornitore(partita_iva, indirizzo)
    VALUES (_partita_iva, _indirizzo);
END;
$$ LANGUAGE plpgsql;

/* Negozi. */

/* Permette di creare un nuovo negozio. */
CREATE OR REPLACE PROCEDURE add_negozio(
    _indirizzo VARCHAR,
    _orario_apertura VARCHAR,
    _responsabile VARCHAR
) AS $$
BEGIN
    INSERT INTO negozio(indirizzo, orario_apertura, nominativo_responsabile, dismesso)
    VALUES (_indirizzo, _orario_apertura, _responsabile, FALSE);
END;
$$ LANGUAGE plpgsql;

/* Permette di dismettere, ovvero chiudere un negozio. In particolare, oltre a dismettere tutte le tessere dei clienti associati ad esso,
è concesso specificare il codice_negozio di un secondo negozio presso il quale trasferire i prodotti ancora in vendita prima che vengano rimossi.
Se il codice_negozio è NULL, allora i prodotti in vendita vengono semplicemente rimossi. */
CREATE OR REPLACE PROCEDURE dismetti_negozio(
    _codice_negozio UUID,
    _nuovo_codice_negozio UUID DEFAULT NULL
) AS $$
DECLARE
    _codice_prodotto UUID;
    _prezzo FLOAT8;
    _quantita INT8;
BEGIN
    -- Revoco la validità delle tessere fedeltà relative ai clienti di tale negozio.
    UPDATE tessera_fedelta
    SET dismessa = TRUE
    WHERE codice_negozio = _codice_negozio;
    -- Imposto l'attributo dismesso del negozio in via di chiusura a true.
    UPDATE negozio
    SET dismesso = TRUE
    WHERE codice_negozio = _codice_negozio;
    IF _nuovo_codice_negozio IS NOT NULL THEN
        -- Se viene specificato alla procedura un secondo negozio presso il quale trasferire le scorte.
        FOR _codice_prodotto, _prezzo, _quantita IN 
            SELECT codice_prodotto, prezzo, quantita
            FROM vende
            WHERE codice_negozio = _codice_negozio
            LOOP
                -- Per ogni prodotto in vendita presso il negozio dismesso.
                BEGIN
                -- Incremento la quantità se il prodotto dovesse essere già presente nel nuovo negozio.
                    UPDATE vende
                    SET quantita = quantita + _quantita
                    WHERE codice_negozio = _nuovo_codice_negozio AND codice_prodotto = _codice_prodotto;
                    -- Se non è stato trovato un prodotto già esistente, ne creo uno nuovo.
                    IF NOT FOUND THEN
                        INSERT INTO vende(codice_negozio, codice_prodotto, prezzo, quantita)
                        VALUES (_nuovo_codice_negozio, _codice_prodotto, _prezzo, _quantita);
                    END IF;
                END;
            END LOOP;
    END IF;
    -- Elimino i prodotti in vendita presso il negozio dismesso.
    DELETE FROM vende
    WHERE codice_negozio = _codice_negozio;
END;
$$ LANGUAGE plpgsql;

/* Fatture. */

/* 3.3.2. Applicazione sconto sulla spesa. Al raggiungimento di determinate soglie di punti,
vengono sbloccati alcuni sconti. In particolare: a 100 punti si sblocca uno sconto del 5%,
a 200 punti del 15%, a 300 punti del 30%. Si noti che lo sconto non pu`o mai essere più elevato di 100 Euro.
L’applicazione dello sconto avviene su scelta del cliente, e lo sconto viene applicato sul totale della fattura
sulla quale viene applicato. In seguito all’applicazione dello sconto, il saldo punti della tessera fedeltà deve
essere decurtato del numero di punti usato per lo sconto. */

/* Applica lo sconto selezionato identificato tramite la quantità di punti utilizzati sul totale della relativa fattura. */
CREATE OR REPLACE PROCEDURE update_totale_fattura(
    _codice_fattura UUID,
    _codice_fiscale VARCHAR,
    _punti_utilizzati INT
) AS $$
DECLARE
    _sconto_percentuale NUMERIC := 0;
    _fattura_totale FLOAT8;
    _sconto_euro FLOAT8 := 0;
    _nuovo_totale FLOAT8;
    _punti_disponibili INT;
BEGIN
    -- Salvo i punti correnti dell'utente all'interno dell'attributo _punti_disponibili.
    SELECT saldo_punti
    INTO _punti_disponibili
    FROM tessera_fedelta
    WHERE codice_fiscale = _codice_fiscale AND dismessa = FALSE;
    -- Se i punti disponibili sono minori dei punti utilizzati allora segnala errore.
    IF _punti_disponibili < _punti_utilizzati THEN
        RAISE EXCEPTION 'Punti insufficienti';
    END IF;
    -- Determina la percentuale di sconto in base ai punti utilizzati.
    IF _punti_utilizzati = 100 THEN
        _sconto_percentuale := 0.05;
    ELSIF _punti_utilizzati = 200 THEN
        _sconto_percentuale := 0.15;
    ELSIF _punti_utilizzati = 300 THEN
        _sconto_percentuale := 0.30;
    ELSIF _punti_utilizzati = 0 THEN
        _sconto_percentuale := 0.00;
    ELSE
        -- Se la quantità di punti utilizzati non è valida allora segnala errore.
        RAISE EXCEPTION 'Quantità di punti utilizzati non valida';
    END IF;
    -- Salvo il totale della fattura all'interno dell'attributo _fattura_totale.
    SELECT totale
    INTO _fattura_totale
    FROM fattura
    WHERE codice_fattura = _codice_fattura;
    -- Calcolo lo sconto applicabile alla fattura secondo la percentuale di sconto scelta, considerando un tetto massimo pari ad euro 100.
    _sconto_euro := LEAST(_fattura_totale * _sconto_percentuale, 100);
    -- Aggiorno il totale della fattura.
    _nuovo_totale := _fattura_totale - _sconto_euro;
    UPDATE fattura
    SET sconto_percentuale = (_sconto_percentuale * 100)::FLOAT8, totale_pagato = _nuovo_totale
    WHERE codice_fattura = _codice_fattura;
    -- Se sono stati utilizzati punti allora aggiorna il saldo relativo alla tessera fedeltà dell'utente.
    IF _punti_utilizzati > 0 THEN
        UPDATE tessera_fedelta
        SET saldo_punti = saldo_punti - _punti_utilizzati
        WHERE codice_fiscale = _codice_fiscale AND dismessa = FALSE;
    END IF;
END;
$$ LANGUAGE plpgsql;

/* Tessere fedeltà. */

/* Permette di creare una nuova tessera dato un cliente ed un negozio, se e solo se tale cliente non ne possiede già una attiva.
Se il cliente è in possesso di una tessera dismessa, allora si recuperano i punti di tale tessera i quali vengono aggiunti come saldo iniziale. */
CREATE OR REPLACE PROCEDURE add_tessera(
    _codice_negozio UUID,
    _codice_fiscale VARCHAR,
    _data DATE
) AS $$
DECLARE
    _saldo_punti INT8;
BEGIN
    -- Se il codice fiscale dell'utente non trova alcuna corrispondenza allora segnala errore.
    IF NOT EXISTS (
        SELECT 1 FROM utente
        WHERE codice_fiscale = _codice_fiscale
    ) THEN
        RAISE EXCEPTION 'Cliente % inesistente', _codice_fiscale;
    END IF;
    -- Se il codice negozio inserito non trova corrispondenza, allora segnala errore.
    IF NOT EXISTS (
        SELECT 1 FROM negozio
        WHERE codice_negozio = _codice_negozio
    ) THEN
        RAISE EXCEPTION 'Negozio % inesistente', _codice_negozio;
    END IF;
    -- Se esiste già una tessera fedeltà attiva associata all'utente allora segnala errore.
    IF EXISTS (
        SELECT 1
        FROM tessera_fedelta
        WHERE codice_fiscale = _codice_fiscale
          AND dismessa = FALSE
    ) THEN
        RAISE EXCEPTION 'Tessera attiva già presente per %', _codice_fiscale;
    END IF;
    -- Se esiste già una tessera dismessa associata all'utente allora ne recupera il saldo.
    SELECT saldo_punti
    INTO _saldo_punti
    FROM tessera_fedelta
    WHERE codice_fiscale = _codice_fiscale
      AND dismessa = TRUE
    ORDER BY data_richiesta DESC
    LIMIT 1;
    IF FOUND THEN
        INSERT INTO tessera_fedelta(codice_negozio, codice_fiscale, data_richiesta, saldo_punti, dismessa)
        VALUES (_codice_negozio, _codice_fiscale, _data, _saldo_punti, FALSE);
        RETURN;
    END IF;
    -- Altrimenti crea una nuova tessera con saldo punti iniziale pari a 0.
    INSERT INTO tessera_fedelta(codice_negozio, codice_fiscale, data_richiesta, saldo_punti, dismessa)
    VALUES (_codice_negozio, _codice_fiscale, _data, 0, FALSE);
END;
$$ LANGUAGE plpgsql;

/* Ordini. */

/* Permette di aggiornare la data di consegna di un ordine. */
CREATE OR REPLACE PROCEDURE update_data_consegna_ordine(
    _numero_ordine UUID,
    _data_consegna DATE
) AS $$
DECLARE
    _data_ordine DATE;
BEGIN
    IF _data_consegna IS NULL THEN
        RAISE EXCEPTION 'La data di consegna non può essere NULL';
    END IF;
    SELECT data_ordine INTO _data_ordine
    FROM ordine
    WHERE numero_ordine = _numero_ordine;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ordine % inesistente', _numero_ordine;
    END IF;
    -- Se la nuova data di consegna dell'ordine è precedente rispetto alla data dell'ordinazione allora segnala errore.
    IF _data_consegna < _data_ordine THEN
        RAISE EXCEPTION 'La data di consegna (%) non può essere precedente alla data ordine (%)', _data_consegna, _data_ordine;
    END IF;
    UPDATE ordine
    SET data_consegna = _data_consegna
    WHERE numero_ordine = _numero_ordine;
END;
$$ LANGUAGE plpgsql;