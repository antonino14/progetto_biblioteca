<?php
session_start();
require_once 'functions.php'; // Inclusione del file functions.php

// Verifica se l'utente è autenticato come lettore
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'lettore') {
    header("Location: login_lettore.php");
    exit();
}

// Connessione al database
$conn = open_pg_connection();
$cf_lettore = $_SESSION['cf_lettore'];

// Recupera i prestiti attivi del lettore
$sql_prestiti = "SELECT p.cod_prestito, p.data_inizio, p.data_fine, l.titolo
                 FROM biblioteca.prestito p
                 JOIN biblioteca.copia c ON p.cod_prestito = c.cod_prestito
                 JOIN biblioteca.libro l ON c.libro = l.isbn
                 WHERE p.lettore = $1 AND p.prestito_aperto = TRUE";
$result_prestiti = pg_query_params($conn, $sql_prestiti, array($cf_lettore));
$prestiti = pg_fetch_all($result_prestiti);

// Gestione delle richieste POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione'])) {
        $azione = $_POST['azione'];

        if ($azione === 'richiedi_prestito') {
            $isbn = $_POST['isbn'] ?? '';
            $sede_preferita = $_POST['sede_preferita'] ?? '';

            // Verifica disponibilità del libro e registra il prestito
            $result_prestito = richiedi_prestito($cf_lettore, $isbn, $sede_preferita);
            if ($result_prestito) {
                $messaggio = "Prestito richiesto con successo!";
            } else {
                $errore = "Errore nella richiesta del prestito.";
            }
        }

        if ($azione === 'visualizza_libro') {
            $isbn_ricerca = $_POST['isbn_ricerca'] ?? '';

            // Recupera informazioni sul libro
            $sql_libro = "SELECT * FROM biblioteca.libro WHERE isbn = $1";
            $result_libro = pg_query_params($conn, $sql_libro, array($isbn_ricerca));
            $libro = pg_fetch_assoc($result_libro);
        }

        if ($azione === 'cambia_password') {
            $password_corrente = $_POST['password_corrente'] ?? '';
            $nuova_password = $_POST['nuova_password'] ?? '';

            // Modifica la password del lettore
            $result_password = cambia_password($_SESSION['email'], $nuova_password);
            if ($result_password) {
                $messaggio = "Password modificata con successo!";
            } else {
                $errore = "Errore nella modifica della password.";
            }
        }
    }
}

pg_free_result($result_prestiti);
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
    <?php include 'sidebar.php'; ?>
    <h1>Area Prestiti</h1>

    <?php if (isset($messaggio)): ?>
        <div class="success-message"><?= htmlspecialchars($messaggio) ?></div>
    <?php endif; ?>

    <?php if (isset($errore)): ?>
        <div class="error-message"><?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <h2>Prestiti Attivi</h2>
    <?php if ($prestiti): ?>
        <table>
            <thead>
                <tr>
                    <th>Codice Prestito</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Titolo Libro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestiti as $prestito): ?>
                    <tr>
                        <td><?= htmlspecialchars($prestito['cod_prestito']) ?></td>
                        <td><?= htmlspecialchars($prestito['data_inizio']) ?></td>
                        <td><?= htmlspecialchars($prestito['data_fine']) ?></td>
                        <td><?= htmlspecialchars($prestito['titolo']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Non hai nessun prestito in corso.</p>
    <?php endif; ?>

    <h2>Richiedi un Nuovo Prestito</h2>
    <form action="richiedi_prestito.php" method="get">
        <label for="isbn">ISBN:</label>
        <input type="text" id="isbn" name="isbn" required>
        <label for="sede_preferita">Sede Preferita (opzionale):</label>
        <input type="text" id="sede_preferita" name="sede_preferita">
        <button type="submit">Richiedi Prestito</button>
    </form>

    <h2>Visualizza Informazioni Libro</h2>
    <form action="area_prestiti.php" method="post">
        <input type="hidden" name="azione" value="visualizza_libro">
        <label for="isbn_ricerca">ISBN:</label>
        <input type="text" id="isbn_ricerca" name="isbn_ricerca" required>
        <button type="submit">Visualizza</button>
    </form>

    <?php if (isset($libro)): ?>
        <h3>Informazioni Libro</h3>
        <p>ISBN: <?= htmlspecialchars($libro['isbn']) ?></p>
        <p>Titolo: <?= htmlspecialchars($libro['titolo']) ?></p>
        <p>Trama: <?= htmlspecialchars($libro['trama']) ?></p>
        <p>Casa Editrice: <?= htmlspecialchars($libro['casa_editrice']) ?></p>
    <?php endif; ?>

    <h2>Cambia Password</h2>
    <form action="area_prestiti.php" method="post">
        <input type="hidden" name="azione" value="cambia_password">
        <label for="password_corrente">Password Corrente:</label>
        <input type="password" id="password_corrente" name="password_corrente" required>
        <label for="nuova_password">Nuova Password:</label>
        <input type="password" id="nuova_password" name="nuova_password" required>
        <button type="submit">Cambia Password</button>
    </form>
</body>
</html>>
