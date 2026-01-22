--
-- PostgreSQL database dump
--

\restrict WlzsyaZwdPmuIxCmBhNOPG0YtNgJINFfPJbPJy9fcJbbQB0Kdn0OZr6A3QJC1Px

-- Dumped from database version 17.4
-- Dumped by pg_dump version 18.1

-- Started on 2026-01-22 17:13:43 CET

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 230 (class 1255 OID 19428)
-- Name: add_cliente(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_cliente(IN _codice_fiscale character varying, IN _email character varying, IN _password character varying, IN _nome character varying, IN _cognome character varying, IN _provincia character varying, IN _citta character varying, IN _via character varying, IN _civico character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Controllo lunghezza codice fiscale.
    IF LENGTH(_codice_fiscale) <> 16 THEN
        RAISE EXCEPTION 'Formato codice fiscale non valido.';
    END IF;
    INSERT INTO utente(codice_fiscale, email, password, ruolo, nome, cognome, provincia, citta, via, civico)
    VALUES (_codice_fiscale, _email, _password, 'cliente', _nome, _cognome, _provincia, _citta, _via, _civico);
END;
$$;


--
-- TOC entry 271 (class 1255 OID 19455)
-- Name: add_fattura_by_carrello(character varying, uuid, uuid[], bigint[]); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.add_fattura_by_carrello(_codice_fiscale character varying, _codice_negozio uuid, _prodotti uuid[], _quantita bigint[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$
DECLARE
    i INT;
    _prezzo NUMERIC;
    _totale NUMERIC := 0;
    _codice_fattura UUID;
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


--
-- TOC entry 237 (class 1255 OID 19434)
-- Name: add_fornitore(character varying, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_fornitore(IN _partita_iva character varying, IN _indirizzo character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Controllo lunghezza partita IVA.
    IF LENGTH(_partita_iva) <> 11 THEN
        RAISE EXCEPTION 'Formato partita IVA non valido.';
    END IF;
    INSERT INTO fornitore(partita_iva, indirizzo)
    VALUES (_partita_iva, _indirizzo);
END;
$$;


--
-- TOC entry 238 (class 1255 OID 19435)
-- Name: add_negozio(character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_negozio(IN _indirizzo character varying, IN _orario_apertura character varying, IN _responsabile character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO negozio(indirizzo, orario_apertura, nominativo_responsabile, dismesso)
    VALUES (_indirizzo, _orario_apertura, _responsabile, FALSE);
END;
$$;


--
-- TOC entry 231 (class 1255 OID 19429)
-- Name: add_prodotto(character varying, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_prodotto(IN _nome character varying, IN _descrizione character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO prodotto(nome, descrizione)
    VALUES (_nome, _descrizione);
END;
$$;


--
-- TOC entry 232 (class 1255 OID 19430)
-- Name: add_prodotto_as_fornitore(character varying, uuid, numeric, integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_prodotto_as_fornitore(IN _partita_iva character varying, IN _codice_prodotto uuid, IN _prezzo numeric, IN _quantita integer)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO venduto_da(partita_iva, codice_prodotto, prezzo, quantita)
    VALUES (_partita_iva, _codice_prodotto, _prezzo, _quantita);
END;
$$;


--
-- TOC entry 258 (class 1255 OID 19438)
-- Name: add_tessera(uuid, character varying, date); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_tessera(IN _codice_negozio uuid, IN _codice_fiscale character varying, IN _data date)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 229 (class 1255 OID 19427)
-- Name: change_password(character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.change_password(IN _codice_fiscale character varying, IN _vecchia_password character varying, IN _nuova_password character varying)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 260 (class 1255 OID 19440)
-- Name: check_login(character varying, character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.check_login(_email character varying, _password character varying) RETURNS TABLE(_codice_fiscale character varying, _ruolo character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT codice_fiscale, ruolo
    FROM utente
    WHERE email = _email AND password = _password;
END;
$$;


--
-- TOC entry 244 (class 1255 OID 19469)
-- Name: data_consegna_ordine_is_not_empty(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.data_consegna_ordine_is_not_empty() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.data_consegna IS NOT NULL THEN
        RAISE EXCEPTION 'La data di consegna non può essere impostata al momento dell''ordine';
    END IF;
    RETURN NEW;
END;
$$;


--
-- TOC entry 245 (class 1255 OID 19436)
-- Name: dismetti_negozio(uuid, uuid); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.dismetti_negozio(IN _codice_negozio uuid, IN _nuovo_codice_negozio uuid DEFAULT NULL::uuid)
    LANGUAGE plpgsql
    AS $$
DECLARE
    _codice_prodotto UUID;
    _prezzo NUMERIC;
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
$$;


--
-- TOC entry 261 (class 1255 OID 19441)
-- Name: get_all_clienti(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_all_clienti() RETURNS TABLE(codice_fiscale character varying, email character varying, ruolo character varying, nome character varying, cognome character varying, provincia character varying, citta character varying, via character varying, civico character varying)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 268 (class 1255 OID 19452)
-- Name: get_all_fatture(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_all_fatture() RETURNS TABLE(codice_fattura uuid, codice_fiscale character varying, codice_negozio uuid, codice_prodotto uuid, prezzo numeric, quantita_acquistata bigint, data_acquisto date, totale numeric, totale_pagato numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.codice_fattura,
        f.codice_fiscale,
        e.codice_negozio,
        e.codice_prodotto,
        e.prezzo,
        e.quantita_acquistata,
        f.data_acquisto,
        f.totale,
        f.totale_pagato
    FROM emette e JOIN fattura f ON f.codice_fattura = e.codice_fattura
    ORDER BY f.data_acquisto DESC, f.codice_fattura DESC;
END;
$$;


--
-- TOC entry 264 (class 1255 OID 19448)
-- Name: get_all_fornitori(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_all_fornitori() RETURNS TABLE(partita_iva character varying, indirizzo character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT f.partita_iva, f.indirizzo
    FROM fornitore f;
END;
$$;


--
-- TOC entry 265 (class 1255 OID 19449)
-- Name: get_all_negozi(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_all_negozi() RETURNS TABLE(codice_negozio uuid, indirizzo character varying, orario_apertura character varying, nominativo_responsabile character varying, dismesso boolean)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 234 (class 1255 OID 19443)
-- Name: get_all_prodotti(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_all_prodotti() RETURNS TABLE(codice_prodotto uuid, nome character varying, descrizione character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        p.codice_prodotto,
        p.nome,
        p.descrizione
    FROM prodotto p
    ORDER BY p.nome;
END;
$$;


--
-- TOC entry 269 (class 1255 OID 19453)
-- Name: get_fatture_by_utente(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_fatture_by_utente(_codice_fiscale character varying) RETURNS TABLE(codice_fattura uuid, data_acquisto date, totale numeric, sconto_percentuale double precision, totale_pagato numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        f.codice_fattura,
        f.data_acquisto,
        f.totale,
        f.sconto_percentuale,
        f.totale_pagato
    FROM fattura AS f
    WHERE f.codice_fiscale = _codice_fiscale
    ORDER BY f.data_acquisto DESC, f.codice_fattura DESC;
END;
$$;


--
-- TOC entry 267 (class 1255 OID 19451)
-- Name: get_negozi_dismessi(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_negozi_dismessi() RETURNS TABLE(codice_negozio uuid, indirizzo character varying, orario_apertura character varying, nominativo_responsabile character varying, dismesso boolean)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 266 (class 1255 OID 19450)
-- Name: get_negozi_non_dismessi(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_negozi_non_dismessi() RETURNS TABLE(codice_negozio uuid, indirizzo character varying, orario_apertura character varying, nominativo_responsabile character varying, dismesso boolean)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 240 (class 1255 OID 19445)
-- Name: get_prodotti_by_fornitore(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_prodotti_by_fornitore(_partita_iva character varying) RETURNS TABLE(codice_prodotto uuid, nome character varying, descrizione character varying, prezzo numeric, quantita bigint)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 239 (class 1255 OID 19444)
-- Name: get_prodotti_by_negozio(uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_prodotti_by_negozio(_codice_negozio uuid) RETURNS TABLE(codice_prodotto uuid, nome character varying, descrizione character varying, prezzo numeric, quantita bigint)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 241 (class 1255 OID 19446)
-- Name: get_prodotti_fuori_catalogo_by_fornitore(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_prodotti_fuori_catalogo_by_fornitore(_partita_iva character varying) RETURNS TABLE(codice_prodotto uuid, nome character varying, descrizione character varying)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 270 (class 1255 OID 19454)
-- Name: get_sconti_applicabili(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_sconti_applicabili(_codice_fiscale character varying) RETURNS TABLE(_punti_necessari integer, _sconto_ottenuto numeric)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 276 (class 1255 OID 19460)
-- Name: get_storico_ordini_by_fornitore(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_storico_ordini_by_fornitore(_partita_iva character varying) RETURNS TABLE(numero_ordine uuid, codice_prodotto uuid, nome character varying, codice_negozio uuid, indirizzo character varying, quantita_ordinata bigint, data_ordine date, data_consegna date, totale numeric)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 273 (class 1255 OID 19457)
-- Name: get_tessera_non_dismessa_by_utente(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_tessera_non_dismessa_by_utente(_codice_fiscale character varying) RETURNS TABLE(codice_tessera uuid, saldo_punti bigint, codice_negozio uuid, data_richiesta date)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 274 (class 1255 OID 19458)
-- Name: get_tesserati_by_negozio(uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_tesserati_by_negozio(_codice_negozio uuid) RETURNS TABLE(codice_fiscale character varying, nome character varying, cognome character varying, email character varying, saldo_punti bigint, data_richiesta date)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 275 (class 1255 OID 19459)
-- Name: get_tesserati_by_negozio_dismesso(uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_tesserati_by_negozio_dismesso(_codice_negozio uuid) RETURNS TABLE(codice_fiscale character varying, nome character varying, cognome character varying, email character varying, saldo_punti bigint, data_richiesta date)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 272 (class 1255 OID 19456)
-- Name: get_tessere_by_utente(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_tessere_by_utente(_codice_fiscale character varying) RETURNS TABLE(codice_tessera uuid, saldo_punti bigint, codice_negozio uuid, data_richiesta date, dismessa boolean)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 262 (class 1255 OID 19442)
-- Name: get_utente_by_codice_fiscale(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_utente_by_codice_fiscale(_codice_fiscale character varying) RETURNS TABLE(codice_fiscale character varying, email character varying, password character varying, ruolo character varying, nome character varying, cognome character varying, provincia character varying, citta character varying, via character varying, civico character varying)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 263 (class 1255 OID 19447)
-- Name: ordina_prodotto_as_negozio(uuid, integer, uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.ordina_prodotto_as_negozio(_codice_prodotto uuid, _quantita integer, _codice_negozio uuid) RETURNS uuid
    LANGUAGE plpgsql
    AS $$
DECLARE
    _partita_iva VARCHAR(11);
    _prezzo NUMERIC;
    _ordine_id UUID;
    _totale NUMERIC;
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
$$;


--
-- TOC entry 243 (class 1255 OID 19467)
-- Name: refresh_view_tessere_dismesse(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.refresh_view_tessere_dismesse() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Se il negozio è appena stato dismesso, aggiorna la MV
    IF NEW.dismesso = TRUE AND OLD.dismesso = FALSE THEN
        REFRESH MATERIALIZED VIEW view_tessere_dismesse;
    END IF;
    RETURN NEW;
END;
$$;


--
-- TOC entry 278 (class 1255 OID 19463)
-- Name: tessera_gia_presente(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.tessera_gia_presente() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        -- Flag true se esiste già una tessera attiva per l'utente.
        SELECT 1 FROM tessera_fedelta
        WHERE codice_fiscale = NEW.codice_fiscale AND dismessa = FALSE
    ) THEN
        -- Se l'utente possiede già una tessera fedeltà attiva allora segnala errore.
        RAISE EXCEPTION 'Tessera attiva già presente';
    END IF;
    RETURN NEW;
END;
$$;


--
-- TOC entry 259 (class 1255 OID 19439)
-- Name: update_data_consegna_ordine(uuid, date); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.update_data_consegna_ordine(IN _numero_ordine uuid, IN _data_consegna date)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 277 (class 1255 OID 19461)
-- Name: update_disponibilita_as_fornitore(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_disponibilita_as_fornitore() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    _prezzo_fornitore NUMERIC;
    _quantita_corrente INT8;
BEGIN
    -- Recupero il prezzo dell'oggetto ordinato.
    SELECT prezzo
    INTO _prezzo_fornitore
    FROM venduto_da
    WHERE partita_iva = NEW.partita_iva
      AND codice_prodotto = NEW.codice_prodotto;
    -- Se partita iva fornitore oppure codice prodotto non trovati allora segnala errore.
    IF NOT FOUND OR _prezzo_fornitore IS NULL THEN
        RAISE EXCEPTION 'Fornitore o prodotto mancanti';
    END IF;
    -- Aggiorno la quantità di prodotto ordinato in vendita dal fornitore sottraendo la quantità ordinata.
    UPDATE venduto_da
    SET quantita = quantita - NEW.quantita_ordinata
    WHERE partita_iva = NEW.partita_iva
      AND codice_prodotto = NEW.codice_prodotto
      AND quantita >= NEW.quantita_ordinata;
    -- Se la quantità disponibile è inferiore rispetto a quella ordinata allora segnala errore.
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Scorte insufficienti';
    END IF;
    -- Verifico se il prodotto è già presente nella tabella vende per il negozio specificato.
    SELECT quantita
    INTO _quantita_corrente
    FROM vende
    WHERE codice_negozio = NEW.codice_negozio
      AND codice_prodotto = NEW.codice_prodotto;
    -- Se il prodotto esiste, aggiorno la quantità.
    IF FOUND THEN
        UPDATE vende
        SET quantita = _quantita_corrente + NEW.quantita_ordinata
        WHERE codice_negozio = NEW.codice_negozio
          AND codice_prodotto = NEW.codice_prodotto;
    ELSE
        -- Altrimenti, inserisco una nuova riga.
        INSERT INTO vende(codice_negozio, codice_prodotto, prezzo, quantita)
        VALUES (NEW.codice_negozio, NEW.codice_prodotto, _prezzo_fornitore, NEW.quantita_ordinata);
    END IF;
    RETURN NEW;
END;
$$;


--
-- TOC entry 235 (class 1255 OID 19432)
-- Name: update_prezzo_prodotto_as_fornitore(character varying, uuid, numeric); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.update_prezzo_prodotto_as_fornitore(IN _partita_iva character varying, IN _codice_prodotto uuid, IN _prezzo numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF _prezzo < 0 THEN
        RAISE EXCEPTION 'Prezzo negativo non ammesso';
    END IF;
    UPDATE venduto_da
    SET prezzo = _prezzo
    WHERE partita_iva = _partita_iva AND codice_prodotto = _codice_prodotto;
END;
$$;


--
-- TOC entry 236 (class 1255 OID 19433)
-- Name: update_prezzo_prodotto_as_negozio(uuid, uuid, numeric); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.update_prezzo_prodotto_as_negozio(IN _codice_negozio uuid, IN _codice_prodotto uuid, IN _nuovo_prezzo numeric)
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- TOC entry 233 (class 1255 OID 19431)
-- Name: update_quantita_prodotto_as_fornitore(character varying, uuid, integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.update_quantita_prodotto_as_fornitore(IN _partita_iva character varying, IN _codice_prodotto uuid, IN _quantita integer)
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF _quantita < 0 THEN
        RAISE EXCEPTION 'La quantità da aggiungere non può essere negativa';
    END IF;
    UPDATE venduto_da
    SET quantita = quantita + _quantita
    WHERE partita_iva = _partita_iva AND codice_prodotto = _codice_prodotto;
END;
$$;


--
-- TOC entry 242 (class 1255 OID 19465)
-- Name: update_saldo_tessera(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_saldo_tessera() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
BEGIN
    UPDATE tessera_fedelta
    SET saldo_punti = saldo_punti + FLOOR(NEW.totale_pagato)
    WHERE codice_fiscale = NEW.codice_fiscale AND dismessa = FALSE;
    RETURN NEW;
END;
$$;


--
-- TOC entry 257 (class 1255 OID 19437)
-- Name: update_totale_fattura(uuid, character varying, integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.update_totale_fattura(IN _codice_fattura uuid, IN _codice_fiscale character varying, IN _punti_utilizzati integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    _sconto_percentuale NUMERIC := 0;
    _fattura_totale NUMERIC;
    _sconto_euro NUMERIC := 0;
    _nuovo_totale NUMERIC;
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
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 226 (class 1259 OID 19405)
-- Name: emette; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.emette (
    codice_negozio uuid NOT NULL,
    codice_prodotto uuid NOT NULL,
    codice_fattura uuid NOT NULL,
    prezzo numeric(10,2) NOT NULL,
    quantita_acquistata bigint NOT NULL,
    CONSTRAINT emette_prezzo_check CHECK ((prezzo >= (0)::numeric)),
    CONSTRAINT emette_quantita_acquistata_check CHECK ((quantita_acquistata > 0))
);


--
-- TOC entry 223 (class 1259 OID 19358)
-- Name: fattura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fattura (
    codice_fattura uuid DEFAULT gen_random_uuid() NOT NULL,
    data_acquisto date NOT NULL,
    totale numeric(10,2) NOT NULL,
    sconto_percentuale double precision,
    totale_pagato numeric(10,2) NOT NULL,
    codice_fiscale character varying(16) NOT NULL,
    CONSTRAINT fattura_check CHECK (((totale_pagato >= (0)::numeric) AND (totale_pagato <= totale))),
    CONSTRAINT fattura_totale_check CHECK ((totale >= (0)::numeric))
);


--
-- TOC entry 219 (class 1259 OID 19304)
-- Name: fornitore; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fornitore (
    partita_iva character varying(11) NOT NULL,
    indirizzo character varying(100) NOT NULL
);


--
-- TOC entry 217 (class 1259 OID 19287)
-- Name: negozio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.negozio (
    codice_negozio uuid DEFAULT gen_random_uuid() NOT NULL,
    indirizzo character varying(100) NOT NULL,
    orario_apertura character varying(500) NOT NULL,
    nominativo_responsabile character varying(30) NOT NULL,
    dismesso boolean DEFAULT false NOT NULL
);


--
-- TOC entry 220 (class 1259 OID 19309)
-- Name: ordine; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ordine (
    numero_ordine uuid DEFAULT gen_random_uuid() NOT NULL,
    data_ordine date NOT NULL,
    data_consegna date,
    totale numeric(10,2) NOT NULL,
    codice_negozio uuid NOT NULL,
    codice_prodotto uuid NOT NULL,
    partita_iva character varying(11) NOT NULL,
    quantita_ordinata bigint NOT NULL,
    CONSTRAINT ordine_quantita_ordinata_check CHECK ((quantita_ordinata > 0)),
    CONSTRAINT ordine_totale_check CHECK ((totale >= (0)::numeric))
);


--
-- TOC entry 218 (class 1259 OID 19296)
-- Name: prodotto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prodotto (
    codice_prodotto uuid DEFAULT gen_random_uuid() NOT NULL,
    nome character varying(100) NOT NULL,
    descrizione character varying(500) NOT NULL
);


--
-- TOC entry 222 (class 1259 OID 19340)
-- Name: tessera_fedelta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tessera_fedelta (
    codice_tessera uuid DEFAULT gen_random_uuid() NOT NULL,
    data_richiesta date NOT NULL,
    saldo_punti bigint NOT NULL,
    dismessa boolean DEFAULT false NOT NULL,
    codice_negozio uuid NOT NULL,
    codice_fiscale character varying(16) NOT NULL,
    CONSTRAINT tessera_fedelta_saldo_punti_check CHECK ((saldo_punti >= 0))
);


--
-- TOC entry 221 (class 1259 OID 19332)
-- Name: utente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.utente (
    codice_fiscale character varying(16) NOT NULL,
    email character varying(70) NOT NULL,
    password character varying(20) NOT NULL,
    ruolo character varying(7) NOT NULL,
    nome character varying(30) NOT NULL,
    cognome character varying(30) NOT NULL,
    provincia character varying(30) NOT NULL,
    citta character varying(30) NOT NULL,
    via character varying(30) NOT NULL,
    civico character varying(5) NOT NULL,
    CONSTRAINT utente_ruolo_check CHECK (((ruolo)::text = ANY ((ARRAY['cliente'::character varying, 'manager'::character varying])::text[])))
);


--
-- TOC entry 224 (class 1259 OID 19371)
-- Name: vende; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vende (
    codice_negozio uuid NOT NULL,
    codice_prodotto uuid NOT NULL,
    prezzo numeric(10,2) NOT NULL,
    quantita bigint NOT NULL,
    CONSTRAINT vende_prezzo_check CHECK ((prezzo >= (0)::numeric)),
    CONSTRAINT vende_quantita_check CHECK ((quantita >= 0))
);


--
-- TOC entry 225 (class 1259 OID 19388)
-- Name: venduto_da; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.venduto_da (
    codice_prodotto uuid NOT NULL,
    partita_iva character varying(11) NOT NULL,
    prezzo numeric(10,2) NOT NULL,
    quantita bigint NOT NULL,
    CONSTRAINT venduto_da_prezzo_check CHECK ((prezzo >= (0)::numeric)),
    CONSTRAINT venduto_da_quantita_check CHECK ((quantita >= 0))
);


--
-- TOC entry 227 (class 1259 OID 19471)
-- Name: view_clienti_almeno_300_punti; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_clienti_almeno_300_punti AS
 SELECT u.codice_fiscale,
    u.nome,
    u.cognome,
    u.email,
    t.codice_tessera,
    t.codice_negozio,
    t.saldo_punti,
    t.data_richiesta,
    t.dismessa
   FROM (public.utente u
     JOIN public.tessera_fedelta t ON (((u.codice_fiscale)::text = (t.codice_fiscale)::text)))
  WHERE (t.saldo_punti > 300);


--
-- TOC entry 228 (class 1259 OID 19476)
-- Name: view_tessere_dismesse; Type: MATERIALIZED VIEW; Schema: public; Owner: -
--

CREATE MATERIALIZED VIEW public.view_tessere_dismesse AS
 SELECT codice_tessera,
    data_richiesta,
    saldo_punti,
    dismessa,
    codice_negozio,
    codice_fiscale
   FROM public.tessera_fedelta
  WHERE (dismessa = true)
  WITH NO DATA;


--
-- TOC entry 3747 (class 0 OID 19405)
-- Dependencies: 226
-- Data for Name: emette; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3744 (class 0 OID 19358)
-- Dependencies: 223
-- Data for Name: fattura; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3740 (class 0 OID 19304)
-- Dependencies: 219
-- Data for Name: fornitore; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3738 (class 0 OID 19287)
-- Dependencies: 217
-- Data for Name: negozio; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3741 (class 0 OID 19309)
-- Dependencies: 220
-- Data for Name: ordine; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3739 (class 0 OID 19296)
-- Dependencies: 218
-- Data for Name: prodotto; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3743 (class 0 OID 19340)
-- Dependencies: 222
-- Data for Name: tessera_fedelta; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3742 (class 0 OID 19332)
-- Dependencies: 221
-- Data for Name: utente; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.utente VALUES ('RSSGFR90D17L175V', 'gianfranco.rossi@gmail.com', 'gattopardo45', 'manager', 'gianfranco', 'rossi', 'sondrio', 'tirano', 'viale italia', '7');
INSERT INTO public.utente VALUES ('FRRMRA00E15F205B', 'mauro.ferrari@gmail.com', 'milano88!', 'cliente', 'mauro', 'ferrari', 'milano', 'milano', 'celoria', '18');


--
-- TOC entry 3745 (class 0 OID 19371)
-- Dependencies: 224
-- Data for Name: vende; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3746 (class 0 OID 19388)
-- Dependencies: 225
-- Data for Name: venduto_da; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3572 (class 2606 OID 19411)
-- Name: emette emette_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.emette
    ADD CONSTRAINT emette_pkey PRIMARY KEY (codice_negozio, codice_prodotto, codice_fattura);


--
-- TOC entry 3566 (class 2606 OID 19365)
-- Name: fattura fattura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fattura
    ADD CONSTRAINT fattura_pkey PRIMARY KEY (codice_fattura);


--
-- TOC entry 3556 (class 2606 OID 19308)
-- Name: fornitore fornitore_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fornitore
    ADD CONSTRAINT fornitore_pkey PRIMARY KEY (partita_iva);


--
-- TOC entry 3552 (class 2606 OID 19295)
-- Name: negozio negozio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.negozio
    ADD CONSTRAINT negozio_pkey PRIMARY KEY (codice_negozio);


--
-- TOC entry 3558 (class 2606 OID 19316)
-- Name: ordine ordine_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordine
    ADD CONSTRAINT ordine_pkey PRIMARY KEY (numero_ordine);


--
-- TOC entry 3554 (class 2606 OID 19303)
-- Name: prodotto prodotto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prodotto
    ADD CONSTRAINT prodotto_pkey PRIMARY KEY (codice_prodotto);


--
-- TOC entry 3564 (class 2606 OID 19347)
-- Name: tessera_fedelta tessera_fedelta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tessera_fedelta
    ADD CONSTRAINT tessera_fedelta_pkey PRIMARY KEY (codice_tessera);


--
-- TOC entry 3560 (class 2606 OID 19339)
-- Name: utente utente_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.utente
    ADD CONSTRAINT utente_email_key UNIQUE (email);


--
-- TOC entry 3562 (class 2606 OID 19337)
-- Name: utente utente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.utente
    ADD CONSTRAINT utente_pkey PRIMARY KEY (codice_fiscale);


--
-- TOC entry 3568 (class 2606 OID 19377)
-- Name: vende vende_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vende
    ADD CONSTRAINT vende_pkey PRIMARY KEY (codice_negozio, codice_prodotto);


--
-- TOC entry 3570 (class 2606 OID 19394)
-- Name: venduto_da venduto_da_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.venduto_da
    ADD CONSTRAINT venduto_da_pkey PRIMARY KEY (partita_iva, codice_prodotto);


--
-- TOC entry 3587 (class 2620 OID 19470)
-- Name: ordine i_data_consegna_ordine_is_not_empty; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER i_data_consegna_ordine_is_not_empty BEFORE INSERT ON public.ordine FOR EACH ROW EXECUTE FUNCTION public.data_consegna_ordine_is_not_empty();


--
-- TOC entry 3589 (class 2620 OID 19464)
-- Name: tessera_fedelta i_tessera_gia_presente; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER i_tessera_gia_presente BEFORE INSERT ON public.tessera_fedelta FOR EACH ROW EXECUTE FUNCTION public.tessera_gia_presente();


--
-- TOC entry 3588 (class 2620 OID 19462)
-- Name: ordine i_update_disponibilita_as_fornitore; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER i_update_disponibilita_as_fornitore AFTER INSERT ON public.ordine FOR EACH ROW EXECUTE FUNCTION public.update_disponibilita_as_fornitore();


--
-- TOC entry 3590 (class 2620 OID 19466)
-- Name: fattura i_update_saldo_tessera; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER i_update_saldo_tessera AFTER INSERT ON public.fattura FOR EACH ROW EXECUTE FUNCTION public.update_saldo_tessera();


--
-- TOC entry 3586 (class 2620 OID 19468)
-- Name: negozio u_refresh_view_tessere_dismesse; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER u_refresh_view_tessere_dismesse AFTER UPDATE OF dismesso ON public.negozio FOR EACH ROW EXECUTE FUNCTION public.refresh_view_tessere_dismesse();


--
-- TOC entry 3583 (class 2606 OID 19422)
-- Name: emette emette_codice_fattura_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.emette
    ADD CONSTRAINT emette_codice_fattura_fkey FOREIGN KEY (codice_fattura) REFERENCES public.fattura(codice_fattura);


--
-- TOC entry 3584 (class 2606 OID 19412)
-- Name: emette emette_codice_negozio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.emette
    ADD CONSTRAINT emette_codice_negozio_fkey FOREIGN KEY (codice_negozio) REFERENCES public.negozio(codice_negozio);


--
-- TOC entry 3585 (class 2606 OID 19417)
-- Name: emette emette_codice_prodotto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.emette
    ADD CONSTRAINT emette_codice_prodotto_fkey FOREIGN KEY (codice_prodotto) REFERENCES public.prodotto(codice_prodotto);


--
-- TOC entry 3578 (class 2606 OID 19366)
-- Name: fattura fattura_codice_fiscale_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fattura
    ADD CONSTRAINT fattura_codice_fiscale_fkey FOREIGN KEY (codice_fiscale) REFERENCES public.utente(codice_fiscale);


--
-- TOC entry 3573 (class 2606 OID 19317)
-- Name: ordine ordine_codice_negozio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordine
    ADD CONSTRAINT ordine_codice_negozio_fkey FOREIGN KEY (codice_negozio) REFERENCES public.negozio(codice_negozio);


--
-- TOC entry 3574 (class 2606 OID 19322)
-- Name: ordine ordine_codice_prodotto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordine
    ADD CONSTRAINT ordine_codice_prodotto_fkey FOREIGN KEY (codice_prodotto) REFERENCES public.prodotto(codice_prodotto);


--
-- TOC entry 3575 (class 2606 OID 19327)
-- Name: ordine ordine_partita_iva_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordine
    ADD CONSTRAINT ordine_partita_iva_fkey FOREIGN KEY (partita_iva) REFERENCES public.fornitore(partita_iva);


--
-- TOC entry 3576 (class 2606 OID 19353)
-- Name: tessera_fedelta tessera_fedelta_codice_fiscale_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tessera_fedelta
    ADD CONSTRAINT tessera_fedelta_codice_fiscale_fkey FOREIGN KEY (codice_fiscale) REFERENCES public.utente(codice_fiscale);


--
-- TOC entry 3577 (class 2606 OID 19348)
-- Name: tessera_fedelta tessera_fedelta_codice_negozio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tessera_fedelta
    ADD CONSTRAINT tessera_fedelta_codice_negozio_fkey FOREIGN KEY (codice_negozio) REFERENCES public.negozio(codice_negozio);


--
-- TOC entry 3579 (class 2606 OID 19378)
-- Name: vende vende_codice_negozio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vende
    ADD CONSTRAINT vende_codice_negozio_fkey FOREIGN KEY (codice_negozio) REFERENCES public.negozio(codice_negozio);


--
-- TOC entry 3580 (class 2606 OID 19383)
-- Name: vende vende_codice_prodotto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vende
    ADD CONSTRAINT vende_codice_prodotto_fkey FOREIGN KEY (codice_prodotto) REFERENCES public.prodotto(codice_prodotto);


--
-- TOC entry 3581 (class 2606 OID 19395)
-- Name: venduto_da venduto_da_codice_prodotto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.venduto_da
    ADD CONSTRAINT venduto_da_codice_prodotto_fkey FOREIGN KEY (codice_prodotto) REFERENCES public.prodotto(codice_prodotto);


--
-- TOC entry 3582 (class 2606 OID 19400)
-- Name: venduto_da venduto_da_partita_iva_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.venduto_da
    ADD CONSTRAINT venduto_da_partita_iva_fkey FOREIGN KEY (partita_iva) REFERENCES public.fornitore(partita_iva);


--
-- TOC entry 3748 (class 0 OID 19476)
-- Dependencies: 228 3750
-- Name: view_tessere_dismesse; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: -
--

REFRESH MATERIALIZED VIEW public.view_tessere_dismesse;


-- Completed on 2026-01-22 17:13:43 CET

--
-- PostgreSQL database dump complete
--

\unrestrict WlzsyaZwdPmuIxCmBhNOPG0YtNgJINFfPJbPJy9fcJbbQB0Kdn0OZr6A3QJC1Px

