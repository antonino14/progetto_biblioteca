<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verifica credenziali
    $bibliotecario_email = login_bibliotecario($email, $password);

    if ($bibliotecario_email) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = 'bibliotecario';
        $_SESSION['bibliotecario_email'] = $bibliotecario_email;
        header('Location: gestione_libri.php');
        exit();
    } else {
        $error_message = "Credenziali non valide. Riprova.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Bibliotecario</title>
    <link rel="stylesheet" href="login_styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Accesso Bibliotecario</h2>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Inserisci la tua email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Inserisci la tua password" required>

            <button type="submit">Accedi</button>
        </form>
    </div>
</body>
</html>
