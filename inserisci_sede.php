<?php
require_once 'functions.php';

// Connessione al database
$conn = connectDB();

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id']);
    $citta = trim($_POST['citta']);
    $indirizzo = trim($_POST['indirizzo']);

    // Inizia una transazione
    pg_query($conn, 'BEGIN');
    try {
        // Controlla se la combinazione città e indirizzo esiste già
        $query_check = "SELECT 1 FROM biblioteca.sede WHERE città = $1 AND indirizzo = $2";
        $result_check = pg_query_params($conn, $query_check, array($citta, $indirizzo));
        if (pg_num_rows($result_check) > 0) {
            throw new Exception("La sede con questa città e indirizzo esiste già.");
        }

        // Inserisce la sede nella tabella biblioteca.sede
        $query_insert = "INSERT INTO biblioteca.sede (id, città, indirizzo) VALUES ($1, $2, $3)";
        $result_insert = pg_query_params($conn, $query_insert, array($id, $citta, $indirizzo));

        if (!$result_insert) {
            throw new Exception("Errore nell'inserimento della sede.");
        }

        // Commit della transazione
        pg_query($conn, 'COMMIT');
        $success = "Sede inserita con successo!";
    } catch (Exception $e) {
        // Rollback in caso di errore
        pg_query($conn, 'ROLLBACK');
        $error = "Errore: " . htmlspecialchars($e->getMessage());
    } finally {
        // Chiusura della connessione
        pg_close($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inserisci Sede</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
    <h1>Inserisci Sede</h1>
    <?php if (!empty($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php elseif (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="id">ID Sede:</label>
        <input type="text" id="id" name="id" required>
        <label for="citta">Città:</label>
        <input type="text" id="citta" name="citta" required>
        <label for="indirizzo">Indirizzo:</label>
        <input type="text" id="indirizzo" name="indirizzo" required>
        <button type="submit">Inserisci</button>
    </form>
</body>
</html>