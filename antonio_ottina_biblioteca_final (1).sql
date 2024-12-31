--
-- PostgreSQL database dump
--

-- Dumped from database version 13.11 (Debian 13.11-1.pgdg110+1)
-- Dumped by pg_dump version 15.6 (Debian 15.6-0+deb12u1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY biblioteca.utente_lettore DROP CONSTRAINT IF EXISTS utente_lettore_cf_lettore_fkey;
ALTER TABLE IF EXISTS ONLY biblioteca.scritto DROP CONSTRAINT IF EXISTS scritto_libro_fkey;
ALTER TABLE IF EXISTS ONLY biblioteca.scritto DROP CONSTRAINT IF EXISTS scritto_autore_fkey;
ALTER TABLE IF EXISTS ONLY biblioteca.prestito DROP CONSTRAINT IF EXISTS prestito_lettore_fkey;
ALTER TABLE IF EXISTS ONLY biblioteca.copia DROP CONSTRAINT IF EXISTS copia_sede_fkey;
ALTER TABLE IF EXISTS ONLY biblioteca.copia DROP CONSTRAINT IF EXISTS copia_libro_fkey;
ALTER TABLE IF EXISTS ONLY biblioteca.copia DROP CONSTRAINT IF EXISTS copia_cod_prestito_fkey;
DROP TRIGGER IF EXISTS blocco_prestito_ritardatari ON biblioteca.prestito;
DROP TRIGGER IF EXISTS blocco_max_prestiti ON biblioteca.prestito;
DROP TRIGGER IF EXISTS aggiorna_ritardi ON biblioteca.prestito;
DROP TRIGGER IF EXISTS aggiorna_disponibilita_prestito ON biblioteca.prestito;
CREATE OR REPLACE VIEW biblioteca.catalogo AS
SELECT
    NULL::character(13) AS isbn,
    NULL::character varying(100) AS titolo,
    NULL::text AS trama,
    NULL::character varying(100) AS casa_editrice,
    NULL::text AS autori;
ALTER TABLE IF EXISTS ONLY biblioteca.utente_lettore DROP CONSTRAINT IF EXISTS utente_lettore_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.utente_bibliotecario DROP CONSTRAINT IF EXISTS utente_bibliotecario_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.sede DROP CONSTRAINT IF EXISTS sede_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.sede DROP CONSTRAINT IF EXISTS "sede_città_indirizzo_key";
ALTER TABLE IF EXISTS ONLY biblioteca.scritto DROP CONSTRAINT IF EXISTS scritto_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.prestito DROP CONSTRAINT IF EXISTS prestito_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.libro DROP CONSTRAINT IF EXISTS libro_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.lettore DROP CONSTRAINT IF EXISTS lettore_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.copia DROP CONSTRAINT IF EXISTS copia_pkey;
ALTER TABLE IF EXISTS ONLY biblioteca.autore DROP CONSTRAINT IF EXISTS autore_pkey;
DROP TABLE IF EXISTS biblioteca.utente_lettore;
DROP TABLE IF EXISTS biblioteca.utente_bibliotecario;
DROP VIEW IF EXISTS biblioteca.statistiche_sede;
DROP TABLE IF EXISTS biblioteca.scritto;
DROP VIEW IF EXISTS biblioteca.prestiti_aperti;
DROP VIEW IF EXISTS biblioteca.num_totale_isbn;
DROP VIEW IF EXISTS biblioteca.num_totale_copie;
DROP VIEW IF EXISTS biblioteca.num_prestiti_in_corso;
DROP TABLE IF EXISTS biblioteca.libro;
DROP VIEW IF EXISTS biblioteca.libri_in_ritardo;
DROP TABLE IF EXISTS biblioteca.sede;
DROP TABLE IF EXISTS biblioteca.prestito;
DROP TABLE IF EXISTS biblioteca.lettore;
DROP TABLE IF EXISTS biblioteca.copia;
DROP VIEW IF EXISTS biblioteca.catalogo;
DROP TABLE IF EXISTS biblioteca.autore;
DROP FUNCTION IF EXISTS biblioteca.update_ritardi();
DROP FUNCTION IF EXISTS biblioteca.seleziona_copia(isbn character, sede_preferita character);
DROP FUNCTION IF EXISTS biblioteca.proroga_prestito(p_cod_prestito character, p_giorni integer);
DROP FUNCTION IF EXISTS biblioteca.check_ritardi();
DROP FUNCTION IF EXISTS biblioteca.check_max_prestiti();
DROP FUNCTION IF EXISTS biblioteca.aggiorna_disponibilita();
-- *not* dropping schema, since initdb creates it
DROP SCHEMA IF EXISTS biblioteca;
DROP SCHEMA IF EXISTS antonino_ottina;
--
-- Name: antonino_ottina; Type: SCHEMA; Schema: -; Owner: antonino_ottina
--

CREATE SCHEMA antonino_ottina;


ALTER SCHEMA antonino_ottina OWNER TO antonino_ottina;

--
-- Name: biblioteca; Type: SCHEMA; Schema: -; Owner: antonino_ottina
--

CREATE SCHEMA biblioteca;


ALTER SCHEMA biblioteca OWNER TO antonino_ottina;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO postgres;

--
-- Name: aggiorna_disponibilita(); Type: FUNCTION; Schema: biblioteca; Owner: antonino_ottina
--

CREATE OR REPLACE FUNCTION biblioteca.aggiorna_disponibilita() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- La copia del libro torna disponibile rimuovendo il codice prestito
    UPDATE biblioteca.copia SET cod_prestito = NULL WHERE cod_prestito = OLD.cod_prestito;

    -- Si chiude il prestito settando a FALSE il campo prestito_aperto
    UPDATE biblioteca.prestito SET prestito_aperto = FALSE WHERE cod_prestito = OLD.cod_prestito;

    -- Se la data di restituzione è successiva alla data di fine prestito, incrementa i ritardi
    IF NEW.data_restituzione > NEW.data_fine THEN
        UPDATE biblioteca.lettore
        SET num_ritardi = num_ritardi + 1
        WHERE CF = NEW.lettore;
    END IF;

    RETURN OLD;
END;
$$;

ALTER FUNCTION biblioteca.aggiorna_disponibilita() OWNER TO antonino_ottina;
--
-- Name: check_max_prestiti(); Type: FUNCTION; Schema: biblioteca; Owner: antonino_ottina
--

CREATE FUNCTION biblioteca.check_max_prestiti() RETURNS trigger
    LANGUAGE plpgsql
    AS $$DECLARE
    max_prestiti INT;
    prestiti_correnti INT;
BEGIN
    -- Determina il numero massimo di prestiti consentiti in base alla categoria del lettore
    IF (SELECT categoria FROM biblioteca.lettore WHERE CF = NEW.lettore) = 'base' THEN
        max_prestiti := 3;
    ELSE
        max_prestiti := 5;
    END IF;
    
    -- Conta il numero di prestiti correnti del lettore
    prestiti_correnti := (SELECT COUNT(*) FROM biblioteca.prestito WHERE lettore = NEW.lettore AND prestito_aperto = TRUE);
    
    -- Verifica se il lettore ha raggiunto il numero massimo di prestiti
    IF prestiti_correnti = max_prestiti THEN
        RAISE INFO 'Numero massimo di prestiti raggiunto per questo lettore';
        RETURN NULL;
    ELSE 
        RETURN NEW;
    END IF;
END;
$$;


ALTER FUNCTION biblioteca.check_max_prestiti() OWNER TO antonino_ottina;

--
-- Name: check_ritardi(); Type: FUNCTION; Schema: biblioteca; Owner: antonino_ottina
--

CREATE FUNCTION biblioteca.check_ritardi() RETURNS trigger
    LANGUAGE plpgsql
    AS $$BEGIN
    IF (SELECT num_ritardi FROM biblioteca.lettore WHERE CF = NEW.lettore) >= 5 THEN
        RAISE EXCEPTION 'Prestito non concesso!!! Il lettore ha ha troppi ritardi all''attivo';
        RETURN NULL;
    ELSE 
        RETURN NEW;
    END IF;
END;
$$;


ALTER FUNCTION biblioteca.check_ritardi() OWNER TO antonino_ottina;

--
-- Name: proroga_prestito(character, integer); Type: FUNCTION; Schema: biblioteca; Owner: antonino_ottina
--

CREATE FUNCTION biblioteca.proroga_prestito(p_cod_prestito character, p_giorni integer) RETURNS boolean
    LANGUAGE plpgsql
    AS $$DECLARE
    fine_prestito DATE;
BEGIN
    -- Recupera la data di fine del prestito
    SELECT data_fine INTO fine_prestito
    FROM biblioteca.prestito
    WHERE cod_prestito = p_cod_prestito;

    -- Controlla se il prestito è già in ritardo
    IF fine_prestito < CURRENT_DATE THEN
        RAISE EXCEPTION 'Il prestito è già in ritardo e non può essere prorogato';
    END IF;

    -- Proroga la data di fine del prestito
    UPDATE biblioteca.prestito
    SET data_fine = fine_prestito + INTERVAL '1 day' * p_giorni
    WHERE cod_prestito = p_cod_prestito;

    RETURN TRUE;

EXCEPTION
    WHEN OTHERS THEN
        RETURN FALSE;
END;
$$;


ALTER FUNCTION biblioteca.proroga_prestito(p_cod_prestito character, p_giorni integer) OWNER TO antonino_ottina;

--
-- Name: seleziona_copia(character, character); Type: FUNCTION; Schema: biblioteca; Owner: antonino_ottina
--

CREATE FUNCTION biblioteca.seleziona_copia(isbn character, sede_preferita character) RETURNS character
    LANGUAGE plpgsql
    AS $$DECLARE
    copia_selezionata CHAR(6);
BEGIN
    -- Cerca copia disponibile nella sede preferita
    SELECT id INTO copia_selezionata 
    FROM biblioteca.copia
    WHERE libro = isbn AND sede = sede_preferita AND cod_prestito IS NULL
    LIMIT 1;

    IF copia_selezionata IS NULL THEN
        -- Se non ci sono copie disponibili nella sede preferita, cerca in altre sedi
        SELECT id INTO copia_selezionata
        FROM biblioteca.copia
        WHERE libro = isbn AND cod_prestito IS NULL
        LIMIT 1;

        -- Avvisa il lettore che il libro verrà preso da un'altra sede
        RAISE NOTICE 'Il libro verrà prestato da una sede diversa da quella specificata.';
    END IF;

    RETURN copia_selezionata;
END;
$$;


ALTER FUNCTION biblioteca.seleziona_copia(isbn character, sede_preferita character) OWNER TO antonino_ottina;

--
-- Name: update_ritardi(); Type: FUNCTION; Schema: biblioteca; Owner: antonino_ottina
--

CREATE FUNCTION biblioteca.update_ritardi() RETURNS trigger
    LANGUAGE plpgsql
    AS $$BEGIN
    -- Controlla se la restituzione è avvenuta in ritardo
    IF NEW.data_restituzione > NEW.data_fine THEN
        -- Incrementa il contatore dei ritardi del lettore
        UPDATE biblioteca.lettore
        SET num_ritardi = num_ritardi + 1
        WHERE CF = NEW.lettore;
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION biblioteca.update_ritardi() OWNER TO antonino_ottina;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: autore; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.autore (
    id character(6) NOT NULL,
    nome character varying(40) NOT NULL,
    cognome character varying(40) NOT NULL,
    data_nascita date NOT NULL,
    data_morte date,
    biografia character varying(200) NOT NULL
);


ALTER TABLE biblioteca.autore OWNER TO antonino_ottina;

--
-- Name: catalogo; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.catalogo AS

CREATE OR REPLACE VIEW biblioteca.catalogo AS
 SELECT l.isbn,
    l.titolo,
    l.trama,
    l.casa_editrice,
    string_agg(concat(a.nome, ' ', a.cognome), ', '::text) AS autori
   FROM ((biblioteca.libro l
     LEFT JOIN biblioteca.scritto s ON ((l.isbn = s.libro)))
     LEFT JOIN biblioteca.autore a ON ((s.autore = a.id)))
  GROUP BY l.isbn
  ORDER BY l.titolo;

ALTER TABLE biblioteca.catalogo OWNER TO antonino_ottina;

--
-- Name: VIEW catalogo; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.catalogo IS 'Vista per la visualizzazione del catalogo';


--
-- Name: copia; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.copia (
    id character(6) NOT NULL,
    libro character(13) NOT NULL,
    sede character(6),
    cod_prestito character(6) DEFAULT NULL::bpchar
);


ALTER TABLE biblioteca.copia OWNER TO antonino_ottina;

--
-- Name: lettore; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.lettore (
    cf character(16) NOT NULL,
    nome character varying(40) NOT NULL,
    cognome character varying(40) NOT NULL,
    categoria character(7) NOT NULL,
    num_ritardi integer DEFAULT 0,
    CONSTRAINT lettore_categoria_check CHECK ((categoria = ANY (ARRAY['base'::bpchar, 'premium'::bpchar])))
);


ALTER TABLE biblioteca.lettore OWNER TO antonino_ottina;

--
-- Name: prestito; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.prestito (
    cod_prestito character(6) NOT NULL,
    data_inizio date NOT NULL,
    data_fine date NOT NULL,
    data_restituzione date,
    prestito_aperto boolean NOT NULL,
    lettore character(16) NOT NULL,
    CONSTRAINT check_date_prestito CHECK ((data_fine > data_inizio))
);


ALTER TABLE biblioteca.prestito OWNER TO antonino_ottina;

--
-- Name: sede; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.sede (
    id character(6) NOT NULL,
    "città" character varying(40) NOT NULL,
    indirizzo character varying(100) NOT NULL
);


ALTER TABLE biblioteca.sede OWNER TO antonino_ottina;

--
-- Name: libri_in_ritardo; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.libri_in_ritardo AS
 SELECT s.id,
    s."città",
    s.indirizzo,
    c.libro,
    p.cod_prestito,
    p.data_fine,
    l.nome AS nome_lettore,
    l.cognome AS cognome_lettore
   FROM (((biblioteca.sede s
     JOIN biblioteca.copia c ON ((s.id = c.sede)))
     JOIN biblioteca.prestito p ON ((c.cod_prestito = p.cod_prestito)))
     JOIN biblioteca.lettore l ON ((p.lettore = l.cf)))
  WHERE ((p.prestito_aperto = true) AND (p.data_fine < CURRENT_DATE))
  ORDER BY s.id;


ALTER TABLE biblioteca.libri_in_ritardo OWNER TO antonino_ottina;

--
-- Name: VIEW libri_in_ritardo; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.libri_in_ritardo IS 'Vista per i libri in prestito in ritardo per ogni sede';


--
-- Name: libro; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.libro (
    isbn character(13) NOT NULL,
    titolo character varying(100) NOT NULL,
    trama text NOT NULL,
    casa_editrice character varying(100) NOT NULL
);


ALTER TABLE biblioteca.libro OWNER TO antonino_ottina;

--
-- Name: num_prestiti_in_corso; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.num_prestiti_in_corso AS
 SELECT c.sede,
    count(*) AS prestiti_in_corso
   FROM (biblioteca.copia c
     JOIN biblioteca.prestito p ON ((c.cod_prestito = p.cod_prestito)))
  WHERE (p.prestito_aperto = true)
  GROUP BY c.sede
  ORDER BY c.sede;


ALTER TABLE biblioteca.num_prestiti_in_corso OWNER TO antonino_ottina;

--
-- Name: VIEW num_prestiti_in_corso; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.num_prestiti_in_corso IS 'Vista per il numero totale di prestiti in corso per ogni sede';


--
-- Name: num_totale_copie; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.num_totale_copie AS
 SELECT copia.sede,
    count(*) AS num_totale_copie
   FROM biblioteca.copia
  GROUP BY copia.sede
  ORDER BY copia.sede;


ALTER TABLE biblioteca.num_totale_copie OWNER TO antonino_ottina;

--
-- Name: VIEW num_totale_copie; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.num_totale_copie IS 'Vista per il numero totale delle copie per ogni sede';


--
-- Name: num_totale_isbn; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.num_totale_isbn AS
 SELECT copia.sede,
    count(DISTINCT copia.libro) AS num_totale_isbn
   FROM biblioteca.copia
  GROUP BY copia.sede
  ORDER BY copia.sede;


ALTER TABLE biblioteca.num_totale_isbn OWNER TO antonino_ottina;

--
-- Name: VIEW num_totale_isbn; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.num_totale_isbn IS 'Vista per il numero totale dei codici ISBN per ogni sede';


--
-- Name: prestiti_aperti; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.prestiti_aperti AS
 SELECT p.cod_prestito,
    p.data_inizio,
    p.data_fine,
    concat(l.nome, ' ', l.cognome) AS lettore,
    c.libro,
    c.id AS copia
   FROM ((biblioteca.lettore l
     JOIN biblioteca.prestito p ON ((l.cf = p.lettore)))
     JOIN biblioteca.copia c ON ((p.cod_prestito = c.cod_prestito)))
  WHERE (p.prestito_aperto = true)
  ORDER BY p.cod_prestito;


ALTER TABLE biblioteca.prestiti_aperti OWNER TO antonino_ottina;

--
-- Name: VIEW prestiti_aperti; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.prestiti_aperti IS 'Vista per tutti i prestiti aperti';


--
-- Name: scritto; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.scritto (
    autore character(6) NOT NULL,
    libro character(13) NOT NULL
);


ALTER TABLE biblioteca.scritto OWNER TO antonino_ottina;

--
-- Name: statistiche_sede; Type: VIEW; Schema: biblioteca; Owner: antonino_ottina
--

CREATE VIEW biblioteca.statistiche_sede AS
 SELECT s.id AS id_sede,
    s."città",
    s.indirizzo,
    c.num_totale_copie,
    i.num_totale_isbn,
    p.prestiti_in_corso
   FROM (((biblioteca.sede s
     LEFT JOIN biblioteca.num_totale_copie c ON ((s.id = c.sede)))
     LEFT JOIN biblioteca.num_totale_isbn i ON ((s.id = i.sede)))
     LEFT JOIN biblioteca.num_prestiti_in_corso p ON ((s.id = p.sede)))
  ORDER BY s.id;


ALTER TABLE biblioteca.statistiche_sede OWNER TO antonino_ottina;

--
-- Name: VIEW statistiche_sede; Type: COMMENT; Schema: biblioteca; Owner: antonino_ottina
--

COMMENT ON VIEW biblioteca.statistiche_sede IS 'Vista complessiva per le statistiche per ogni sede con città e indirizzo';


--
-- Name: utente_bibliotecario; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.utente_bibliotecario (
    email character varying(100) NOT NULL,
    password character varying(255) NOT NULL
);


ALTER TABLE biblioteca.utente_bibliotecario OWNER TO antonino_ottina;

--
-- Name: utente_lettore; Type: TABLE; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TABLE biblioteca.utente_lettore (
    email character varying(100) NOT NULL,
    password character varying(255) NOT NULL,
    cf_lettore character(16) NOT NULL
);


ALTER TABLE biblioteca.utente_lettore OWNER TO antonino_ottina;

--
-- Data for Name: autore; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.autore VALUES ('A00001', 'Gabriel', 'Garcia Marquez', '1927-03-06', '2014-04-17', 'Autore colombiano, premio Nobel.');
INSERT INTO biblioteca.autore VALUES ('A00002', 'George', 'Orwell', '1903-06-25', '1950-01-21', 'Autore britannico di narrativa e saggistica.');
INSERT INTO biblioteca.autore VALUES ('A00003', 'Jane', 'Austen', '1775-12-16', '1817-07-18', 'Scrittrice inglese, pioniera del romanzo moderno.');
INSERT INTO biblioteca.autore VALUES ('A00004', 'J.K.', 'Rowling', '1965-07-31', NULL, 'Autrice della serie di Harry Potter.');
INSERT INTO biblioteca.autore VALUES ('A00005', 'F. Scott', 'Fitzgerald', '1896-09-24', '1940-12-21', 'Scrittore americano noto per "Il grande Gatsby".');
INSERT INTO biblioteca.autore VALUES ('A00006', 'Ernest', 'Hemingway', '1899-07-21', '1961-07-02', 'Scrittore americano, premio Nobel.');
INSERT INTO biblioteca.autore VALUES ('A00007', 'Mark', 'Twain', '1835-11-30', '1910-04-21', 'Autore americano noto per "Le avventure di Tom Sawyer".');
INSERT INTO biblioteca.autore VALUES ('A00008', 'Leo', 'Tolstoy', '1828-09-09', '1910-11-20', 'Scrittore russo, autore di "Guerra e pace".');
INSERT INTO biblioteca.autore VALUES ('A00009', 'Fyodor', 'Dostoevsky', '1821-11-11', '1881-02-09', 'Scrittore russo noto per "I fratelli Karamazov".');
INSERT INTO biblioteca.autore VALUES ('A00010', 'Herman', 'Melville', '1819-08-01', '1891-09-28', 'Scrittore americano noto per "Moby-Dick".');
INSERT INTO biblioteca.autore VALUES ('A00011', 'Charles', 'Dickens', '1812-02-07', '1870-06-09', 'Scrittore inglese, autore di "Oliver Twist".');
INSERT INTO biblioteca.autore VALUES ('A00012', 'Virginia', 'Woolf', '1882-01-25', '1941-03-28', 'Scrittrice inglese, pioniera del modernismo.');
INSERT INTO biblioteca.autore VALUES ('A00013', 'William', 'Shakespeare', '1564-04-23', '1616-04-23', 'Drammaturgo e poeta inglese.');
INSERT INTO biblioteca.autore VALUES ('A00014', 'Homer', 'Simpson', '1956-05-12', NULL, 'Scrittore immaginario di Springfield.');
INSERT INTO biblioteca.autore VALUES ('A00015', 'Agatha', 'Christie', '1890-09-15', '1976-01-12', 'Scrittrice britannica di romanzi gialli.');
INSERT INTO biblioteca.autore VALUES ('A00016', 'J.R.R.', 'Tolkien', '1892-01-03', '1973-09-02', 'Autore de "Il Signore degli Anelli".');
INSERT INTO biblioteca.autore VALUES ('A00017', 'Isaac', 'Asimov', '1920-01-02', '1992-04-06', 'Scrittore e biochimico russo-americano.');
INSERT INTO biblioteca.autore VALUES ('A00018', 'Arthur', 'Conan Doyle', '1859-05-22', '1930-07-07', 'Scrittore britannico, creatore di Sherlock Holmes.');
INSERT INTO biblioteca.autore VALUES ('A00019', 'Franz', 'Kafka', '1883-07-03', '1924-06-03', 'Scrittore boemo di lingua tedesca.');
INSERT INTO biblioteca.autore VALUES ('A00020', 'Jules', 'Verne', '1828-02-08', '1905-03-24', 'Scrittore francese, pioniere della fantascienza.');
INSERT INTO biblioteca.autore VALUES ('A00021', 'Ned', 'Flanders', '1950-11-06', NULL, 'Scrittore immaginario di Springfield e vicino di Homer Simpson');
INSERT INTO biblioteca.autore VALUES ('A00022', 'Giovannino', 'Guareschi', '1908-05-01', '1968-07-22', 'È considerato uno degli scrittori italiani più popolari ed influenti di sempre');


--
-- Data for Name: copia; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.copia VALUES ('C00008', '9780199536894', 'S00008', NULL);
INSERT INTO biblioteca.copia VALUES ('C00029', '9780140449136', 'S00009', NULL);
INSERT INTO biblioteca.copia VALUES ('C00009', '9780140449136', 'S00009', NULL);
INSERT INTO biblioteca.copia VALUES ('C00010', '9780142437247', 'S00010', NULL);
INSERT INTO biblioteca.copia VALUES ('C00030', '9780142437247', 'S00010', NULL);
INSERT INTO biblioteca.copia VALUES ('C00011', '9780141439563', 'S00011', NULL);
INSERT INTO biblioteca.copia VALUES ('C00012', '9780156030410', 'S00012', NULL);
INSERT INTO biblioteca.copia VALUES ('C00013', '9780486282114', 'S00013', NULL);
INSERT INTO biblioteca.copia VALUES ('C00015', '9780062073504', 'S00015', NULL);
INSERT INTO biblioteca.copia VALUES ('C00017', '9780553382563', 'S00017', NULL);
INSERT INTO biblioteca.copia VALUES ('C00018', '9780241952882', 'S00018', NULL);
INSERT INTO biblioteca.copia VALUES ('C00019', '9780805210408', 'S00019', NULL);
INSERT INTO biblioteca.copia VALUES ('C00020', '9780140445145', 'S00020', NULL);
INSERT INTO biblioteca.copia VALUES ('C00016', '9780547928227', 'S00016', NULL);
INSERT INTO biblioteca.copia VALUES ('C00028', '9780199536894', 'S00008', NULL);
INSERT INTO biblioteca.copia VALUES ('C00033', '9780439139601', 'S00014', NULL);
INSERT INTO biblioteca.copia VALUES ('C00021', '9780141185064', 'S00001', NULL);
INSERT INTO biblioteca.copia VALUES ('C00001', '9780141185064', 'S00001', NULL);
INSERT INTO biblioteca.copia VALUES ('C00031', '9780451524935', 'S00001', NULL);
INSERT INTO biblioteca.copia VALUES ('C00032', '9780451524935', 'S00001', NULL);
INSERT INTO biblioteca.copia VALUES ('C00022', '9780451524935', 'S00002', NULL);
INSERT INTO biblioteca.copia VALUES ('C00002', '9780451524935', 'S00002', NULL);
INSERT INTO biblioteca.copia VALUES ('C00023', '9780439139601', 'S00003', NULL);
INSERT INTO biblioteca.copia VALUES ('C00003', '9780141439518', 'S00003', NULL);
INSERT INTO biblioteca.copia VALUES ('C00004', '9780439139601', 'S00004', NULL);
INSERT INTO biblioteca.copia VALUES ('C00024', '9780439139601', 'S00004', NULL);
INSERT INTO biblioteca.copia VALUES ('C00005', '9780743273565', 'S00005', NULL);
INSERT INTO biblioteca.copia VALUES ('C00025', '9780743273565', 'S00005', NULL);
INSERT INTO biblioteca.copia VALUES ('C00006', '9780684830490', 'S00006', NULL);
INSERT INTO biblioteca.copia VALUES ('C00026', '9780684830490', 'S00006', NULL);
INSERT INTO biblioteca.copia VALUES ('C00007', '9780486280615', 'S00007', NULL);
INSERT INTO biblioteca.copia VALUES ('C00027', '9780486280615', 'S00007', NULL);
INSERT INTO biblioteca.copia VALUES('C00034', '9780141185064', 'S00001',  NULL);
INSERT INTO biblioteca.copia VALUES('C00035', '9780141185064', 'S00002',  NULL);
INSERT INTO biblioteca.copia VALUES('C00036', '9780451524935', 'S00003',  NULL);
INSERT INTO biblioteca.copia VALUES('C00037', '9780451524935', 'S00004',  NULL);
INSERT INTO biblioteca.copia VALUES('C00038', '9780439139601', 'S00005',  NULL);
INSERT INTO biblioteca.copia VALUES('C00039', '9780439139601', 'S00006',  NULL);


--
-- Data for Name: lettore; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.lettore VALUES ('CF00000000000004', 'Pietro', 'Strambini', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000011', 'Dotty', 'Borgese', 'base   ', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000014', 'Luca', 'Todaro', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000010', 'Francesca', 'Terraneo', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000002', 'Giovanni', 'Gioia', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000007', 'Matteo', 'Monti', 'base   ', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000006', 'Filippo', 'Oltolini', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000012', 'Rocco', 'Costantino', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000013', 'Carlo', 'Iannello', 'base   ', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000015', 'Gabriel', 'Naso', 'base   ', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000005', 'Edoardo', 'Verga', 'base   ', 1);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000003', 'Filippo', 'Sorze', 'base   ', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000009', 'Matteo', 'Cardani', 'base   ', 1);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000008', 'Lamine', 'Sangare', 'premium', 0);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000001', 'Gianluca', 'Colombo', 'base   ', 1);
INSERT INTO biblioteca.lettore VALUES ('CF00000000000016', 'Francesco', 'Ottina', 'base   ', 1);


