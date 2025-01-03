<?php
define("myhost", "postgres");
define("myuser", "antonino_ottina");
define("mypsw", "Cermenate14@");
define("mydb", "antonino_ottina_test1");

/* Funzione per aprire la connessione con il database */
function open_pg_connection() {
    $connection = "host=" . myhost . " dbname=" . mydb . " user=" . myuser . " password=" . mypsw;
    return pg_connect($connection);
}

/* Funzione per chiudere la connessione con il database */
function close_pg_connection($db) {
    return pg_close($db);
}

/* Funzione di autenticazione utente */
function authenticateUser($requiredUserType) {
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== $requiredUserType) {
        header("Location: login_bibliotecario.php");
        exit();
    }
}

/* Funzione di Login per il lettore */
function login_lettore($email, $password) {
    if (empty($email) || empty($password)) {
        return null;
    }

    $logged = null;
    $db = open_pg_connection();
    $sql = "SELECT cf_lettore, password FROM biblioteca.utente_lettore WHERE email = $1";

    $params = array($email);

    $result = pg_prepare($db, "check_user_lettore", $sql);
    $result = pg_execute($db, "check_user_lettore", $params);

    if ($row = pg_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $logged = $row['cf_lettore'];
        }
    }

    close_pg_connection($db);
    return $logged;
}

/* Funzione di Login per il bibliotecario */
function login_bibliotecario($email, $password) {
    if (empty($email) || empty($password)) {
        return null;
    }

    $logged = null;
    $db = open_pg_connection();
    $sql = "SELECT email, password FROM biblioteca.utente_bibliotecario WHERE email = $1";

    $params = array($email);

    $result = pg_prepare($db, "check_user_bibliotecario", $sql);
    $result = pg_execute($db, "check_user_bibliotecario", $params);

    if ($row = pg_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $logged = $row['email'];
        }
    }

    close_pg_connection($db);
    return $logged;
}

/* Funzione per cambiare la password */
function cambia_password($email, $new_password) {
    if (empty($email) || empty($new_password)) {
        return false;
    }

    $db = open_pg_connection();

    // Controlla se l'utente è un lettore o un bibliotecario
    $query = "SELECT 'lettore' AS tipo FROM biblioteca.utente_lettore WHERE email = $1
              UNION
              SELECT 'bibliotecario' AS tipo FROM biblioteca.utente_bibliotecario WHERE email = $1";
    $result = pg_prepare($db, "check_user_type", $query);
    $result = pg_execute($db, "check_user_type", array($email));

    if ($row = pg_fetch_assoc($result)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        if ($row['tipo'] == 'lettore') {
            $query = "UPDATE biblioteca.utente_lettore SET password = $1 WHERE email = $2";
        } else {
            $query = "UPDATE biblioteca.utente_bibliotecario SET password = $1 WHERE email = $2";
        }

        $result = pg_prepare($db, "update_user", $query);
        $result = pg_execute($db, "update_user", array($hashed_password, $email));
        
        close_pg_connection($db);
        return $result;
    } else {
        close_pg_connection($db);
        return false;
    }
}
?>
