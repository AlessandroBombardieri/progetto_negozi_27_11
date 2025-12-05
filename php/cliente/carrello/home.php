<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);

include_once('../../lib/functions.php'); // adatta il path se necessario
session_start();

if (!isset($_SESSION['utente']) || $_SESSION['utente']['ruolo'] !== 'cliente') {
    redirect('../home.php');
}

$ok = $err = null;

// Stato carrello da sessione
$carrello = $_SESSION['carrello'] ?? null;
$codice_negozio = $carrello['codice_negozio'] ?? null;
$items = $carrello['items'] ?? [];
$carrello_vuoto = empty($items);

// Utente
$codice_fiscale = $_SESSION['utente']['codice_fiscale'] ?? null;

// Helper: totale carrello
function calcola_totale_carrello(array $items): float {
    $tot = 0.0;
    foreach ($items as $it) {
        $tot += $it['prezzo'] * $it['qty'];
    }
    return $tot;
}

/*
 * 1) Gestione POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Svuota carrello
    if (isset($_POST['svuota'])) {
        unset($_SESSION['carrello']);
        $carrello = null;
        $items = [];
        $carrello_vuoto = true;
        $ok = "Carrello svuotato.";
    }

    // Aggiorna quantità / rimuovi singoli
    elseif (isset($_POST['aggiorna'])) {
        if (!$codice_negozio) {
            $err = "Errore: nessun negozio associato al carrello.";
        } else {
            // Ricarico i prodotti per validare prezzi/stock
            $rows = get_prodotti_by_negozio($codice_negozio);
            $prodotti_by_id = [];
            foreach ($rows as $r) {
                $prodotti_by_id[$r['codice_prodotto']] = $r;
            }

            $nuovi_items = [];
            $quantita = $_POST['qty'] ?? [];
            if (!is_array($quantita)) {
                $quantita = [];
            }

            foreach ($quantita as $codice_prodotto => $qty) {
                $qty = (int)$qty;
                if ($qty <= 0) {
                    continue; // rimosso
                }

                if (!isset($prodotti_by_id[$codice_prodotto])) {
                    continue; // prodotto non più valido per questo negozio
                }
                $prod = $prodotti_by_id[$codice_prodotto];
                $max_disponibile = (int)$prod['quantita'];
                if ($qty > $max_disponibile) {
                    $qty = $max_disponibile;
                }

                $nuovi_items[$codice_prodotto] = [
                    'nome'   => $prod['nome'],
                    'prezzo' => (float)$prod['prezzo'],
                    'qty'    => $qty,
                ];
            }

            if (empty($nuovi_items)) {
                unset($_SESSION['carrello']);
                $carrello = null;
                $items = [];
                $carrello_vuoto = true;
                $ok = "Carrello vuoto.";
            } else {
                $_SESSION['carrello']['items'] = $nuovi_items;
                $items = $nuovi_items;
                $carrello_vuoto = false;
                $ok = "Carrello aggiornato.";
            }
        }
    }

    // Conferma acquisto
    elseif (isset($_POST['acquista'])) {
        if ($carrello_vuoto) {
            $err = "Il carrello è vuoto.";
        } elseif (!$codice_negozio) {
            $err = "Errore: nessun negozio associato al carrello.";
        } elseif (!$codice_fiscale) {
            $err = "Errore: utente non riconosciuto.";
        } else {
            $totale = calcola_totale_carrello($items);

            // Tessera attiva (se serve a livello di procedura SQL)
            $tessera = get_tessera_non_dismessa_by_utente($codice_fiscale); // funzione che hai/puoi avere in functions.php
            if (!$tessera) {
                $err = "Non hai una tessera fedeltà attiva per questo negozio.";
            } else {
                // Sconto scelto (sarà passato a update_totale_fattura)
                $sconto_perc = isset($_POST['sconto']) ? (int)$_POST['sconto'] : 0;

                $db = open_pg_connection();
                pg_query($db, "BEGIN");

                try {
                    // 1) Crea fattura con totale (senza applicare sconto qui)
                    $sql_fatt = "
                        INSERT INTO fattura(data_acquisto, totale, sconto_percentuale, totale_pagato, codice_fiscale)
                        VALUES (CURRENT_DATE, $1, NULL, $1, $2)
                        RETURNING codice_fattura
                    ";
                    $res_f = pg_query_params($db, $sql_fatt, [$totale, $codice_fiscale]);
                    if (!$res_f) {
                        throw new Exception(pg_last_error($db));
                    }
                    $fatt = pg_fetch_assoc($res_f);
                    $codice_fattura = $fatt['codice_fattura'];

                    // 2) Inserisci righe emette + aggiorna stock
                    foreach ($items as $codice_prodotto => $it) {
                        $prezzo = $it['prezzo'];
                        $qty    = $it['qty'];

                        // riga emette
                        $sql_em = "
                            INSERT INTO emette(codice_negozio, codice_prodotto, codice_fattura, prezzo, quantita_acquistata)
                            VALUES ($1, $2, $3, $4, $5)
                        ";
                        $ok_em = pg_query_params($db, $sql_em, [
                            $codice_negozio,
                            $codice_prodotto,
                            $codice_fattura,
                            $prezzo,
                            $qty
                        ]);
                        if (!$ok_em) {
                            throw new Exception(pg_last_error($db));
                        }

                        // decremento stock da vende
                        $sql_stock = "
                            UPDATE vende
                            SET quantita = quantita - $1
                            WHERE codice_negozio = $2 AND codice_prodotto = $3
                        ";
                        $ok_st = pg_query_params($db, $sql_stock, [
                            $qty,
                            $codice_negozio,
                            $codice_prodotto
                        ]);
                        if (!$ok_st) {
                            throw new Exception(pg_last_error($db));
                        }
                    }

                    // 3) Chiama la tua funzione/procedura SQL update_totale_fattura
                    //    QUI devi usare la firma esatta di update_totale_fattura che hai definito.
                    //    Esempio (da adattare):
                    //
                    //    SELECT update_totale_fattura(codice_fattura, codice_fiscale, codice_negozio, sconto_percentuale);
                    //
                    $sql_update = "SELECT update_totale_fattura($1, $2, $3, $4)";
                    $res_upd = pg_query_params($db, $sql_update, [
                        $codice_fattura,
                        $codice_fiscale,
                        $codice_negozio,
                        $sconto_perc
                    ]);
                    if (!$res_upd) {
                        throw new Exception(pg_last_error($db));
                    }

                    pg_query($db, "COMMIT");
                    close_pg_connection($db);

                    unset($_SESSION['carrello']);
                    $carrello_vuoto = true;
                    $items = [];

                    $ok = "Acquisto completato. Codice fattura: " . htmlspecialchars($codice_fattura);
                } catch (Exception $e) {
                    pg_query($db, "ROLLBACK");
                    close_pg_connection($db);
                    $err = "Errore durante la conferma dell'acquisto: " . $e->getMessage();
                }
            }
        }
    }
}

// 2) Stato per la vista
$totale_carrello = calcola_totale_carrello($items);

// saldo punti + sconti applicabili usando get_sconti_applicabili
$saldo_punti = 0;
$opzioni_sconto = [0]; // 0 = nessuno sconto

if (!$carrello_vuoto && $codice_negozio && $codice_fiscale) {
    // Recupera tessera e saldo per info
    $tessera = get_tessera_non_dismessa_by_utente($codice_fiscale);
    if ($tessera) {
        $saldo_punti = (int)$tessera['saldo_punti'];
    }

    // Usa la tua funzione SQL get_sconti_applicabili
    // Devi adattare la firma esatta: qui suppongo (codice_fiscale, codice_negozio)
    $db2 = open_pg_connection();
    $sql_sconti = "SELECT * FROM get_sconti_applicabili($1, $2)";
    $res_s = pg_query_params($db2, $sql_sconti, [$codice_fiscale, $codice_negozio]);
    if ($res_s) {
        while ($row = pg_fetch_assoc($res_s)) {
            // supponiamo che la funzione ritorni una colonna 'percentuale'
            $perc = (int)$row['percentuale'];
            if (!in_array($perc, $opzioni_sconto, true)) {
                $opzioni_sconto[] = $perc;
            }
        }
    }
    close_pg_connection($db2);
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Carrello</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Carrello</h1>
        <div class="text-end">
            <div class="mb-2">
                <a class="btn btn-outline-secondary me-2" href="../home.php">← Home cliente</a>
                <a class="btn btn-outline-primary" href="../cliente/prodotti/home.php">Torna al catalogo</a>
            </div>
            <div class="fw-semibold">
                Totale: <?= number_format($totale_carrello, 2, ',', '.') ?> €
            </div>
        </div>
    </div>

    <?php if ($err): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($ok && !$err): ?>
        <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <?php if ($carrello_vuoto): ?>
        <div class="alert alert-info">Il carrello è vuoto.</div>
    <?php else: ?>
        <!-- Form aggiornamento carrello -->
        <form method="post" class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Prodotti nel carrello</h2>
                <div class="table-responsive bg-white rounded">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Prezzo unitario</th>
                            <th>Quantità</th>
                            <th>Subtotale</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $codice_prodotto => $it): ?>
                            <tr>
                                <td><?= htmlspecialchars($it['nome']) ?></td>
                                <td><?= number_format($it['prezzo'], 2, ',', '.') ?> €</td>
                                <td style="max-width: 100px;">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="qty[<?= htmlspecialchars($codice_prodotto) ?>]"
                                           min="0"
                                           value="<?= (int)$it['qty'] ?>">
                                </td>
                                <td><?= number_format($it['prezzo'] * $it['qty'], 2, ',', '.') ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <button type="submit" name="aggiorna" value="1" class="btn btn-outline-primary">
                        Aggiorna carrello
                    </button>
                    <button type="submit" name="svuota" value="1" class="btn btn-outline-danger"
                            onclick="return confirm('Svuotare il carrello?');">
                        Svuota carrello
                    </button>
                </div>
            </div>
        </form>

        <!-- Form scelta sconto + acquisto -->
        <form method="post" class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Sconti disponibili</h2>
                <p class="mb-1">Saldo punti tessera: <strong><?= (int)$saldo_punti ?></strong></p>
                <p class="text-muted small mb-3">
                    Gli sconti mostrati sono calcolati dalla funzione get_sconti_applicabili.
                </p>

                <div class="mb-3">
                    <?php foreach ($opzioni_sconto as $perc): ?>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="radio"
                                   name="sconto"
                                   id="sconto_<?= $perc ?>"
                                   value="<?= $perc ?>"
                                   <?= $perc === 0 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sconto_<?= $perc ?>">
                                <?= $perc === 0 ? 'Nessuno sconto' : $perc . '%' ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Totale attuale: <strong><?= number_format($totale_carrello, 2, ',', '.') ?> €</strong>
                    </div>
                    <button type="submit" name="acquista" value="1" class="btn btn-success">
                        Procedi all'acquisto
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>