--
-- Data for Name: libro; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.libro VALUES ('9780141185064', 'Cent''anni di solitudine', 'Trama del libro Cent''anni di solitudine.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9780451524935', '1984', 'Trama del libro 1984.', 'Signet Classic');
INSERT INTO biblioteca.libro VALUES ('9780141439518', 'Orgoglio e pregiudizio', 'Trama del libro Orgoglio e pregiudizio.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9780439139601', 'Harry Potter e il prigioniero di Azkaban', 'Trama del libro Harry Potter e il prigioniero di Azkaban.', 'Scholastic');
INSERT INTO biblioteca.libro VALUES ('9780743273565', 'Il grande Gatsby', 'Trama del libro Il grande Gatsby.', 'Scribner');
INSERT INTO biblioteca.libro VALUES ('9780684830490', 'Il vecchio e il mare', 'Trama del libro Il vecchio e il mare.', 'Scribner');
INSERT INTO biblioteca.libro VALUES ('9780486280615', 'Le avventure di Tom Sawyer', 'Trama del libro Le avventure di Tom Sawyer.', 'Dover Publications');
INSERT INTO biblioteca.libro VALUES ('9780199536894', 'Guerra e pace', 'Trama del libro Guerra e pace.', 'Oxford University Press');
INSERT INTO biblioteca.libro VALUES ('9780140449136', 'I fratelli Karamazov', 'Trama del libro I fratelli Karamazov.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9780142437247', 'Moby-Dick', 'Trama del libro Moby-Dick.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9780141439563', 'Oliver Twist', 'Trama del libro Oliver Twist.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9780156030410', 'La signora Dalloway', 'Trama del libro La signora Dalloway.', 'Harcourt');
INSERT INTO biblioteca.libro VALUES ('9780486282114', 'Amleto', 'Trama del libro Amleto.', 'Dover Publications');
INSERT INTO biblioteca.libro VALUES ('9780062073504', 'Dieci piccoli indiani', 'Trama del libro Dieci piccoli indiani.', 'William Morrow Paperbacks');
INSERT INTO biblioteca.libro VALUES ('9780547928227', 'Il Signore degli Anelli', 'Trama del libro Il Signore degli Anelli.', 'Houghton Mifflin Harcourt');
INSERT INTO biblioteca.libro VALUES ('9780553382563', 'Io, Robot', 'Trama del libro Io, Robot.', 'Spectra');
INSERT INTO biblioteca.libro VALUES ('9780241952882', 'Uno studio in rosso', 'Trama del libro Uno studio in rosso.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9780805210408', 'Il processo', 'Trama del libro Il processo.', 'Schocken Books Inc');
INSERT INTO biblioteca.libro VALUES ('9780140445145', 'Ventimila leghe sotto i mari', 'Trama del libro Ventimila leghe sotto i mari.', 'Penguin Classics');
INSERT INTO biblioteca.libro VALUES ('9788858645123', 'Don Camillo e il suo gregge', 'Trama del libro Don Camillo e il suo gregge', 'Rizzoli Libri');
INSERT INTO biblioteca.libro VALUES ('9780062872335', 'Springfield Confidential', 'Trama del libro Springfield Confidential', 'Dey Street Books');


