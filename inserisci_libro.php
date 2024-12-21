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
<nav>
    <a href="welcome.php">Home</a>
    <a href="catalogo.php">Catalogo</a>
    <a href="area_prestiti.php">Prestiti</a>
    <a href="login_bibliotecario.php">Accedi</a>
</nav>
<div class="container">

    <div class="card">
        <h2>Aggiungi un Libro</h2>
        <form method="POST" action="inserisci_libro.php">
            <label for="titolo">Titolo:</label>
            <input type="text" id="titolo" name="titolo" placeholder="Inserisci il titolo del libro" required>
            <label for="autore">Autore:</label>
            <input type="text" id="autore" name="autore" placeholder="Inserisci l'autore" required>
            <label for="anno">Anno Pubblicazione:</label>
            <input type="number" id="anno" name="anno" placeholder="Inserisci l'anno" required>
            <label for="isbn">ISBN:</label>
            <input type="text" id="isbn" name="isbn" placeholder="Inserisci l'ISBN" required>
            <button type="submit" class="button">Inserisci Libro</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'functions.php';
            $conn = connectDB();

            $titolo = trim($_POST['titolo']);
            $autore = trim($_POST['autore']);
            $anno = (int)$_POST['anno'];
            $isbn = trim($_POST['isbn']);

            pg_query($conn, 'BEGIN');
            try {
                // Inserimento o verifica autore
                $query_autore = "SELECT id FROM biblioteca.autore WHERE concat(nome, ' ', cognome) = $1 LIMIT 1";
                $result_autore = pg_query_params($conn, $query_autore, array($autore));
                if ($row_autore = pg_fetch_assoc($result_autore)) {
                    $id_autore = $row_autore['id'];
                } else {
                    // Suddivisione dell'autore in nome e cognome
                    $autore_parts = explode(' ', $autore, 2);
                    $nome_autore = $autore_parts[0];
                    $cognome_autore = $autore_parts[1] ?? '';
                    $id_autore = uniqid('A');
                    $query_insert_autore = "INSERT INTO biblioteca.autore (id, nome, cognome) VALUES ($1, $2, $3)";
                    pg_query_params($conn, $query_insert_autore, array($id_autore, $nome_autore, $cognome_autore));
                }

                // Inserimento libro
                $query_libro = "INSERT INTO biblioteca.libro (isbn, titolo, trama, casa_editrice) VALUES ($1, $2, '', '')";
                pg_query_params($conn, $query_libro, array($isbn, $titolo));

                // Relazione libro-autore
                $query_scritto = "INSERT INTO biblioteca.scritto (autore, libro) VALUES ($1, $2)";
                pg_query_params($conn, $query_scritto, array($id_autore, $isbn));

                pg_query($conn, 'COMMIT');
                echo "<p class='success'>Libro aggiunto con successo!</p>";
            } catch (Exception $e) {
                pg_query($conn, 'ROLLBACK');
                echo "<p class='error'>Errore durante l'inserimento del libro: " . htmlspecialchars($e->getMessage()) . "</p>";
            } finally {
                pg_close($conn);
            }
        }
        ?>
    </div>

</div>
<footer>
</footer>
<link rel="stylesheet" href="insert_styles.css">
</body>
</html>
