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
$codice_negozio = $_POST['codice_negozio'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {
    if ($codice_negozio === '' || $codice_negozio === null) {
        $err = "Compila tutti i campi";
    }
    if (!$err) {
        $rows = get_prodotti_by_negozio($codice_negozio);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add_'])) {
    if ($codice_negozio === '' || $codice_negozio === null) {
        $err = "Compila tutti i campi";
    } else {
        $rows = get_prodotti_by_negozio($codice_negozio);
        if (!$rows) {
            $err = "Nessun prodotto.";
        }
    }
    if (!$err) {
        if (!isset($_SESSION['carrello'])) {
            $_SESSION['carrello'] = [
                'codice_negozio' => $codice_negozio,
                'items' => []
            ];
        } elseif ($_SESSION['carrello']['codice_negozio'] !== $codice_negozio) {
            $_SESSION['carrello'] = [
                'codice_negozio' => $codice_negozio,
                'items' => []
            ];
        }
        $quantita = $_POST['qty'] ?? [];
        $prodotti_by_id = [];
        foreach ($rows as $r) {
            $prodotti_by_id[$r['codice_prodotto']] = $r;
        }
        foreach ($quantita as $codice_prodotto => $qty) {
            $qty = (int) $qty;
            if ($qty < 0) {
                $qty = 0;
            }
            if ($qty > 0) {
                if (!isset($prodotti_by_id[$codice_prodotto])) {
                    continue;
                }
                $prod = $prodotti_by_id[$codice_prodotto];
                $max_disponibile = (int) $prod['quantita'];
                if ($qty > $max_disponibile) {
                    $qty = $max_disponibile;
                }
                $_SESSION['carrello']['items'][$codice_prodotto] = [
                    'nome' => $prod['nome'],
                    'prezzo' => (float) $prod['prezzo'],
                    'qty' => $qty,
                ];
            } else {
                unset($_SESSION['carrello']['items'][$codice_prodotto]);
            }
        }
        $ok = "Carrello aggiornato.";
    }
}
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
        <?php if ($ok && !$err): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Prodotti per negozio</h2>
                <form method="post" class="row g-2 mb-3">
                    <div class="col-md-8">
                        <select name="codice_negozio" class="form-select" required>
                            <option value="" disabled selected>Seleziona un negozio</option>
                            <?php foreach ($negozi as $r): ?>
                                <option value="<?= htmlspecialchars($r['codice_negozio']) ?>">
                                    <?= htmlspecialchars($r['codice_negozio']) ?> —
                                    <?= htmlspecialchars($r['indirizzo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- <select name="codice_negozio" class="form-select" required>
                            <option value="" disabled <?= $codice_negozio ? '' : 'selected' ?>>Seleziona un negozio
                            </option>
                            <?php foreach ($negozi as $r): ?>
                                <option value="<?= htmlspecialchars($r['codice_negozio']) ?>"
                                    <?= ($codice_negozio === $r['codice_negozio']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['codice_negozio']) ?> —
                                    <?= htmlspecialchars($r['indirizzo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select> -->
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" name="submit_add" value="1">Mostra</button>
                    </div>
                </form>

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
                                        $in_carrello = 0;
                                        if (isset($_SESSION['carrello']['items'][$r['codice_prodotto']])) {
                                            $in_carrello = $_SESSION['carrello']['items'][$r['codice_prodotto']]['qty'];
                                        }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['codice_prodotto']) ?></td>
                                            <td><?= htmlspecialchars($r['nome']) ?></td>
                                            <td><?= htmlspecialchars($r['descrizione']) ?></td>
                                            <td><?= htmlspecialchars($r['prezzo']) ?></td>
                                            <td><?= htmlspecialchars($r['quantita']) ?></td>
                                            <td style="max-width: 120px;">
                                                <input type="number" class="form-control form-control-sm"
                                                    name="qty[<?= htmlspecialchars($r['codice_prodotto']) ?>]" min="0"
                                                    max="<?= (int) $r['quantita'] ?>" value="<?= (int) $in_carrello ?>">
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