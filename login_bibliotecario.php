<?php
require_once 'functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $conn = connectDB();

    // Preparazione della query per evitare SQL injection
    $query = "SELECT * FROM biblioteca.utente_bibliotecario WHERE email = $1 AND password = $2";
    $result = pg_query_params($conn, $query, array($email, md5($password)));

    if ($result && pg_num_rows($result) === 1) {
        $user = pg_fetch_assoc($result);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = 'bibliotecario';
        $_SESSION['user_email'] = $user['email'];

        header('Location: gestione_prestiti.php');
        exit();
    } else {
        $error = "Credenziali errate. Riprova.";
    }

    disconnectDB($conn);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Bibliotecario</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Accedi come Bibliotecario</h1>
        <?php if (!empty($error)): ?>
            <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Accedi</button>
        </form>
        <a href="welcome.php" class="back-link">Torna alla scelta</a>
    </div>
</body>
</html>
