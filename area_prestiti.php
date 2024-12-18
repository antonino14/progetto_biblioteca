<?php
require_once 'functions.php';
session_start();

// Verifica se l'utente è loggato come lettore
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'lettore') {
    header('Location: login_lettore.php');
    exit();
}

// Connessione al database
$conn = open_pg_connection();

$query = "SELECT p.cod_prestito, p.data_inizio, p.data_fine, c.libro AS isbn, c.id AS copia
          FROM biblioteca.prestito p
          JOIN biblioteca.copia c ON p.cod_prestito = c.cod_prestito
          WHERE p.lettore = $1 AND p.prestito_aperto = TRUE";
$result = pg_query_params($conn, $query, array($_SESSION['user_id']));

$prestiti_aperti = pg_fetch_all($result);

close_pg_connection($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Prestiti</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Area Prestiti</h1>
    <?php if ($prestiti_aperti): ?>
        <table>
            <thead>
                <tr>
                    <th>Codice Prestito</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>ISBN Libro</th>
                    <th>Copia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestiti_aperti as $prestito): ?>
                    <tr>
                        <td><?= htmlspecialchars($prestito['cod_prestito']) ?></td>
                        <td><?= htmlspecialchars($prestito['data_inizio']) ?></td>
                        <td><?= htmlspecialchars($prestito['data_fine']) ?></td>
                        <td><?= htmlspecialchars($prestito['isbn']) ?></td>
                        <td><?= htmlspecialchars($prestito['copia']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nessun prestito attivo trovato.</p>
    <?php endif; ?>
</body>
</html>
