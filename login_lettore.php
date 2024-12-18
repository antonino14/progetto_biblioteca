<?php
require_once 'functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Effettua il login del lettore
    $cf_lettore = login_lettore($email, $password);

    if ($cf_lettore) {
        // Salva le informazioni di sessione
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = 'lettore';
        $_SESSION['cf_lettore'] = $cf_lettore;

        // Redireziona all'area prestiti o alla pagina desiderata
        header('Location: area_prestiti.php');
        exit();
    } else {
        $error = "Credenziali non valide. Riprova.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Lettore</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Login Lettore</h1>
        <?php if (!empty($error)): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Accedi</button>
        </form>
        <a href="welcome.php">Torna alla pagina iniziale</a>
    </div>
</body>
</html>
