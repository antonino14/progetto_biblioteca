<?php
session_start();
require_once 'functions.php'; // Inclusione del file functions.php

// Controllo della sessione per il lettore
authenticateUser('lettore');

// Recupero dell'ID utente dalla sessione
$cf_lettore = $_SESSION['cf_lettore'];

// Connessione al database
$conn = connectDatabase();

// Recupero dei prestiti associati al lettore
$sql = "SELECT p.cod_prestito, p.data_inizio, p.data_fine, l.titolo, c.id AS copia_id
        FROM biblioteca.prestito p
        JOIN biblioteca.copia c ON p.cod_prestito = c.cod_prestito
        JOIN biblioteca.libro l ON c.libro = l.isbn
        WHERE p.lettore = $1 AND p.prestito_aperto = TRUE";

$result = pg_query_params($conn, $sql, array($cf_lettore));

if (!$result) {
    die("Errore nella query: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Prestiti</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main>
        <h1>Prestiti in Corso</h1>
        <table>
            <thead>
                <tr>
                    <th>Codice Prestito</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Titolo Libro</th>
                    <th>ID Copia</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['cod_prestito']); ?></td>
                        <td><?php echo htmlspecialchars($row['data_inizio']); ?></td>
                        <td><?php echo htmlspecialchars($row['data_fine']); ?></td>
                        <td><?php echo htmlspecialchars($row['titolo']); ?></td>
                        <td><?php echo htmlspecialchars($row['copia_id']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>

<?php
pg_free_result($result);
pg_close($conn);
?>