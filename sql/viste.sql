/* Viste. */

/* Utenti/Clienti. */

/* 3.3.8. Saldi punti. E' necessario mantenere un elenco aggiornato dei clienti che hanno una tessera fedeltà con un saldo punti superiore a 300 punti. */
CREATE VIEW view_clienti_almeno_300_punti AS
SELECT u.codice_fiscale, u.nome, u.cognome, u.email, t.codice_tessera, t.codice_negozio, t.saldo_punti, t.data_richiesta, t.dismessa
FROM utente u INNER JOIN tessera_fedelta t ON u.codice_fiscale = t.codice_fiscale
WHERE t.saldo_punti > 300;

/* Tessere fedeltà. */

/* 3.3.3. Mantenimento storico tessere. Quando un negozio viene eliminato, è necessario mantenere
in una tabella di storico le informazioni sulle tessere che erano state emesse dal negozio stesso,
con la data di emissione. */
CREATE MATERIALIZED VIEW view_tessere_dismesse AS
SELECT *
FROM tessera_fedelta
WHERE dismessa = TRUE;