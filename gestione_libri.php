<?php

require_once 'functions.php';
session_start();

// Verifica se l'utente è autenticato come bibliotecario
if (!isset($_SESSION['bibliotecario'])) {
    header("Location: login_bibliotecario.php");
    exit();
}

$conn = connectToDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aggiungi un nuovo libro
    if (isset($_POST['aggiungi'])) {
        $isbn = $_POST['isbn'] ?? '';
        $titolo = $_POST['titolo'] ?? '';
        $trama = $_POST['trama'] ?? '';
        $casa_editrice = $_POST['casa_editrice'] ?? '';

        $stmt = $conn->prepare("INSERT INTO biblioteca.libro (isbn, titolo, trama, casa_editrice) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $isbn, $titolo, $trama, $casa_editrice);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Libro aggiunto con successo!";
        } else {
            $_SESSION['error'] = "Errore nell'aggiunta del libro: " . $stmt->error;
        }

        $stmt->close();
    }

    // Elimina un libro
    if (isset($_POST['elimina'])) {
        $isbn = $_POST['isbn'] ?? '';

        $stmt = $conn->prepare("DELETE FROM biblioteca.libro WHERE isbn = ?");
        $stmt->bind_param("s", $isbn);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Libro eliminato con successo!";
        } else {
            $_SESSION['error'] = "Errore nell'eliminazione del libro: " . $stmt->error;
        }

        $stmt->close();
    }

    header("Location: gestione_libri.php");
    exit();
}

// Recupera l'elenco dei libri
$query = "SELECT * FROM biblioteca.libro";
$result = $conn->query($query);
$libri = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Libri</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1>Gestione Libri</h1>

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

        <h2>Aggiungi un nuovo libro</h2>
        <form action="gestione_libri.php" method="post">
            <label for="isbn">ISBN:</label>
            <input type="text" id="isbn" name="isbn" required>

            <label for="titolo">Titolo:</label>
            <input type="text" id="titolo" name="titolo" required>

            <label for="trama">Trama:</label>
            <textarea id="trama" name="trama" required></textarea>

            <label for="casa_editrice">Casa Editrice:</label>
            <input type="text" id="casa_editrice" name="casa_editrice" required>

            <button type="submit" name="aggiungi">Aggiungi</button>
        </form>

        <h2>Elenco Libri</h2>
        <table>
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Titolo</th>
                    <th>Trama</th>
                    <th>Casa Editrice</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libri as $libro): ?>
                    <tr>
                        <td><?= htmlspecialchars($libro['isbn']) ?></td>
                        <td><?= htmlspecialchars($libro['titolo']) ?></td>
                        <td><?= htmlspecialchars($libro['trama']) ?></td>
                        <td><?= htmlspecialchars($libro['casa_editrice']) ?></td>
                        <td>
                            <form action="gestione_libri.php" method="post" style="display:inline;">
                                <input type="hidden" name="isbn" value="<?= htmlspecialchars($libro['isbn']) ?>">
                                <button type="submit" name="elimina">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>