--
-- Data for Name: prestito; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--


INSERT INTO biblioteca.prestito VALUES ('P00004', '2024-01-04', '2024-01-18', NULL, false, 'CF00000000000004');
INSERT INTO biblioteca.prestito VALUES ('P00006', '2024-01-06', '2024-01-20', NULL, false, 'CF00000000000006');
INSERT INTO biblioteca.prestito VALUES ('P00007', '2024-01-07', '2024-01-21', NULL, false, 'CF00000000000007');
INSERT INTO biblioteca.prestito VALUES ('P00010', '2024-01-10', '2024-01-24', NULL, false, 'CF00000000000010');
INSERT INTO biblioteca.prestito VALUES ('P00011', '2024-01-11', '2024-01-25', NULL, false, 'CF00000000000011');
INSERT INTO biblioteca.prestito VALUES ('P00013', '2024-01-13', '2024-01-27', NULL, false, 'CF00000000000013');
INSERT INTO biblioteca.prestito VALUES ('P00014', '2024-01-14', '2024-01-28', NULL, false, 'CF00000000000014');
INSERT INTO biblioteca.prestito VALUES ('P00015', '2024-01-15', '2024-01-29', NULL, false, 'CF00000000000015');
INSERT INTO biblioteca.prestito VALUES ('P00017', '2024-01-17', '2024-01-31', NULL, false, 'CF00000000000002');
INSERT INTO biblioteca.prestito VALUES ('P00019', '2024-01-19', '2024-02-02', NULL, false, 'CF00000000000004');
INSERT INTO biblioteca.prestito VALUES ('P00025', '2024-07-10', '2024-08-09', NULL, false, 'CF00000000000016');
INSERT INTO biblioteca.prestito VALUES ('P00002', '2024-01-02', '2024-01-16', NULL, false, 'CF00000000000002');
INSERT INTO biblioteca.prestito VALUES ('P00012', '2024-01-12', '2024-01-26', NULL, false, 'CF00000000000012');
INSERT INTO biblioteca.prestito VALUES ('P00018', '2024-01-18', '2024-02-01', NULL, false, 'CF00000000000003');
INSERT INTO biblioteca.prestito VALUES ('P00003', '2024-01-03', '2024-01-17', '2024-01-20', false, 'CF00000000000003');
INSERT INTO biblioteca.prestito VALUES ('P00008', '2024-01-08', '2024-01-22', '2024-01-27', false, 'CF00000000000008');
INSERT INTO biblioteca.prestito VALUES ('P00005', '2024-01-05', '2024-01-19', '2024-01-22', false, 'CF00000000000005');
INSERT INTO biblioteca.prestito VALUES ('P00021', '2024-06-14', '2024-07-03', '2024-07-06', false, 'CF00000000000016');
INSERT INTO biblioteca.prestito VALUES ('P00020', '2024-06-15', '2024-07-14', NULL, false, 'CF00000000000005');
INSERT INTO biblioteca.prestito VALUES ('P00001', '2024-01-01', '2024-03-15', NULL, false, 'CF00000000000001');
INSERT INTO biblioteca.prestito VALUES ('P00009', '2024-01-09', '2024-01-23', '2024-07-07', false, 'CF00000000000009');
INSERT INTO biblioteca.prestito VALUES ('P00024', '2024-07-07', '2024-08-12', NULL, false, 'CF00000000000016');
INSERT INTO biblioteca.prestito VALUES ('P00022', '2024-07-06', '2024-08-25', NULL, false, 'CF00000000000016');
INSERT INTO biblioteca.prestito VALUES ('P00016', '2024-01-16', '2024-01-30', '2024-07-10', false, 'CF00000000000001');
INSERT INTO biblioteca.prestito VALUES ('P00023', '2024-07-07', '2024-08-14', '2024-07-10', false, 'CF00000000000016');


