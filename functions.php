<?php
// Funzioni aggiornate per la connessione e gestione del login

define("MY_HOST", "postgres");
define("MY_USER", "antonino_ottina");
define("MY_PASSWORD", "Cermenate14@");
define("MY_DB", "antonino_ottina_test1");

/**
 * Apre la connessione al database PostgreSQL.
 * @return resource Connessione al database
 */
function connectDB() {
    $connectionString = "host=" . MY_HOST . " dbname=" . MY_DB . " user=" . MY_USER . " password=" . MY_PASSWORD;
    $db = pg_connect($connectionString);

    if (!$db) {
        die("Errore di connessione al database.");
    }

    return $db;
}

/**
 * Chiude la connessione al database PostgreSQL.
 * @param resource $db Connessione da chiudere
 */
function disconnectDB($db) {
    pg_close($db);
}

/**
 * Verifica il login per un lettore.
 * @param string $email Email del lettore
 * @param string $password Password del lettore
 * @return string|null Codice fiscale del lettore se valido, altrimenti null
 */
function loginLettore($email, $password) {
    $db = connectDB();

    $query = "SELECT cf_lettore FROM biblioteca.utente_lettore WHERE email = $1 AND password = $2";
    $params = array($email, md5($password));

    $result = pg_query_params($db, $query, $params);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        disconnectDB($db);
        return $row['cf_lettore'];
    }

    disconnectDB($db);
    return null;
}

/**
 * Verifica il login per un bibliotecario.
 * @param string $email Email del bibliotecario
 * @param string $password Password del bibliotecario
 * @return string|null Email del bibliotecario se valido, altrimenti null
 */
function loginBibliotecario($email, $password) {
    $db = connectDB();

    $query = "SELECT email FROM biblioteca.utente_bibliotecario WHERE email = $1 AND password = $2";
    $params = array($email, md5($password));

    $result = pg_query_params($db, $query, $params);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        disconnectDB($db);
        return $row['email'];
    }

    disconnectDB($db);
    return null;
}

