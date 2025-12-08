/* Triggers. */

/* Prodotti. */

/* 3.2.4. Aggiornamento disponibilità prodotti dai fornitori. La disponibilità dei prodotti dai vari fornitori
è ovviamente limitata (e comunicata da ciascun fornitore alla catena di negozi).
In seguito ad un ordine di un certo prodotto presso un certo fornitore, è necessario mantenere aggiornata
la disponibilità di quel prodotto da quel fornitore. */
CREATE OR REPLACE FUNCTION update_disponibilita_as_fornitore()
RETURNS TRIGGER AS $$
DECLARE
    _prezzo_fornitore FLOAT8;
BEGIN
    -- Recupero il prezzo dell'oggetto ordinato.
    SELECT prezzo
    INTO _prezzo_fornitore
    FROM venduto_da
    WHERE partita_iva = NEW.partita_iva
      AND codice_prodotto = NEW.codice_prodotto;
    -- Se partita iva fornitore oppure codice prodotto non trovati allora segnala errore.
    IF NOT FOUND OR _prezzo_fornitore IS NULL THEN
        RAISE EXCEPTION 'Prezzo non disponibile per fornitore % e prodotto %', NEW.partita_iva, NEW.codice_prodotto;
    END IF;
    -- Aggiorno la quantità di prodotto ordinato in vendita dal fornitore sottraendo la quantità ordinata.
    UPDATE venduto_da
    SET quantita = quantita - NEW.quantita_ordinata
    WHERE partita_iva = NEW.partita_iva
      AND codice_prodotto = NEW.codice_prodotto
      AND quantita >= NEW.quantita_ordinata;
    -- Se partita iva fornitore oppure codice prodotto non trovati allora segnala errore.
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Scorte insufficienti per fornitore % e prodotto % (richieste %, disponibili inferiori)',
                        NEW.partita_iva, NEW.codice_prodotto, NEW.quantita_ordinata;
    END IF;
    -- Aggiorno la tabella vende per il negozio che ha effettuato l'ordine.
    INSERT INTO vende(codice_negozio, codice_prodotto, prezzo, quantita)
    VALUES (NEW.codice_negozio, NEW.codice_prodotto, _prezzo_fornitore, NEW.quantita_ordinata)
    ON CONFLICT (codice_negozio, codice_prodotto)
    DO UPDATE SET quantita = vende.quantita + EXCLUDED.quantita;
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

/* 3.2.1. Aggiornamento saldo punti su tessera fedeltà. Per ogni Euro speso, viene accumulato un punto
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