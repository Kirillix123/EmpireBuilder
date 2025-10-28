<?php
// gefragt bei KI: "Wie erstelle ich eine Datenbankverbindung in PHP?"
// Konfiguration der Datenbankverbindung
define('DB_HOST', 'localhost');
define('DB_NAME', 'empirebuilder');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Erstellung der Datenbankverbindung
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Fehlermeldung auf Deutsch, um konsistente Ausgaben zu gewährleisten
        die("Datenbankverbindungsfehler: " . $e->getMessage());
    }
}