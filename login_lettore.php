<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $cf_lettore = loginLettore($email, $password);

    if ($cf_lettore) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = 'lettore';
        $_SESSION['cf_lettore'] = $cf_lettore;
        header('Location: area_prestiti.php');
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
    <title>Login Lettore</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Login Lettore</h1>
    <?php if (isset($error_message)): ?>
        <p class="error"> <?= htmlspecialchars($error_message) ?> </p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Accedi</button>
    </form>
    <a href="welcome.php">Torna alla pagina principale</a>
</body>
</html>

