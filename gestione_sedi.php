<?php
include_once 'functions.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'bibliotecario') {
    header('Location: login_bibliotecario.php');
    exit();
}

$db = open_pg_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione'], $_POST['id_sede'])) {
        $azione = $_POST['azione'];
        $id_sede = $_POST['id_sede'];

        if ($azione === 'elimina') {
            $query = "DELETE FROM biblioteca.sede WHERE id = $1";
            $stmt = pg_prepare($db, "elimina_sede", $query);
            if ($stmt) {
                $result = pg_execute($db, "elimina_sede", array($id_sede));

                if ($result) {
                    $_SESSION['message'] = "Sede eliminata con successo.";
                } else {
                    $_SESSION['error'] = "Errore nell'eliminazione della sede.";
                }
            } else {
                $_SESSION['error'] = "Errore nella preparazione della query.";
            }
        }
    }
}

$query = "SELECT id, \"città\", indirizzo FROM biblioteca.sede ORDER BY id";
$result = pg_query($db, $query);
$sedi = $result ? pg_fetch_all($result) : [];

pg_free_result($result);
close_pg_connection($db);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Sedi</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1>Gestione Sedi</h1>

        <form method="GET" action="inserisci_sede.php">
            <button type="submit">Aggiungi Sede</button>
        </form>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message"> <?= htmlspecialchars($_SESSION['message']) ?> </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"> <?= htmlspecialchars($_SESSION['error']) ?> </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID Sede</th>
                    <th>Città</th>
                    <th>Indirizzo</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sedi): ?>
                    <?php foreach ($sedi as $sede): ?>
                        <tr>
                            <td><?= htmlspecialchars($sede['id']) ?></td>
                            <td><?= htmlspecialchars($sede['città']) ?></td>
                            <td><?= htmlspecialchars($sede['indirizzo']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="id_sede" value="<?= htmlspecialchars($sede['id']) ?>">
                                    <button type="submit" name="azione" value="elimina">Elimina</button>
                                </form>
                                <form method="GET" action="inserisci_copia.php">
                                    <input type="hidden" name="id_sede" value="<?= htmlspecialchars($sede['id']) ?>">
                                    <button type="submit">Aggiungi Copia</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nessuna sede trovata.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <link rel="stylesheet" href="gestione_styles.css">
</body>
</html>