--
-- Data for Name: scritto; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.scritto VALUES ('A00001', '9780141185064');
INSERT INTO biblioteca.scritto VALUES ('A00002', '9780451524935');
INSERT INTO biblioteca.scritto VALUES ('A00003', '9780141439518');
INSERT INTO biblioteca.scritto VALUES ('A00004', '9780439139601');
INSERT INTO biblioteca.scritto VALUES ('A00005', '9780743273565');
INSERT INTO biblioteca.scritto VALUES ('A00006', '9780684830490');
INSERT INTO biblioteca.scritto VALUES ('A00007', '9780486280615');
INSERT INTO biblioteca.scritto VALUES ('A00008', '9780199536894');
INSERT INTO biblioteca.scritto VALUES ('A00009', '9780140449136');
INSERT INTO biblioteca.scritto VALUES ('A00010', '9780142437247');
INSERT INTO biblioteca.scritto VALUES ('A00011', '9780141439563');
INSERT INTO biblioteca.scritto VALUES ('A00012', '9780156030410');
INSERT INTO biblioteca.scritto VALUES ('A00013', '9780486282114');
INSERT INTO biblioteca.scritto VALUES ('A00015', '9780062073504');
INSERT INTO biblioteca.scritto VALUES ('A00016', '9780547928227');
INSERT INTO biblioteca.scritto VALUES ('A00017', '9780553382563');
INSERT INTO biblioteca.scritto VALUES ('A00018', '9780241952882');
INSERT INTO biblioteca.scritto VALUES ('A00019', '9780805210408');
INSERT INTO biblioteca.scritto VALUES ('A00020', '9780140445145');
INSERT INTO biblioteca.scritto VALUES ('A00022', '9788858645123');
INSERT INTO biblioteca.scritto VALUES ('A00014', '9780062872335');
INSERT INTO biblioteca.scritto VALUES ('A00021', '9780062872335');


