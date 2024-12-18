<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('library/functions.php');

// Controllo se il lettore è loggato
session_start();
if (!isset($_SESSION['lettore'])) {
    header("Location: login_lettore.php");
    exit();
}

// Connessione al database
$db = open_pg_connection();

// Recupera l'ISBN dal parametro della query string
$isbn = $_GET['isbn'];

// Query per ottenere le sedi con copie disponibili del libro
$query = "SELECT DISTINCT s.id as id_sede, s.città, s.indirizzo
          FROM biblioteca.copia c
          JOIN biblioteca.sede s ON c.sede = s.id
          WHERE c.libro = $1 AND c.cod_prestito IS NULL";

$result = pg_prepare($db, "query_sedi" ,$query);
$result = pg_execute($db, "query_sedi", array($isbn));

// Chiusura della connessione al database
close_pg_connection($db);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Richiedi Prestito</title>
    <link rel="stylesheet" type="text/css" href="prestito_styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Richiedi Prestito</h1>
        </header>
        <main>
            <form action="conferma_prestito.php" method="post">
                <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
                <label for="sede">Seleziona una sede:</label>
                <select id="sede" name="sede" required>
                    <?php
                    while ($row = pg_fetch_assoc($result)) {
                        echo "<option value=\"{$row['id_sede']}\">{$row['città']} - {$row['indirizzo']}</option>";
                    }
                    ?>
                </select>
                <button type="submit">Conferma Prestito</button>
                <p><a href="catalogo.php">Torna al catalogo</a></p>
            </form>
        </main>
    </div>
</body>
</html>