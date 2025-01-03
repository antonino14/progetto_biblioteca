<?php
require_once 'functions.php'; 

session_start();
// Verifica autenticazione utente bibliotecario
authenticateUser('bibliotecario');

// Connessione al database
$db = open_pg_connection();

try {
    if (isset($_POST['reset_ritardi'])) {
        $query = "UPDATE biblioteca.lettore SET num_ritardi = 0 WHERE cf = $1;";
        $result = pg_prepare($db, "azzera_ritardi", $query);
        $result = pg_execute($db, "azzera_ritardi", array($_POST['cf']));

        if (!$result) {
            throw new Exception("Errore durante l'azzeramento dei ritardi: " . pg_last_error($db));
        } else {
            echo "<script>alert('Ritardi azzerati con successo!');</script>";
        }
    }
} catch (Exception $e) {
    echo "<script>alert('" . $e->getMessage() . "');</script>";
}

// Recupero della lista dei lettori
$sql = "SELECT cf, nome, cognome, categoria, num_ritardi FROM biblioteca.lettore ORDER BY cognome, nome";
$result = pg_query($db, $sql);

if (!$result) {
    die("Errore nella query: " . pg_last_error($db));
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Gestione Lettori</title>
    <link rel="stylesheet" type="text/css" href="gestione_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="container">
        <header>
            <h1>Lettori Iscritti</h1>
        </header>
        <div class="nuovo-bottone-container">
            <a href="inserisci_lettore.php" class="button">Nuovo Lettore</a>
        </div>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Codice Fiscale</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Categoria</th>
                        <th>Numero Ritardi</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['cf']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['cognome']); ?></td>
                            <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                            <td><?php echo htmlspecialchars($row['num_ritardi']); ?></td>
                            <td>
                                <form method="post" action="gestione_lettori.php">
                                    <input type="hidden" name="cf" value="<?php echo htmlspecialchars($row['cf']); ?>">
                                    <button type="submit" name="reset_ritardi">Azzera Ritardi</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>

<?php
pg_free_result($result);
close_pg_connection($db);
?>
