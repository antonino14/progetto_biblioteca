<?php
require_once 'functions.php'; 

// Controllo sessione utente
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'bibliotecario') {
    header("Location: login_bibliotecario.php");
    exit();
}

// Connessione al database
$db = open_pg_connection();
if (!$db) {
    die("Errore nella connessione al database: " . pg_last_error());
}

// Verifica l'esistenza della vista 'prestiti_aperti'
$check_query = "SELECT EXISTS (SELECT 1 FROM pg_views WHERE viewname = 'prestiti_aperti')";
$check_result = pg_query($db, $check_query);
if (!$check_result) {
    die("Errore nel controllo dell'esistenza della vista: " . pg_last_error($db));
}
$check_row = pg_fetch_assoc($check_result);
if (!$check_row['exists']) {
    die("La vista 'prestiti_aperti' non esiste nel database.");
}

try {
    // Gestione restituzione prestito
    if (isset($_POST['restituzione']) && isset($_POST['cod_prestito'])) {
        $query = "UPDATE biblioteca.prestito SET data_restituzione = CURRENT_DATE WHERE cod_prestito = $1;";
        $result = pg_prepare($db, "fine_prestito", $query);
        if (!$result) {
            throw new Exception("Errore nella preparazione della query: " . pg_last_error($db));
        }
        $result = pg_execute($db, "fine_prestito", array($_POST['cod_prestito']));
        if (!$result) {
            throw new Exception("Errore durante la chiusura del prestito: " . pg_last_error($db));
        } else {
            echo "<script>alert('Prestito concluso con successo!');</script>";
        }
    }
    // Gestione proroga prestito
    else if (isset($_POST['proroga']) && isset($_POST['cod_prestito']) && isset($_POST['giorni'])) {
        $giorni = intval($_POST['giorni']);
        $query = "SELECT biblioteca.proroga_prestito($1, $2)";
        $result = pg_prepare($db, "proroga_prestito", $query);
        if (!$result) {
            throw new Exception("Errore nella preparazione della query: " . pg_last_error($db));
        }
        $result = pg_execute($db, "proroga_prestito", array($_POST['cod_prestito'], $giorni));
        if (!$result) {
            throw new Exception("Errore durante la proroga del prestito: " . pg_last_error($db));
        }
        if (pg_fetch_row($result)[0] === 'f') {
            throw new Exception("Il prestito è già in ritardo e non può essere prorogato.");
        } else {
            echo "<script>alert('Prestito prorogato con successo!');</script>";
        }
    }
} catch (Exception $e) {
    echo "<script>alert('" . $e->getMessage() . "');</script>";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Prestiti</title>
    <link rel="stylesheet" type="text/css" href="gestione_styles.css">
    <link rel="stylesheet" href="sidebar.css">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="container">
        <header>
            <h1>Prestiti Aperti</h1>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Codice Prestito</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Lettore</th>
                        <th>Libro</th>
                        <th>Copia</th>
                        <th colspan="2">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query per ottenere i prestiti aperti
                    $query = "SELECT * FROM biblioteca.prestiti_aperti;";
                    $result = pg_query($db, $query);
                    if (!$result) {
                        echo "<tr><td colspan='7'>Errore nella query: " . pg_last_error($db) . "</td></tr>";
                    } else {
                        while ($row = pg_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['cod_prestito']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['data_inizio']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['data_fine']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['lettore']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['libro']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['copia']) . "</td>";
                            echo "<td>
                                <form method='post'>
                                    <input type='hidden' name='cod_prestito' value='" . htmlspecialchars($row['cod_prestito']) . "'>
                                    <input type='number' name='giorni' min='1' required>
                                    <button type='submit' name='proroga'>Proroga</button>
                                </form>
                            </td>";
                            echo "<td>
                                <form method='post'>
                                    <input type='hidden' name='cod_prestito' value='" . htmlspecialchars($row['cod_prestito']) . "'>
                                    <button type='submit' name='restituzione'>Restituzione</button>
                                </form>
                            </td>";
                            echo "</tr>";
                        }
                    }

                    // Chiusura connessione al database
                    close_pg_connection($db);
                    ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
