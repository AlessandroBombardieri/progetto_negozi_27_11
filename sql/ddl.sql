create table negozio(
	codice_negozio uuid primary key default gen_random_uuid(),
	indirizzo varchar(100) not null,
	orario_apertura varchar(500) not null,
	nominativo_responsabile varchar(30) not null,
	dismesso bool not null default false
);

create table prodotto(
	codice_prodotto uuid primary key default gen_random_uuid(),
	nome varchar(100) not null,
	descrizione varchar(500) not null
);

create table fornitore(
	partita_iva varchar(11) primary key,
	indirizzo varchar(100) not null
);

create table ordine(
	numero_ordine uuid primary key default gen_random_uuid(),
	data_ordine date not null,
	data_consegna date,
	totale numeric(10,2) not null check(totale >= 0),
	codice_negozio uuid not null references negozio(codice_negozio),
	codice_prodotto uuid not null references prodotto(codice_prodotto),
	partita_iva varchar(11) not null references fornitore(partita_iva),
	quantita_ordinata int8 not null check (quantita_ordinata > 0)
);

create table utente(
    codice_fiscale varchar(16) primary key,
    email varchar(70) not null unique,
    password varchar(20) not null,
    ruolo varchar(7) not null check (ruolo in ('cliente', 'manager')),
    nome varchar(30) not null,
    cognome varchar(30) not null,
    provincia varchar(30) not null,
    citta varchar(30) not null,
    via varchar(30) not null,
    civico varchar(5) not null
);

create table tessera_fedelta(
	codice_tessera uuid primary key default gen_random_uuid(),
	data_richiesta date not null,
	saldo_punti int8 not null check(saldo_punti >= 0),
	dismessa bool not null default false,
	codice_negozio uuid not null references negozio(codice_negozio),
	codice_fiscale varchar(16) not null references utente(codice_fiscale)
);

create table fattura(
	codice_fattura uuid primary key default gen_random_uuid(),
	data_acquisto date not null,
	totale numeric(10,2) not null check (totale >= 0),
	sconto_percentuale float8,
	totale_pagato numeric(10,2) not null check (totale_pagato >= 0 and totale_pagato <= totale),
	codice_fiscale varchar(16) not null references utente(codice_fiscale)
);

create table vende(
	codice_negozio uuid references negozio(codice_negozio),
	codice_prodotto uuid references prodotto(codice_prodotto),
	prezzo numeric(10,2) not null check (prezzo >= 0),
	quantita int8 not null check (quantita >= 0),
	primary key(codice_negozio, codice_prodotto)
);

create table venduto_da(
	codice_prodotto uuid references prodotto(codice_prodotto),
	partita_iva varchar(11) references fornitore(partita_iva),
	prezzo numeric(10,2) not null check (prezzo >= 0),
	quantita int8 not null check (quantita >= 0),
	primary key(partita_iva, codice_prodotto)
);

create table emette(
	codice_negozio uuid references negozio(codice_negozio),
	codice_prodotto uuid references prodotto(codice_prodotto),
	codice_fattura uuid references fattura(codice_fattura),
	prezzo numeric(10,2) not null check (prezzo >= 0),
	quantita_acquistata int8 not null check (quantita_acquistata > 0),
	primary key(codice_negozio, codice_prodotto, codice_fattura)
);	