--
-- Data for Name: sede; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.sede VALUES ('S00001', 'Milano', 'Via Roma 1');
INSERT INTO biblioteca.sede VALUES ('S00002', 'Roma', 'Via Milano 2');
INSERT INTO biblioteca.sede VALUES ('S00003', 'Torino', 'Corso Francia 3');
INSERT INTO biblioteca.sede VALUES ('S00004', 'Firenze', 'Piazza della Signoria 4');
INSERT INTO biblioteca.sede VALUES ('S00005', 'Napoli', 'Via Toledo 5');
INSERT INTO biblioteca.sede VALUES ('S00006', 'Bologna', 'Via Rizzoli 6');
INSERT INTO biblioteca.sede VALUES ('S00007', 'Venezia', 'Piazza San Marco 7');
INSERT INTO biblioteca.sede VALUES ('S00008', 'Verona', 'Via Mazzini 8');
INSERT INTO biblioteca.sede VALUES ('S00009', 'Palermo', 'Corso Vittorio Emanuele 9');
INSERT INTO biblioteca.sede VALUES ('S00010', 'Genova', 'Via Garibaldi 10');
INSERT INTO biblioteca.sede VALUES ('S00011', 'Pisa', 'Lungarno 11');
INSERT INTO biblioteca.sede VALUES ('S00012', 'Siena', 'Piazza del Campo 12');
INSERT INTO biblioteca.sede VALUES ('S00013', 'Perugia', 'Corso Vannucci 13');
INSERT INTO biblioteca.sede VALUES ('S00015', 'Modena', 'Via Emilia 15');
INSERT INTO biblioteca.sede VALUES ('S00016', 'Rimini', 'Corso d''Augusto 16');
INSERT INTO biblioteca.sede VALUES ('S00017', 'Trieste', 'Piazza Unità d''Italia 17');
INSERT INTO biblioteca.sede VALUES ('S00018', 'Ancona', 'Corso Garibaldi 18');
INSERT INTO biblioteca.sede VALUES ('S00019', 'Milano', 'Via Belenzani 19');
INSERT INTO biblioteca.sede VALUES ('S00020', 'Roma', 'Piazza Walther 20');
INSERT INTO biblioteca.sede VALUES ('S00014', 'Milano', 'Via S. Domenico Savio 3');


