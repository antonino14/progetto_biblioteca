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

/* Funzione di Login per il lettore */
function login_lettore($email, $password) {
    if (empty($email) || empty($password)) {
        return null;
    }

    $logged = null;
    $db = open_pg_connection();
    try {
        // Hashing della password
        $hashed_password = md5($password); // Assumiamo che la password sia hashata con MD5

        $sql = "SELECT cf_lettore FROM biblioteca.utente_lettore WHERE email = $1 AND password = $2";
        $params = array($email, $hashed_password);

        pg_prepare($db, "check_user_lettore", $sql);
        $result = pg_execute($db, "check_user_lettore", $params);

        if ($row = pg_fetch_assoc($result)) {
            $logged = $row['cf_lettore'];
        } else {
            error_log("Login Lettore fallito per email: $email");
        }
    } catch (Exception $e) {
        error_log("Errore durante il login lettore: " . $e->getMessage());
    } finally {
        close_pg_connection($db);
    }
    return $logged;
}

/* Funzione di Login per il bibliotecario */
function login_bibliotecario($email, $password) {
    if (empty($email) || empty($password)) {
        return null;
    }

    $logged = null;
    $db = open_pg_connection();
    try {
        // Hashing della password
        $hashed_password = md5($password); // Assumiamo che la password sia hashata con MD5

        $sql = "SELECT email FROM biblioteca.utente_bibliotecario WHERE email = $1 AND password = $2";
        $params = array($email, $hashed_password);

        pg_prepare($db, "check_user_bibliotecario", $sql);
        $result = pg_execute($db, "check_user_bibliotecario", $params);

        if ($row = pg_fetch_assoc($result)) {
            $logged = $row['email'];
        } else {
            error_log("Login Bibliotecario fallito per email: $email");
        }
    } catch (Exception $e) {
        error_log("Errore durante il login bibliotecario: " . $e->getMessage());
    } finally {
        close_pg_connection($db);
    }
    return $logged;
}

/* Funzione per cambiare la password */
function cambia_password($email, $new_password) {
    if (empty($email) || empty($new_password)) {
        return false;
    }

    $db = open_pg_connection();
    try {
        // Hashing della nuova password
        $hashed_password = md5($new_password); // Assumiamo che la password sia hashata con MD5

        // Controlla se l'utente è un lettore o un bibliotecario
        $query = "SELECT 'lettore' AS tipo FROM biblioteca.utente_lettore WHERE email = $1
                  UNION
                  SELECT 'bibliotecario' AS tipo FROM biblioteca.utente_bibliotecario WHERE email = $1";
        pg_prepare($db, "check_user_type", $query);
        $result = pg_execute($db, "check_user_type", array($email));

        if ($row = pg_fetch_assoc($result)) {
            $query = $row['tipo'] == 'lettore' ?
                "UPDATE biblioteca.utente_lettore SET password = $1 WHERE email = $2" :
                "UPDATE biblioteca.utente_bibliotecario SET password = $1 WHERE email = $2";

            pg_prepare($db, "update_user", $query);
            $result = pg_execute($db, "update_user", array($hashed_password, $email));
            if ($result) {
                return true;
            } else {
                error_log("Errore durante l'aggiornamento della password per email: $email");
            }
        } else {
            error_log("Cambia Password fallito: Utente non trovato per email: $email");
        }
    } catch (Exception $e) {
        error_log("Errore durante il cambio password: " . $e->getMessage());
    } finally {
        close_pg_connection($db);
    }
    return false;
}
?>
