<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('../../lib/functions.php'); // adatta il path se serve
session_start();
if (!isset($_SESSION['utente']) || $_SESSION['utente']['ruolo'] !== 'cliente') {
    redirect('../home.php');
}
$ok = $err = null;
$carrello = $_SESSION['carrello'] ?? null;
$codice_negozio = $carrello['codice_negozio'] ?? null;
$items = $carrello['items'] ?? [];
$carrello_vuoto = empty($items);
$codice_fiscale = $_SESSION['utente']['codice_fiscale'] ?? null;
function calcola_totale_carrello(array $items): float {
    $tot = 0.0;
    foreach ($items as $it) {
        $tot += $it['prezzo'] * $it['qty'];
    }
    return $tot;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['svuota'])) {
        unset($_SESSION['carrello']);
        $carrello = null;
        $items = [];
        $carrello_vuoto = true;
        $ok = "Carrello svuotato.";
    }
    elseif (isset($_POST['aggiorna'])) {
        if (!$codice_negozio) {
            $err = "Errore: nessun negozio associato al carrello.";
        } else {
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
                if ($qty < 0) $qty = 0;
                if ($qty === 0) {
                    continue;
                }
                if (!isset($prodotti_by_id[$codice_prodotto])) {
                    continue;
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
    elseif (isset($_POST['acquista'])) {
        if ($carrello_vuoto) {
            $err = "Il carrello è vuoto.";
        } elseif (!$codice_negozio) {
            $err = "Errore: nessun negozio associato al carrello.";
        } elseif (!$codice_fiscale) {
            $err = "Errore: utente non riconosciuto.";
        } else {
            $totale = calcola_totale_carrello($items);
            $tessera = get_tessera_non_dismessa_by_utente($codice_fiscale);
            if (!$tessera) {
                $err = "Non hai una tessera fedeltà attiva.";
            } else {
                $saldo_punti = (int)$tessera['saldo_punti'];
                $sconto_perc = isset($_POST['sconto']) ? (int)$_POST['sconto'] : 0;
                $punti_da_scalare = 0;
                if ($sconto_perc === 5)  $punti_da_scalare = 100;
                if ($sconto_perc === 15) $punti_da_scalare = 200;
                if ($sconto_perc === 30) $punti_da_scalare = 300;
                if ($punti_da_scalare > 0 && $saldo_punti < $punti_da_scalare) {
                    $err = "Punti insufficienti per lo sconto selezionato.";
                } else {
                    $sconto_euro = $totale * ($sconto_perc / 100.0);
                    if ($sconto_euro > 100) {
                        $sconto_euro = 100;
                    }
                    $totale_pagato = $totale - $sconto_euro;
                    $db = dbConnection();
                    pg_query($db, "BEGIN");
                    try {
                        $sql_fatt = "INSERT INTO fattura(data_acquisto, totale, sconto_percentuale, totale_pagato, codice_fiscale)
                                     VALUES (CURRENT_DATE, $1, $2, $3, $4)
                                     RETURNING codice_fattura";
                        $res_f = pg_query_params($db, $sql_fatt, [
                            $totale,
                            $sconto_perc ?: null,
                            $totale_pagato,
                            $codice_fiscale
                        ]);
                        if (!$res_f) {
                            throw new Exception(pg_last_error($db));
                        }
                        $fatt = pg_fetch_assoc($res_f);
                        $codice_fattura = $fatt['codice_fattura'];
                        foreach ($items as $codice_prodotto => $it) {
                            $prezzo = $it['prezzo'];
                            $qty    = $it['qty'];
                            $sql_em = "INSERT INTO emette(codice_negozio, codice_prodotto, codice_fattura, prezzo, quantita_acquistata)
                                       VALUES ($1, $2, $3, $4, $5)";
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
                            $sql_stock = "UPDATE vende
                                          SET quantita = quantita - $1
                                          WHERE codice_negozio = $2 AND codice_prodotto = $3";
                            $ok_st = pg_query_params($db, $sql_stock, [
                                $qty,
                                $codice_negozio,
                                $codice_prodotto
                            ]);
                            if (!$ok_st) {
                                throw new Exception(pg_last_error($db));
                            }
                        }
                        if ($punti_da_scalare > 0) {
                            $sql_pts = "UPDATE tessera_fedelta
                                        SET saldo_punti = saldo_punti - $1
                                        WHERE codice_fiscale = $2
                                          AND codice_negozio = $3
                                          AND dismessa = FALSE";
                            $ok_pts = pg_query_params($db, $sql_pts, [
                                $punti_da_scalare,
                                $codice_fiscale,
                                $codice_negozio
                            ]);
                            if (!$ok_pts) {
                                throw new Exception(pg_last_error($db));
                            }
                        }
                        pg_query($db, "COMMIT");
                        pg_close($db);
                        unset($_SESSION['carrello']);
                        $carrello_vuoto = true;
                        $items = [];
                        $ok = "Acquisto completato. Codice fattura: " . htmlspecialchars($codice_fattura);
                    } catch (Exception $e) {
                        pg_query($db, "ROLLBACK");
                        pg_close($db);
                        $err = "Errore durante la conferma dell'acquisto: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Ricalcola totale per la vista
$totale_carrello = calcola_totale_carrello($items);

// Calcolo sconti disponibili in base al saldo punti (solo per vista)
$saldo_punti = 0;
$tessera = null;
if (!$carrello_vuoto && $codice_fiscale) {
    $tessera = get_tessera_non_dismessa_by_utente($codice_fiscale);
    if ($tessera) {
        $saldo_punti = (int)$tessera['saldo_punti'];
    }
}
$opzioni_sconto = [0]; // 0 = nessuno sconto
if ($saldo_punti >= 100) $opzioni_sconto[] = 5;
if ($saldo_punti >= 200) $opzioni_sconto[] = 15;
if ($saldo_punti >= 300) $opzioni_sconto[] = 30;
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
                                <td>
                                    <?= number_format($it['prezzo'] * $it['qty'], 2, ',', '.') ?> €
                                </td>
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

        <form method="post" class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Sconti disponibili</h2>
                <p class="mb-1">Saldo punti tessera: <strong><?= (int)$saldo_punti ?></strong></p>
                <p class="text-muted small mb-3">
                    Soglie: 100 punti → 5%, 200 → 15%, 300 → 30%. Lo sconto massimo applicabile è 100 €.
                </p>

                <div class="mb-3">
                    <?php foreach ($opzioni_sconto as $perc): ?>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="radio"
                                   name="sconto"
                                   id="sconto_<?= $perc ?>"
                                   value="<?= $perc ?>"
                                   <?= $perc === 0 ? 'checked' : '' ?>
                                   <?= ($perc > 0 && $saldo_punti < ($perc === 5 ? 100 : ($perc === 15 ? 200 : 300))) ? 'disabled' : '' ?>
                            >
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