--
-- Data for Name: utente_bibliotecario; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.utente_bibliotecario VALUES ('antonino.ottina@studenti.unimi.it', '81ebe228e8a2c54bb5f1b97c34b7f321');
INSERT INTO biblioteca.utente_bibliotecario VALUES ('giovanni.livraga@gmail.com', '46f2026157e5cf498ec1fd7f7be8ca6c');


--
-- Data for Name: utente_lettore; Type: TABLE DATA; Schema: biblioteca; Owner: antonino_ottina
--

INSERT INTO biblioteca.utente_lettore VALUES ('francesco.ottina@example.com', '7b7c3421ba78a7b89afa4ea5e57531ce', 'CF00000000000016');
INSERT INTO biblioteca.utente_lettore VALUES ('filippo.sorze@example.com', '41e07dd0b0c595a323ef436abca5c804', 'CF00000000000003');
INSERT INTO biblioteca.utente_lettore VALUES ('gianluca.colombo@example.it', '438b01b6a37f683e4dee94743c81952a', 'CF00000000000001');
INSERT INTO biblioteca.utente_lettore VALUES ('giovanni.gioia@example.it', '645fccd2f3fcbae199cc014bd7a87ee1', 'CF00000000000002');
INSERT INTO biblioteca.utente_lettore VALUES ('edoardo.verga@example.it', 'bff0ad2681e2874d822bd9fd5363796b', 'CF00000000000005');
INSERT INTO biblioteca.utente_lettore VALUES ('filippo.oltolini@example.it', 'a6a6ac81a9b2c7e50b6ec722524e4c29', 'CF00000000000006');
INSERT INTO biblioteca.utente_lettore VALUES ('pietro.strambini@example.it', '63fede4b8924d81b0d36fb0c8fa9ae79', 'CF00000000000004');
INSERT INTO biblioteca.utente_lettore VALUES ('matteo.monti@example.it', '749504d72bf063d6de82717fd9b15d76', 'CF00000000000007');
INSERT INTO biblioteca.utente_lettore VALUES ('lamine.sangare@example.it', '94b34d9ffc4bf1f6e2329baf84545076', 'CF00000000000008');
INSERT INTO biblioteca.utente_lettore VALUES ('matteo.cardani@example.it', '55a3c03008cd01efc20f50ffd6d43b9d', 'CF00000000000009');
INSERT INTO biblioteca.utente_lettore VALUES ('francesca.terraneo@example.it', '1aacacebbea4a3458e870168ec30e570', 'CF00000000000010');
INSERT INTO biblioteca.utente_lettore VALUES ('dotty.borgese@example.it', 'b9bc314b456edb1e7da6d86c86b852d8', 'CF00000000000011');
INSERT INTO biblioteca.utente_lettore VALUES ('rocco.costantino@example.it', 'c1cbb21ce9a585dbef53f4182cfe6eec', 'CF00000000000012');
INSERT INTO biblioteca.utente_lettore VALUES ('carlo.iannello@example.it', 'eb84ed65827600ba61c3aa6e63188c19', 'CF00000000000013');
INSERT INTO biblioteca.utente_lettore VALUES ('luca.todaro@example.it', '795d9e6925209f61c261a77492822c21', 'CF00000000000014');
INSERT INTO biblioteca.utente_lettore VALUES ('gabriel.naso@example.it', '7b027223caaa40ba5360b9cf2a9805a1', 'CF00000000000015');


