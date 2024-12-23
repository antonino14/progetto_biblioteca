<?php 
require_once 'functions.php';
session_start();

// Verifica se l'utente è autenticato come bibliotecario
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'bibliotecario') {
    header("Location: login_bibliotecario.php");
    exit();
}

$db = open_pg_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aggiungi un nuovo libro
    if (isset($_POST['aggiungi'])) {
        $isbn = $_POST['isbn'] ?? '';
        $titolo = $_POST['titolo'] ?? '';
        $trama = $_POST['trama'] ?? '';
        $casa_editrice = $_POST['casa_editrice'] ?? '';

        $query = "INSERT INTO biblioteca.libro (isbn, titolo, trama, casa_editrice) VALUES ($1, $2, $3, $4)";
        $params = array($isbn, $titolo, $trama, $casa_editrice);

        $result = pg_query_params($db, $query, $params);

        if ($result) {
            $_SESSION['success'] = "Libro aggiunto con successo!";
        } else {
            $_SESSION['error'] = "Errore nell'aggiunta del libro: " . pg_last_error($db);
        }
    }

    // Elimina un libro
    if (isset($_POST['elimina'])) {
        $isbn = $_POST['isbn'] ?? '';

        $query = "DELETE FROM biblioteca.libro WHERE isbn = $1";
        $params = array($isbn);

        $result = pg_query_params($db, $query, $params);

        if ($result) {
            $_SESSION['success'] = "Libro eliminato con successo!";
        } else {
            $_SESSION['error'] = "Errore nell'eliminazione del libro: " . pg_last_error($db);
        }
    }

    header("Location: gestione_libri.php");
    exit();
}

// Recupera l'elenco dei libri
$query = "SELECT l.isbn, l.titolo, l.trama, l.casa_editrice, string_agg(a.nome || ' ' || a.cognome, ', ') AS autori 
          FROM biblioteca.libro l
          LEFT JOIN biblioteca.scritto s ON l.isbn = s.libro
          LEFT JOIN biblioteca.autore a ON s.autore = a.id
          GROUP BY l.isbn, l.titolo, l.trama, l.casa_editrice
          ORDER BY l.titolo";
$result = pg_query($db, $query);
$libri = pg_fetch_all($result);

pg_free_result($result);
close_pg_connection($db);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Libri</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1>Gestione Libri</h1>

        <form method="GET" action="inserisci_libro.php">
            <button type="submit">Nuovo Libro</button>
        </form>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h2>Elenco Libri</h2>
        <table>
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Titolo</th>
                    <th>Trama</th>
                    <th>Casa Editrice</th>
                    <th>Autori</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($libri): ?>
                    <?php foreach ($libri as $libro): ?>
                        <tr>
                            <td><?= htmlspecialchars($libro['isbn']) ?></td>
                            <td><?= htmlspecialchars($libro['titolo']) ?></td>
                            <td><?= htmlspecialchars($libro['trama']) ?></td>
                            <td><?= htmlspecialchars($libro['casa_editrice']) ?></td>
                            <td><?= htmlspecialchars($libro['autori']) ?></td>
                            <td>
                                <form action="gestione_libri.php" method="post" style="display:inline;">
                                    <input type="hidden" name="isbn" value="<?= htmlspecialchars($libro['isbn']) ?>">
                                    <button type="submit" name="elimina">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nessun libro trovato.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <link rel="stylesheet" href="gestione_styles.css">
</body>

</html>
