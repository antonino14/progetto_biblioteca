<?php 

ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('functions.php'); 

// Controllo se il bibliotecario è loggato
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'bibliotecario') {
    header("Location: login_bibliotecario.php");
    exit();
}

$conn = connectDB();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id_sede'], $_POST['isbn'])) {

        $id_sede = $_GET['id_sede'];
        $isbn = $_POST['isbn'];

        // Ottieni l'ultimo codice copia
        $query_last_copia = "SELECT id FROM biblioteca.copia ORDER BY id DESC LIMIT 1";
        $result = pg_query($conn, $query_last_copia);

        if (!$result) {
            throw new Exception('Errore durante la ricerca dell\'ultimo codice copia');
        }

        $row = pg_fetch_assoc($result);
        $last_cod_copia = $row ? $row['id'] : null;

        // Incrementa il numero
        if ($last_cod_copia) {
            $last_number = intval(substr($last_cod_copia, 1)); // Rimuove la 'C' e converte in int
            $new_number = $last_number + 1;
        } else {
            $new_number = 1; // Prima copia
        }

        // Genera il nuovo codice copia
        $new_cod_copia = 'C' . str_pad($new_number, 5, '0', STR_PAD_LEFT);

        // Query per l'inserimento di una copia
        $query_insert_copia = "INSERT INTO biblioteca.copia (id, libro, sede, cod_prestito) VALUES ($1, $2, $3, NULL)";
        $stmt = pg_prepare($conn, "aggiungi_copia", $query_insert_copia);
        $result = pg_execute($stmt, array($new_cod_copia, $isbn, $id_sede));

        if (!$result) {
            throw new Exception("Errore durante l'aggiunta della copia");
        }

        $_SESSION['message'] = 'Copia inserita con successo!';
        header("Location: gestione_sedi.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

disconnectDB($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Inserisci Copia</title>
    <link rel="stylesheet" type="text/css" href="css/insert_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1>Inserisci Copia</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message"> <?= $_SESSION['message'] ?> </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"> <?= $_SESSION['error'] ?> </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="inserisci_copia.php?id_sede=<?= htmlspecialchars($_GET['id_sede']) ?>" method="post">
            <label for="libro">Scegli Libro:</label>
            <select id="libro" name="isbn" required>
                <?php
                // Connessione al database per ottenere i libri esistenti
                $conn = connectDB();
                $query_libri = "SELECT isbn, titolo FROM biblioteca.libro ORDER BY titolo ASC";
                $result = pg_query($conn, $query_libri);

                if ($result) {
                    while ($row = pg_fetch_assoc($result)) {
                        echo "<option value=\"" . htmlspecialchars($row['isbn']) . "\">" . htmlspecialchars($row['titolo']) . "</option>";
                    }
                }

                disconnectDB($conn);
                ?>
            </select>
            <button type="submit" name="inserisci_copia">Inserisci Copia</button>
            <a href="gestione_sedi.php" class="torna-indietro">Torna Indietro</a>
        </form>
    </div>
    <link rel="stylesheet" href="insert_styles.css">
</body>
</html>
