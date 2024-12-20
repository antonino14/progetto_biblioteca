<?php
session_start();
require_once 'functions.php'; // Inclusione del file functions.php

// Verifica autenticazione utente (lettore)
authenticateUser('lettore');

// Connessione al database
$conn = open_pg_connection();

// Controllo e sanitizzazione dei parametri inviati tramite POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = $_POST['isbn'] ?? '';
    $sede = $_POST['sede'] ?? '';
    $sede_preferita = $_POST['sede_preferita'] ?? '';
    $isbn = htmlspecialchars(trim($isbn));
    $sede = htmlspecialchars(trim($sede));
    $sede_preferita = htmlspecialchars(trim($sede_preferita));

    // Inserimento del prestito
    $cf_lettore = $_SESSION['cf_lettore'];

    $sql_prestito = "SELECT biblioteca.seleziona_copia($1, $2) AS copia_id";
    $result_copia = pg_query_params($conn, $sql_prestito, array($isbn, $sede));

    if ($result_copia && $row = pg_fetch_assoc($result_copia)) {
        $copia_id = $row['copia_id'];

        if ($copia_id) {
            $sql_inserisci = "INSERT INTO biblioteca.prestito (data_inizio, data_fine, prestito_aperto, lettore, cod_prestito)
                              VALUES (CURRENT_DATE, CURRENT_DATE + INTERVAL '30 days', TRUE, $1, DEFAULT) RETURNING cod_prestito";

            $result_prestito = pg_query_params($conn, $sql_inserisci, array($cf_lettore));

            if ($result_prestito) {
                echo "<p>Prestito confermato con successo!</p>";
            } else {
                echo "<p>Errore durante la conferma del prestito: " . pg_last_error($conn) . "</p>";
            }
        } else {
            echo "<p>Non ci sono copie disponibili per il libro richiesto.</p>";
        }
    } else {
        echo "<p>Errore durante la selezione della copia: " . pg_last_error($conn) . "</p>";
    }

    pg_free_result($result_copia);
} else {
    echo "<p>Richiesta non valida.</p>";
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Prestito</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main>
        <h1>Conferma Prestito</h1>
        <a href="area_prestiti.php">Torna all'area prestiti</a>
    </main>
</body>
</html>>
