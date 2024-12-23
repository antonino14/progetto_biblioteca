<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'functions.php';
    session_start();

    // Funzione per generare un ID di 6 caratteri
    function generate_id($length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        return $random_string;
    }

    $nome = htmlspecialchars($_POST['nome']);
    $cognome = htmlspecialchars($_POST['cognome']);
    $data_nascita = $_POST['data_nascita'];
    $data_morte = empty($_POST['data_morte']) ? null : $_POST['data_morte'];
    $biografia = htmlspecialchars($_POST['biografia']);
    $id = generate_id();

    $conn = open_pg_connection();

    pg_query($conn, 'BEGIN');
    try {
        $query = "INSERT INTO biblioteca.autore (id, nome, cognome, data_nascita, data_morte, biografia) VALUES ($1, $2, $3, $4, $5, $6)";
        $params = array($id, $nome, $cognome, $data_nascita, $data_morte, $biografia);
        $result = pg_query_params($conn, $query, $params);

        if ($result) {
            pg_query($conn, 'COMMIT');
            $_SESSION['success'] = "Autore aggiunto con successo!";
            header("Location: inserisci_libro.php");
            exit();
        } else {
            throw new Exception("Errore durante l'inserimento dell'autore.");
        }
    } catch (Exception $e) {
        pg_query($conn, 'ROLLBACK');
        $_SESSION['error'] = "Errore durante l'inserimento dell'autore: " . htmlspecialchars($e->getMessage());
        header("Location: inserisci_autore.php");
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
    <title>Inserisci Autore</title>
    <link rel="stylesheet" href="css/insert_styles.css">
</head>
<body>
<header>
    <h1>Inserisci Nuovo Autore</h1>
</header>

<div class="container">

    <div class="card">
        <h2>Aggiungi un Autore</h2>
        <form method="POST" action="inserisci_autore.php">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" placeholder="Inserisci il nome dell'autore" required>
            <label for="cognome">Cognome:</label>
            <input type="text" id="cognome" name="cognome" placeholder="Inserisci il cognome dell'autore" required>
            <label for="data_nascita">Data di Nascita:</label>
            <input type="date" id="data_nascita" name="data_nascita" required>
            <label for="data_morte">Data di Morte (opzionale):</label>
            <input type="date" id="data_morte" name="data_morte">
            <label for="biografia">Biografia:</label>
            <textarea id="biografia" name="biografia" placeholder="Inserisci la biografia dell'autore" required></textarea>
            <button type="submit" class="button">Inserisci Autore</button>
        </form>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>

</div>
<footer>
</footer>
<link rel="stylesheet" href="insert_styles.css">
</body>
</html>
