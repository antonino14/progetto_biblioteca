<?php
session_start();
$user_type = $_SESSION['user_type'] ?? null;
?>
<div class="sidebar">
    <ul>
        <li><a href="welcome.php">Home</a></li>
        <?php if ($user_type === 'bibliotecario'): ?>
            <li><a href="gestione_prestiti.php">Gestione Prestiti</a></li>
            <li><a href="gestione_lettori.php">Gestione Lettori</a></li>
            <li><a href="gestione_libri.php">Gestione Libri</a></li>
            <li><a href="gestione_sedi.php">Gestione Sedi</a></li>
        <?php elseif ($user_type === 'lettore'): ?>
            <li><a href="catalogo.php">Catalogo</a></li>
            <li><a href="area_prestiti.php">I miei Prestiti</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
<div class="main-content">
</div>