--
-- Name: autore autore_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.autore
    ADD CONSTRAINT autore_pkey PRIMARY KEY (id);


--
-- Name: copia copia_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.copia
    ADD CONSTRAINT copia_pkey PRIMARY KEY (id, libro);


--
-- Name: lettore lettore_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.lettore
    ADD CONSTRAINT lettore_pkey PRIMARY KEY (cf);


--
-- Name: libro libro_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.libro
    ADD CONSTRAINT libro_pkey PRIMARY KEY (isbn);


--
-- Name: prestito prestito_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.prestito
    ADD CONSTRAINT prestito_pkey PRIMARY KEY (cod_prestito);


--
-- Name: scritto scritto_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.scritto
    ADD CONSTRAINT scritto_pkey PRIMARY KEY (autore, libro);


--
-- Name: sede sede_città_indirizzo_key; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.sede
    ADD CONSTRAINT "sede_città_indirizzo_key" UNIQUE ("città", indirizzo);


--
-- Name: sede sede_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.sede
    ADD CONSTRAINT sede_pkey PRIMARY KEY (id);


--
-- Name: utente_bibliotecario utente_bibliotecario_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.utente_bibliotecario
    ADD CONSTRAINT utente_bibliotecario_pkey PRIMARY KEY (email, password);


--
-- Name: utente_lettore utente_lettore_pkey; Type: CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.utente_lettore
    ADD CONSTRAINT utente_lettore_pkey PRIMARY KEY (email, password);


