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
    <title>Login Bibliotecario</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Accesso Bibliotecario</h1>
    <?php if (isset($error_message)): ?>
        <p class="error"> <?= htmlspecialchars($error_message) ?> </p>
    <?php endif; ?>
    <form method="POST">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Accedi</button>
    </form>
</body>
</html>
