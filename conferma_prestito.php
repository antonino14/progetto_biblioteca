<?php
ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
include_once('functions.php');
// Controllo se il lettore è loggato
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'lettore') {
    header("Location: login_lettore.php");
    exit();
}
$cf = $_SESSION['cf_lettore'];

// Connessione al database
$db = open_pg_connection();

// Ottieni l'ultimo codice prestito
$query = "SELECT cod_prestito FROM biblioteca.prestito ORDER BY cod_prestito DESC LIMIT 1";
$result = pg_prepare($db, "query_last_cod_prestito", $query);
$result = pg_execute($db, "query_last_cod_prestito", array());
if (!$result) {
    throw new Exception('Errore durante la ricerca dell\'ultimo codice prestito: ' . pg_last_error($db));
   }
$row = pg_fetch_assoc($result);
$last_cod_prestito = $row ? $row['cod_prestito'] : null;
// Incrementa il numero
if ($last_cod_prestito) {
    $last_number = intval(substr($last_cod_prestito, 1));
    $new_number = $last_number + 1;
} else {
    $new_number = 1; // primo prestito
}
// Genera il nuovo codice prestito
$new_cod_prestito = 'P' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
// Recupera i dati dal modulo
$isbn = $_POST['isbn'];
$id_sede = $_POST['sede']; 

// Avvia una transazione
pg_query($db, "BEGIN");

try {
    // Recupera una copia disponibile del libro nella sede selezionata
    $query = "SELECT biblioteca.seleziona_copia($1, $2) AS copia_selezionata";
    $result = pg_prepare($db, "query_copia", $query);
    $result = pg_execute($db, "query_copia", array($isbn, $id_sede));

    if (!$result) {
        throw new Exception("Errore durante la ricerca della copia in sede: " . pg_last_error($db));
    }
    $copia = pg_fetch_assoc($result);
    $cod_copia = $copia['copia_selezionata'];

    if (!$cod_copia) {
        throw new Exception("Nessuna copia disponibile per il libro selezionato nella sede specificata");
    }

    // Inserisci il nuovo prestito
    $query = "INSERT INTO biblioteca.prestito (cod_prestito, data_inizio, data_fine, prestito_aperto, lettore) VALUES ($1, CURRENT_DATE, (CURRENT_DATE + INTERVAL '30 days'), TRUE, $2)";
    $result = pg_prepare($db, "query_prestito", $query);
    $result = pg_execute($db, "query_prestito", array($new_cod_prestito, $cf));

    if (pg_affected_rows($result) == 0) {
        throw new Exception("Errore durante l'inserimento del prestito. Controlla se hai raggiunto il numero massimo di prestiti");
           }

    // Associa il prestito alla copia del libro
    $query = "UPDATE biblioteca.copia SET cod_prestito = $1 WHERE id = $2";
    $result = pg_prepare($db, "query_copia_prestito", $query);
    $result = pg_execute($db, "query_copia_prestito", array($new_cod_prestito, $cod_copia));

    if (!$result) {
        throw new Exception("Errore durante l'aggiornamento della copia: " . pg_last_error($db));
    }

    // Commit della transazione
    pg_query($db, "COMMIT");
} catch (Exception $e) {
    // Rollback della transazione in caso di errore
    pg_query($db, "ROLLBACK");
    $error_message = $e->getMessage();
    }

// Chiusura della connessione al database
close_pg_connection($db);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Conferma Prestito</title>
    <link rel="stylesheet" type="text/css" href="prestito_styles.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Conferma Prestito</h1>
        </header>
        <main>
            <?php if (isset($error_message)) : ?>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <p><a href="catalogo.php">Torna al catalogo</a></p>
            <?php else : ?>
                <p>Il prestito è stato creato con successo!</p>
                <p>Codice Prestito: <?php echo htmlspecialchars($new_cod_prestito); ?></p>
                <p><a href="catalogo.php">Torna al catalogo</a></p>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>
