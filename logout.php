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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Logout Eseguito</h1>
</header>
<nav>
    <a href="welcome.php">Home</a>
    <a href="catalogo.php">Catalogo</a>
    <a href="login_lettore.php">Accedi</a>
</nav>
<div class="container">
    <div class="card">
        <p>Hai effettuato il logout con successo. Torna alla <a href="welcome.php">Home</a>.</p>
    </div>
</div>
<footer>
    &copy; 2024 Biblioteca Comunale. Tutti i diritti riservati.
</footer>
</body>
</html>