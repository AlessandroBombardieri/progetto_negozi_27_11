/* Triggers. */

/* Prodotti. */

/* 3.3.4. Aggiornamento disponibilità prodotti dai fornitori. La disponibilità dei prodotti dai vari fornitori
è ovviamente limitata (e comunicata da ciascun fornitore alla catena di negozi).
In seguito ad un ordine di un certo prodotto presso un certo fornitore, è necessario mantenere aggiornata
la disponibilità di quel prodotto da quel fornitore. */
CREATE OR REPLACE FUNCTION update_disponibilita_as_fornitore()
RETURNS TRIGGER AS $$
DECLARE
    _prezzo_fornitore FLOAT8;
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
$$ LANGUAGE plpgsql;

CREATE TRIGGER i_update_disponibilita_as_fornitore
AFTER INSERT ON ordine
FOR EACH ROW
EXECUTE FUNCTION update_disponibilita_as_fornitore();

/* Tessere fedeltà. */

/* Permette di impedire la creazione di una nuova tessera fedeltà se un utente ne possiede già una attiva. */
CREATE OR REPLACE FUNCTION tessera_gia_presente()
RETURNS TRIGGER AS $$
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
$$ LANGUAGE plpgsql;

CREATE TRIGGER i_tessera_gia_presente
BEFORE INSERT ON tessera_fedelta
FOR EACH ROW
EXECUTE FUNCTION tessera_gia_presente();

/* 3.3.1. Aggiornamento saldo punti su tessera fedeltà. Per ogni Euro speso, viene accumulato un punto
sulla tessera del cliente che effettua la spesa. Il saldo punti su ogni tessera deve essere continuamente aggiornato. */
CREATE OR REPLACE FUNCTION update_saldo_tessera()
RETURNS TRIGGER AS $$
DECLARE
BEGIN
    UPDATE tessera_fedelta
    SET saldo_punti = saldo_punti + FLOOR(NEW.totale_pagato)
    WHERE codice_fiscale = NEW.codice_fiscale AND dismessa = FALSE;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER i_update_saldo_tessera
AFTER INSERT ON fattura
FOR EACH ROW
EXECUTE FUNCTION update_saldo_tessera();

/* 3.3.3. Mantenimento storico tessere. Quando un negozio viene eliminato, è necessario mantenere
in una tabella di storico le informazioni sulle tessere che erano state emesse dal negozio stesso,
con la data di emissione. */

/* Si occupa di aggiornare la vista materializzata relativa alle tessere fedeltà dismesse attivandosi non appena un negozio venga dismesso. */
CREATE OR REPLACE FUNCTION refresh_view_tessere_dismesse()
RETURNS TRIGGER AS $$
BEGIN
    -- Se il negozio è appena stato dismesso, aggiorna la MV
    IF NEW.dismesso = TRUE AND OLD.dismesso = FALSE THEN
        REFRESH MATERIALIZED VIEW view_tessere_dismesse;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER u_refresh_view_tessere_dismesse
AFTER UPDATE OF dismesso ON negozio
FOR EACH ROW
EXECUTE FUNCTION refresh_view_tessere_dismesse();

/* Ordini. */

/* Permette di impedire la creazione di un ordine se la data di consegna dovesse già venir selezionata. */
CREATE OR REPLACE FUNCTION data_consegna_ordine_is_not_empty()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.data_consegna IS NOT NULL THEN
        RAISE EXCEPTION 'La data di consegna non può essere impostata al momento dell''ordine';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER i_data_consegna_ordine_is_not_empty
BEFORE INSERT ON ordine
FOR EACH ROW
EXECUTE FUNCTION data_consegna_ordine_is_not_empty();