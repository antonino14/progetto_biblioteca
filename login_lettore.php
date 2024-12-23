<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $cf_lettore = login_lettore($email, $password);

    if ($cf_lettore) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = 'lettore';
        $_SESSION['cf_lettore'] = $cf_lettore;

        header("Location: area_prestiti.php");
        exit();
    } else {
        $error = "Email o password errati.";
        error_log("Login Lettore fallito per email: $email");
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Lettore</title>
    <link rel="stylesheet" href="login_styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Login Lettore</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="login_lettore.php" method="post">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <a href="cambia_password.php" class="change-password-button">Cambia Password</a>
    </div>
</body>
</html>
