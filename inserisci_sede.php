<?php 

require_once 'functions.php';

// Connessione al database
$conn = open_pg_connection();

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citta = trim($_POST['citta']);
    $indirizzo = trim($_POST['indirizzo']);

    // Genera un ID univoco
    $query_last_sede = "SELECT id FROM biblioteca.sede ORDER BY id DESC LIMIT 1";
    $result = pg_query($conn, $query_last_sede);
    $row = pg_fetch_assoc($result);
    $last_id = $row ? $row['id'] : 'S00000';
    $new_id = 'S' . str_pad(intval(substr($last_id, 1)) + 1, 5, '0', STR_PAD_LEFT);

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
        $result_insert = pg_query_params($conn, $query_insert, array($new_id, $citta, $indirizzo));

        if (!$result_insert) {
            throw new Exception("Errore nell'inserimento della sede.");
        }

        // Commit della transazione
        pg_query($conn, 'COMMIT');
        $_SESSION['message'] = "Sede inserita con successo!";
        header("Location: gestione_sedi.php");
        exit();
    } catch (Exception $e) {
        // Rollback in caso di errore
        pg_query($conn, 'ROLLBACK');
        $_SESSION['error'] = "Errore: " . htmlspecialchars($e->getMessage());
        header("Location: gestione_sedi.php");
        exit();
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
    <link rel="stylesheet" type="text/css" href="css/insert_styles.css">
</head>
<body>
    <h1>Inserisci Sede</h1>
    <form method="POST">
        <label for="citta">Città:</label>
        <input type="text" id="citta" name="citta" required>
        <label for="indirizzo">Indirizzo:</label>
        <input type="text" id="indirizzo" name="indirizzo" required>
        <button type="submit">Inserisci</button>
        <a href="gestione_sedi.php" class="button">Torna a Gestione Sedi</a>
    </form>
    <link rel="stylesheet" href="insert_styles.css">
</body>
</html>
