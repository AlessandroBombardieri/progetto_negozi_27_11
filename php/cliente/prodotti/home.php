<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);

include_once('../../lib/functions.php');
session_start();

if (!isset($_SESSION['utente'])) {
    redirect('../home.php');
}

$ok = $err = null;
$rows = [];
$negozi = get_negozi_non_dismessi();

// messaggio quando il carrello è stato svuotato perché si è cambiato negozio
$carrello_svuotato_msg = null;
if (isset($_SESSION['carrello_svuotato'])) {
    $carrello_svuotato_msg = $_SESSION['carrello_svuotato'];
    unset($_SESSION['carrello_svuotato']);
}

// negozio selezionato nel form (per mostrare prodotti / aggiornare carrello)
$codice_negozio = $_POST['codice_negozio'] ?? null;

/*
 * 1) MOSTRA PRODOTTI PER NEGOZIO (submit_add)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    if ($codice_negozio === '' || $codice_negozio === null) {
        $err = "Compila tutti i campi";
    } else {
        $rows = get_prodotti_by_negozio($codice_negozio);
        if (!$rows) {
            $err = "Nessun prodotto per il negozio selezionato.";
        }
    }
}

/*
 * 2) AGGIORNA CARRELLO (submit_add_)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add_'])) {
    if ($codice_negozio === '' || $codice_negozio === null) {
        $err = "Compila tutti i campi";
    } else {
        $rows = get_prodotti_by_negozio($codice_negozio);
        if (!$rows) {
            $err = "Nessun prodotto per il negozio selezionato.";
        }
    }

    if (!$err) {
        // inizializza carrello se non esiste
        if (!isset($_SESSION['carrello'])) {
            $_SESSION['carrello'] = [
                'codice_negozio' => $codice_negozio,
                'items' => []
            ];
        } elseif ($_SESSION['carrello']['codice_negozio'] !== $codice_negozio) {
            // se il carrello era di un altro negozio, svuota e segnala
            $_SESSION['carrello'] = [
                'codice_negozio' => $codice_negozio,
                'items' => []
            ];
            $_SESSION['carrello_svuotato'] = "Il carrello è stato svuotato perché hai cambiato negozio.";
        }

        // leggi quantità dal form in modo sicuro
        $quantita = $_POST['qty'] ?? [];
        if (!is_array($quantita)) {
            $quantita = [];
        }

        // indicizza i prodotti per codice_prodotto
        $prodotti_by_id = [];
        foreach ($rows as $r) {
            $prodotti_by_id[$r['codice_prodotto']] = $r;
        }

        foreach ($quantita as $codice_prodotto => $qty) {
            $qty = (int)$qty;
            if ($qty < 0) {
                $qty = 0;
            }

            if ($qty > 0) {
                // il prodotto deve appartenere al negozio corrente
                if (!isset($prodotti_by_id[$codice_prodotto])) {
                    continue;
                }
                $prod = $prodotti_by_id[$codice_prodotto];

                // limita alle scorte disponibili
                $max_disponibile = (int)$prod['quantita'];
                if ($qty > $max_disponibile) {
                    $qty = $max_disponibile;
                }

                $_SESSION['carrello']['items'][$codice_prodotto] = [
                    'nome'   => $prod['nome'],
                    'prezzo' => (float)$prod['prezzo'],
                    'qty'    => $qty,
                ];
            } else {
                // qty = 0 → rimuovi dal carrello
                unset($_SESSION['carrello']['items'][$codice_prodotto]);
            }
        }

        $ok = "Carrello aggiornato.";
    }
}

/*
 * 3) Totale carrello corrente
 */
$totale_carrello = 0.0;
if (isset($_SESSION['carrello'])) {
    foreach ($_SESSION['carrello']['items'] as $item) {
        $totale_carrello += $item['prezzo'] * $item['qty'];
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Catalogo prodotti</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Catalogo prodotti</h1>
        <div>
            <a class="btn btn-outline-secondary me-2" href="../home.php">← Home cliente</a>
            <a class="btn btn-outline-primary" href="../carrello/home.php">
                Carrello (<?= number_format($totale_carrello, 2, ',', '.') ?> €)
            </a>
        </div>
    </div>

    <?php if ($err): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($carrello_svuotato_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($carrello_svuotato_msg) ?></div>
    <?php endif; ?>

    <?php if ($ok && !$err): ?>
        <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Prodotti per negozio</h2>

            <!-- Form selezione negozio -->
            <form method="post" class="row g-2 mb-3">
                <div class="col-md-8">
                    <select name="codice_negozio" class="form-select" required>
                        <option value="" disabled <?= $codice_negozio ? '' : 'selected' ?>>Seleziona un negozio</option>
                        <?php foreach ($negozi as $r): ?>
                            <option value="<?= htmlspecialchars($r['codice_negozio']) ?>"
                                <?= ($codice_negozio === $r['codice_negozio']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['codice_negozio']) ?> —
                                <?= htmlspecialchars($r['indirizzo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100" name="submit_add" value="1">Mostra</button>
                </div>
            </form>

            <!-- Tabella prodotti + form aggiornamento carrello -->
            <div class="table-responsive bg-white shadow-sm rounded">
                <form method="post">
                    <input type="hidden" name="codice_negozio" value="<?= htmlspecialchars($codice_negozio) ?>">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Codice prodotto</th>
                            <th>Nome</th>
                            <th>Descrizione</th>
                            <th>Prezzo</th>
                            <th>Quantità disponibile</th>
                            <th>Nel carrello</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows): ?>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                // valore mostrato nell'input = valore attuale nel carrello per QUESTO negozio
                                $in_carrello = 0;
                                if (isset($_SESSION['carrello'])
                                    && $_SESSION['carrello']['codice_negozio'] === $codice_negozio
                                    && isset($_SESSION['carrello']['items'][$r['codice_prodotto']])) {
                                    $in_carrello = (int)$_SESSION['carrello']['items'][$r['codice_prodotto']]['qty'];
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['codice_prodotto']) ?></td>
                                    <td><?= htmlspecialchars($r['nome']) ?></td>
                                    <td><?= htmlspecialchars($r['descrizione']) ?></td>
                                    <td><?= htmlspecialchars($r['prezzo']) ?></td>
                                    <td><?= htmlspecialchars($r['quantita']) ?></td>
                                    <td style="max-width: 120px;">
                                        <input
                                            type="number"
                                            class="form-control form-control-sm"
                                            name="qty[<?= htmlspecialchars($r['codice_prodotto']) ?>]"
                                            min="0"
                                            max="<?= (int)$r['quantita'] ?>"
                                            value="<?= $in_carrello ?>"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nessun prodotto</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($rows): ?>
                        <div class="mt-3 text-end">
                            <button class="btn btn-success" type="submit" name="submit_add_" value="1">
                                Aggiorna carrello
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>