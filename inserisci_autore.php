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
<nav>
    <a href="welcome.php">Home</a>
    <a href="catalogo.php">Catalogo</a>
    <a href="area_prestiti.php">Prestiti</a>
    <a href="login_bibliotecario.php">Accedi</a>
</nav>
<div class="container">

    <div class="card">
        <h2>Aggiungi un Autore</h2>
        <form method="POST" action="inserisci_autore.php">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" placeholder="Inserisci il nome dell'autore" required>
            <label for="cognome">Cognome:</label>
            <input type="text" id="cognome" name="cognome" placeholder="Inserisci il cognome dell'autore" required>
            <button type="submit" class="button">Inserisci Autore</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            include 'functions.php';

            $nome = htmlspecialchars($_POST['nome']);
            $cognome = htmlspecialchars($_POST['cognome']);

            $conn = connectDB();

            $query = "INSERT INTO biblioteca.autore (nome, cognome) VALUES ($1, $2)";
            $stmt = pg_prepare($conn, "inserisci_autore", $query);
            $result = pg_execute($stmt, array($nome, $cognome));

            if ($result) {
                echo "<p class='success'>Autore aggiunto con successo!</p>";
            } else {
                echo "<p class='error'>Errore durante l'inserimento dell'autore.</p>";
            }

            disconnectDB($conn);
        }
        ?>
    </div>

</div>
<footer>
</footer>
<link rel="stylesheet" href="insert_styles.css">
</body>
</html>