--
-- Name: catalogo _RETURN; Type: RULE; Schema: biblioteca; Owner: antonino_ottina
--



--
-- Name: prestito aggiorna_disponibilita_prestito; Type: TRIGGER; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TRIGGER aggiorna_disponibilita_prestito AFTER UPDATE OF data_restituzione ON biblioteca.prestito FOR EACH ROW WHEN (((new.data_restituzione IS NOT NULL) AND (old.prestito_aperto IS TRUE))) EXECUTE FUNCTION biblioteca.aggiorna_disponibilita();


--
-- Name: prestito aggiorna_ritardi; Type: TRIGGER; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TRIGGER aggiorna_ritardi AFTER UPDATE OF data_restituzione ON biblioteca.prestito FOR EACH ROW WHEN ((new.data_restituzione IS NOT NULL)) EXECUTE FUNCTION biblioteca.update_ritardi();


--
-- Name: prestito blocco_max_prestiti; Type: TRIGGER; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TRIGGER blocco_max_prestiti BEFORE INSERT ON biblioteca.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca.check_max_prestiti();


--
-- Name: prestito blocco_prestito_ritardatari; Type: TRIGGER; Schema: biblioteca; Owner: antonino_ottina
--

CREATE TRIGGER blocco_prestito_ritardatari BEFORE INSERT ON biblioteca.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca.check_ritardi();


--
-- Name: copia copia_cod_prestito_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.copia
    ADD CONSTRAINT copia_cod_prestito_fkey FOREIGN KEY (cod_prestito) REFERENCES biblioteca.prestito(cod_prestito) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: copia copia_libro_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.copia
    ADD CONSTRAINT copia_libro_fkey FOREIGN KEY (libro) REFERENCES biblioteca.libro(isbn) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: copia copia_sede_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.copia
    ADD CONSTRAINT copia_sede_fkey FOREIGN KEY (sede) REFERENCES biblioteca.sede(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prestito prestito_lettore_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.prestito
    ADD CONSTRAINT prestito_lettore_fkey FOREIGN KEY (lettore) REFERENCES biblioteca.lettore(cf) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: scritto scritto_autore_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.scritto
    ADD CONSTRAINT scritto_autore_fkey FOREIGN KEY (autore) REFERENCES biblioteca.autore(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: scritto scritto_libro_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.scritto
    ADD CONSTRAINT scritto_libro_fkey FOREIGN KEY (libro) REFERENCES biblioteca.libro(isbn) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: utente_lettore utente_lettore_cf_lettore_fkey; Type: FK CONSTRAINT; Schema: biblioteca; Owner: antonino_ottina
--

ALTER TABLE ONLY biblioteca.utente_lettore
    ADD CONSTRAINT utente_lettore_cf_lettore_fkey FOREIGN KEY (cf_lettore) REFERENCES biblioteca.lettore(cf) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE USAGE ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

