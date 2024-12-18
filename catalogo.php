<?php
session_start();
require_once 'functions.php'; // Inclusione del file functions.php

authenticateUser(); // Verifica dell'autenticazione (lettore o bibliotecario)

// Connessione al database
$conn = open_pg_connection();

// Recupero dei dati del catalogo
$sql = "SELECT l.isbn, l.titolo, l.trama, l.casa_editrice, 
               string_agg(concat(a.nome, ' ', a.cognome), ', ') AS autori
        FROM biblioteca.libro l
        LEFT JOIN biblioteca.scritto s ON l.isbn = s.libro
        LEFT JOIN biblioteca.autore a ON s.autore = a.id
        GROUP BY l.isbn
        ORDER BY l.titolo";

$result = pg_query($conn, $sql);
if (!$result) {
    die("Errore nella query: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main>
        <h1>Catalogo Libri</h1>
        <table>
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Titolo</th>
                    <th>Trama</th>
                    <th>Casa Editrice</th>
                    <th>Autori</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($row['titolo']); ?></td>
                        <td><?php echo htmlspecialchars($row['trama']); ?></td>
                        <td><?php echo htmlspecialchars($row['casa_editrice']); ?></td>
                        <td><?php echo htmlspecialchars($row['autori']); ?></td>
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