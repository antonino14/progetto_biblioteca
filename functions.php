<?php

define("myhost", "postgres");
define("myuser", "antonino_ottina");
define("mypsw", "Cermenate14@");
define("mydb", "antonino_ottina_test1");

/**
 * Funzione per aprire la connessione con il database
 * @return resource|false
 */
function connectDB() {
    $connectionString = "host=" . myhost . " dbname=" . mydb . " user=" . myuser . " password=" . mypsw;
    $db = pg_connect($connectionString);

    if (!$db) {
        die("Errore di connessione al database: " . pg_last_error());
    }
    return $db;
}

/**
 * Funzione di Login per il lettore
 * @param string $email
 * @param string $password
 * @return string|null
 */
function login_lettore($email, $password) {
    if (empty($email) || empty($password)) {
        return null;
    }

    $db = connectDB();
    try {
        $query = "SELECT cf_lettore FROM biblioteca.utente_lettore WHERE email = $1 AND password = $2";
        $params = array($email, md5($password));
        
        $result = pg_query_params($db, $query, $params);

        if ($result && $row = pg_fetch_assoc($result)) {
            return $row['cf_lettore'];
        }
    } catch (Exception $e) {
        error_log("Errore durante il login lettore: " . $e->getMessage());
    } finally {
        pg_close($db);
    }
    return null;
}

/**
 * Funzione di Login per il bibliotecario
 * @param string $email
 * @param string $password
 * @return string|null
 */
function login_bibliotecario($email, $password) {
    if (empty($email) || empty($password)) {
        return null;
    }

    $db = connectDB();
    try {
        $query = "SELECT email FROM biblioteca.utente_bibliotecario WHERE email = $1 AND password = $2";
        $params = array($email, md5($password));

        $result = pg_query_params($db, $query, $params);

        if ($result && $row = pg_fetch_assoc($result)) {
            return $row['email'];
        }
    } catch (Exception $e) {
        error_log("Errore durante il login bibliotecario: " . $e->getMessage());
    } finally {
        pg_close($db);
    }
    return null;
}

/**
 * Funzione per cambiare la password
 * @param string $email
 * @param string $new_password
 * @return bool
 */
function cambia_password($email, $new_password) {
    if (empty($email) || empty($new_password)) {
        return false;
    }

    $db = connectDB();
    try {
        $query = "SELECT 'lettore' AS tipo FROM biblioteca.utente_lettore WHERE email = $1
                  UNION
                  SELECT 'bibliotecario' AS tipo FROM biblioteca.utente_bibliotecario WHERE email = $1";
        $result = pg_query_params($db, $query, array($email));

        if ($result && $row = pg_fetch_assoc($result)) {
            $updateQuery = $row['tipo'] === 'lettore' ?
                "UPDATE biblioteca.utente_lettore SET password = $1 WHERE email = $2" :
                "UPDATE biblioteca.utente_bibliotecario SET password = $1 WHERE email = $2";

            $updateResult = pg_query_params($db, $updateQuery, array(md5($new_password), $email));
            return $updateResult !== false;
        }
    } catch (Exception $e) {
        error_log("Errore durante il cambio password: " . $e->getMessage());
    } finally {
        pg_close($db);
    }
    return false;
}

/**
 * Funzione per chiudere la connessione con il database
 * @param resource $db
 * @return bool
 */
function close_pg_connection($db) {
    return pg_close($db);
}
