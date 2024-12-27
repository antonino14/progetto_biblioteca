<?php
    
    include_once('functions.php'); // Inclusione del file functions.php

    // Controllo se il lettore è loggato
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'lettore') {
        header("Location: login_lettore.php");
        exit();
    }
    $cf = $_SESSION['cf_lettore'];

    // Connessione al database
    $db = open_pg_connection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Area Prestiti</title>
    <link rel="stylesheet" type="text/css" href="catalogo_styles.css">
</head>
<body>
    <div class="sidebar">
        <h2>Menu</h2>
        <ul>
            <li><a href="catalogo.php">Catalogo</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
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
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query per ottenere i prestiti aperti
                    $query = "SELECT p.cod_prestito, p.data_inizio, p.data_fine, p.prestito_aperto AS stato
                              FROM biblioteca.prestito p 
                              JOIN biblioteca.lettore l ON p.lettore = l.CF
                              WHERE l.CF = $1 AND p.prestito_aperto = TRUE
                              ORDER BY p.data_fine ASC;";
                    $result = pg_prepare($db, "prestiti_aperti", $query);
                    $result = pg_execute($db, "prestiti_aperti", array($cf));

                    // Popolamento della tabella con i libri
                    while ($row = pg_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>{$row['cod_prestito']}</td>";
                        echo "<td>{$row['data_inizio']}</td>";
                        echo "<td>{$row['data_fine']}</td>";
                        $stato = $row['stato'] == 't' ? 'In Corso' : 'Chiuso';
                        echo "<td>{$stato}</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>
    <div class="container">
        <header>
            <h1>Prestiti Chiusi</h1>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Codice Prestito</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Data Restituzione</th>
                        <th>Consegnato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query per ottenere i prestiti chiusi
                    $query = "SELECT p.cod_prestito, p.data_inizio, p.data_fine, p.data_restituzione
                              FROM biblioteca.prestito p 
                              JOIN biblioteca.lettore l ON p.lettore = l.CF
                              WHERE l.CF = $1 AND p.prestito_aperto = FALSE
                              ORDER BY p.data_fine ASC;";
                    $result = pg_prepare($db, "prestiti_chiusi", $query);
                    $result = pg_execute($db, "prestiti_chiusi", array($cf));

                    // Popolamento della tabella con i libri
                    while ($row = pg_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>{$row['cod_prestito']}</td>";
                        echo "<td>{$row['data_inizio']}</td>";
                        echo "<td>{$row['data_fine']}</td>";
                        echo "<td>{$row['data_restituzione']}</td>";
                        $consegnato = $row['data_restituzione'] > $row['data_fine'] ? 'In Ritardo' : 'In Tempo';
                        echo "<td>{$consegnato}</td>";
                        echo "</tr>";
                    }

                    // Chiusura della connessione al database
                    close_pg_connection($db);
                    ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
