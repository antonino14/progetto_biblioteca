<?php
require_once 'functions.php';
session_start();
$conn = open_pg_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = trim($_POST['isbn']);
    $titolo = trim($_POST['titolo']);
    $trama = trim($_POST['trama']);
    $casa_editrice = trim($_POST['casa_editrice']);
    $autori = $_POST['autori'];

    pg_query($conn, 'BEGIN');
    try {
        // Inserimento libro
        $query_libro = "INSERT INTO biblioteca.libro (isbn, titolo, trama, casa_editrice) VALUES ($1, $2, $3, $4)";
        pg_query_params($conn, $query_libro, array($isbn, $titolo, $trama, $casa_editrice));

        // Relazione libro-autore
        foreach ($autori as $autore) {
            $query_scritto = "INSERT INTO biblioteca.scritto (autore, libro) VALUES ($1, $2)";
            pg_query_params($conn, $query_scritto, array($autore, $isbn));
        }

        pg_query($conn, 'COMMIT');
        $_SESSION['success'] = "Libro aggiunto con successo!";
        header("Location: gestione_libri.php");
        exit();
    } catch (Exception $e) {
        pg_query($conn, 'ROLLBACK');
        $_SESSION['error'] = "Errore durante l'inserimento del libro: " . htmlspecialchars($e->getMessage());
        header("Location: gestione_libri.php");
        exit();
    } finally {
        close_pg_connection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Libro</title>
    <link rel="stylesheet" href="css/insert_styles.css">
</head>
<body>
<header>
    <h1>Inserisci Nuovo Libro</h1>
</header>

<div class="container">

    <div class="card">
        <h2>Aggiungi un Libro</h2>
        <form method="POST" action="inserisci_libro.php">
            <label for="isbn">ISBN:</label>
            <input type="text" id="isbn" name="isbn" placeholder="Inserisci l'ISBN" required>
            <label for="titolo">Titolo:</label>
            <input type="text" id="titolo" name="titolo" placeholder="Inserisci il titolo del libro" required>
            <label for="trama">Trama:</label>
            <textarea id="trama" name="trama" placeholder="Inserisci la trama del libro" required></textarea>
            <label for="casa_editrice">Casa Editrice:</label>
            <input type="text" id="casa_editrice" name="casa_editrice" placeholder="Inserisci la casa editrice" required>
            <label for="autori">Autori:</label>
            <select id="autori" name="autori[]" multiple required>
                <?php
                $conn = open_pg_connection();
                $query_autori = "SELECT id, nome || ' ' || cognome AS nome_completo FROM biblioteca.autore ORDER BY nome, cognome";
                $result_autori = pg_query($conn, $query_autori);
                while ($row = pg_fetch_assoc($result_autori)) {
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['nome_completo']) . "</option>";
                }
                close_pg_connection($conn);
                ?>
            </select>
            <a href="inserisci_autore.php" class="button">Aggiungi Autore</a>
            <button type="submit" class="button">Inserisci Libro</button>
        </form>
    </div>

</div>
<footer>
</footer>
<link rel="stylesheet" href="insert_styles.css">
</body>
</html>
