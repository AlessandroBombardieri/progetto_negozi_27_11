/* Tabelle. */

CREATE TABLE negozio(
    codice_negozio UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    indirizzo VARCHAR(100) NOT NULL,
    orario_apertura VARCHAR(500) NOT NULL,
    nominativo_responsabile VARCHAR(30) NOT NULL,
    dismesso BOOL NOT NULL DEFAULT FALSE
);

CREATE TABLE prodotto(
    codice_prodotto UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nome VARCHAR(100) NOT NULL,
    descrizione VARCHAR(500) NOT NULL
);

CREATE TABLE fornitore(
    partita_iva VARCHAR(11) PRIMARY KEY,
    indirizzo VARCHAR(100) NOT NULL
);

CREATE TABLE ordine(
    numero_ordine UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    data_ordine DATE NOT NULL,
    data_consegna DATE,
    totale NUMERIC(10,2) NOT NULL CHECK(totale >= 0),
    codice_negozio UUID NOT NULL REFERENCES negozio(codice_negozio),
    codice_prodotto UUID NOT NULL REFERENCES prodotto(codice_prodotto),
    partita_iva VARCHAR(11) NOT NULL REFERENCES fornitore(partita_iva),
    quantita_ordinata INT8 NOT NULL CHECK(quantita_ordinata > 0)
);

CREATE TABLE utente(
    codice_fiscale VARCHAR(16) PRIMARY KEY,
    email VARCHAR(70) NOT NULL UNIQUE,
    password VARCHAR(20) NOT NULL,
    ruolo VARCHAR(7) NOT NULL CHECK(ruolo IN ('cliente', 'manager')),
    nome VARCHAR(30) NOT NULL,
    cognome VARCHAR(30) NOT NULL,
    provincia VARCHAR(30) NOT NULL,
    citta VARCHAR(30) NOT NULL,
    via VARCHAR(30) NOT NULL,
    civico VARCHAR(5) NOT NULL
);

CREATE TABLE tessera_fedelta(
    codice_tessera UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    data_richiesta DATE NOT NULL,
    saldo_punti INT8 NOT NULL CHECK(saldo_punti >= 0),
    dismessa BOOL NOT NULL DEFAULT FALSE,
    codice_negozio UUID NOT NULL REFERENCES negozio(codice_negozio),
    codice_fiscale VARCHAR(16) NOT NULL REFERENCES utente(codice_fiscale)
);

CREATE TABLE fattura(
    codice_fattura UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    data_acquisto DATE NOT NULL,
    totale NUMERIC(10,2) NOT NULL CHECK(totale >= 0),
    sconto_percentuale FLOAT8,
    totale_pagato NUMERIC(10,2) NOT NULL CHECK(totale_pagato >= 0 AND totale_pagato <= totale),
    codice_fiscale VARCHAR(16) NOT NULL REFERENCES utente(codice_fiscale)
);

CREATE TABLE vende(
    codice_negozio UUID REFERENCES negozio(codice_negozio),
    codice_prodotto UUID REFERENCES prodotto(codice_prodotto),
    prezzo NUMERIC(10,2) NOT NULL CHECK(prezzo >= 0),
    quantita INT8 NOT NULL CHECK(quantita >= 0),
    PRIMARY KEY(codice_negozio, codice_prodotto)
);

CREATE TABLE venduto_da(
    codice_prodotto UUID REFERENCES prodotto(codice_prodotto),
    partita_iva VARCHAR(11) REFERENCES fornitore(partita_iva),
    prezzo NUMERIC(10,2) NOT NULL CHECK(prezzo >= 0),
    quantita INT8 NOT NULL CHECK(quantita >= 0),
    PRIMARY KEY(partita_iva, codice_prodotto)
);

CREATE TABLE emette(
    codice_negozio UUID REFERENCES negozio(codice_negozio),
    codice_prodotto UUID REFERENCES prodotto(codice_prodotto),
    codice_fattura UUID REFERENCES fattura(codice_fattura),
    prezzo NUMERIC(10,2) NOT NULL CHECK(prezzo >= 0),
    quantita_acquistata INT8 NOT NULL CHECK(quantita_acquistata > 0),
    PRIMARY KEY(codice_negozio, codice_prodotto, codice_fattura)
);