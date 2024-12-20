<?php
session_start();
require_once 'functions.php'; // Inclusione del file functions.php

// Verifica autenticazione utente (bibliotecario)
authenticateUser('bibliotecario');

// Connessione al database
$conn = open_pg_connection();
if (!$conn) {
    die("Errore nella connessione al database: " . pg_last_error());
}

echo "Connessione al database riuscita.<br>";

// Recupero della lista dei lettori
$sql = "SELECT cf, nome, cognome, categoria, num_ritardi FROM biblioteca.lettore ORDER BY cognome, nome";
$result = pg_query($conn, $sql);

if (!$result) {
    die("Errore nella query: " . pg_last_error($conn));
}

echo "Query eseguita con successo.<br>";
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Lettori</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main>
        <h1>Gestione Lettori</h1>
        <table>
            <thead>
                <tr>
                    <th>CF</th>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Categoria</th>
                    <th>Numero di Ritardi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['cf']); ?></td>
                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['cognome']); ?></td>
                        <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($row['num_ritardi']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>

<?php
pg_free_result($result);
close_pg_connection($conn);
?>
