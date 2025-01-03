<?php 

require_once 'functions.php';

// Connessione al database
 $conn = open_pg_connection();


// Messaggi di feedback
$success = $error = '';

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizzazione e validazione input
    $cf = trim($_POST['cf']);
    $nome = htmlspecialchars(trim($_POST['nome']));
    $cognome = htmlspecialchars(trim($_POST['cognome']));
    $categoria = $_POST['categoria'];
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Email non valida.";
    } elseif (empty($password)) {
        $error = "La password non può essere vuota.";
    } else {
        // Inizia una transazione
        pg_query($conn, 'BEGIN');
        try {
            // Inserisce il lettore nella tabella biblioteca.lettore
            $query_lettore = "INSERT INTO biblioteca.lettore (cf, nome, cognome, categoria) VALUES ($1, $2, $3, $4)";
            pg_query_params($conn, $query_lettore, array($cf, $nome, $cognome, $categoria));

            // Inserisce l'utente nella tabella biblioteca.utente_lettore
            $query_utente = "INSERT INTO biblioteca.utente_lettore (email, password, cf_lettore) VALUES ($1, $2, $3)";
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            pg_query_params($conn, $query_utente, array($email, $hashed_password, $cf));

            // Commit della transazione
            pg_query($conn, 'COMMIT');
            $success = "Lettore inserito con successo!";
        } catch (Exception $e) {
            // Rollback in caso di errore
            pg_query($conn, 'ROLLBACK');
            $error = "Errore nell'inserimento del lettore: " . pg_last_error($conn);
        }
    }
}

pg_close($conn);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Lettore</title>
    <link rel="stylesheet" type="text/css" href="css/insert_styles.css">
</head>
<body>
    <h1>Inserisci Lettore</h1>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php elseif ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="cf">Codice Fiscale:</label>
        <input type="text" id="cf" name="cf" required>
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        <label for="cognome">Cognome:</label>
        <input type="text" id="cognome" name="cognome" required>
        <label for="categoria">Categoria:</label>
        <select id="categoria" name="categoria" required>
            <option value="base">Base</option>
            <option value="premium">Premium</option>
        </select>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Inserisci</button>
    </form>
    <link rel="stylesheet" href="insert_styles.css">
</body>
</html>
