<?php
// Inizio della sessione
session_start();

// Distruggo tutte le sessioni
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="login_styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Logout Eseguito</h2>
        <p>Hai effettuato il logout con successo.</p>
        <a href="welcome.php" class="button">Torna alla Home</a>
    </div>
</body>
</html>
