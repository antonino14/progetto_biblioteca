<?php
include_once 'functions.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'bibliotecario') {
    header('Location: login_bibliotecario.php');
    exit();
}

$db = open_pg_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione'], $_POST['cod_prestito'])) {
        $azione = $_POST['azione'];
        $cod_prestito = $_POST['cod_prestito'];

        if ($azione === 'chiudi') {
            $query = "UPDATE biblioteca.prestito SET data_restituzione = CURRENT_DATE, prestito_aperto = FALSE WHERE cod_prestito = $1";
            $result = pg_prepare($db, "chiudi_prestito", $query);
            if ($result) {
                $result = pg_execute($db, "chiudi_prestito", array($cod_prestito));

                if ($result) {
                    $_SESSION['message'] = "Prestito chiuso con successo.";
                } else {
                    $_SESSION['error'] = "Errore nella chiusura del prestito.";
                }
            } else {
                $_SESSION['error'] = "Errore nella preparazione della query.";
            }
        }
    }
}

$query = "SELECT * FROM biblioteca.prestiti_aperti";
$result = pg_query($db, $query);
$prestiti_aperti = $result ? pg_fetch_all($result) : [];

pg_free_result($result);
close_pg_connection($db);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Prestiti</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1>Gestione Prestiti</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message"> <?= htmlspecialchars($_SESSION['message']) ?> </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"> <?= htmlspecialchars($_SESSION['error']) ?> </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Codice Prestito</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Lettore</th>
                    <th>ISBN Libro</th>
                    <th>Copia</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($prestiti_aperti): ?>
                    <?php foreach ($prestiti_aperti as $prestito): ?>
                        <tr>
                            <td><?= htmlspecialchars($prestito['cod_prestito']) ?></td>
                            <td><?= htmlspecialchars($prestito['data_inizio']) ?></td>
                            <td><?= htmlspecialchars($prestito['data_fine']) ?></td>
                            <td><?= htmlspecialchars($prestito['lettore']) ?></td>
                            <td><?= htmlspecialchars($prestito['libro']) ?></td>
                            <td><?= htmlspecialchars($prestito['copia']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="cod_prestito" value="<?= htmlspecialchars($prestito['cod_prestito']) ?>">
                                    <button type="submit" name="azione" value="chiudi">Chiudi Prestito</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nessun prestito aperto trovato.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
