<?php
//gefragt bei KI: "Wie erstelle ich eine Sitzungsverwaltung in PHP?"

//Sitzungsverwaltung
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//Prüfen, ob der Benutzer eingeloggt ist
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

//Aktuelle Benutzer-ID abrufen
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

//Zugriffsprüfung (sicherstellen, dass Ressource zum Benutzer gehört)
function checkOwnership($resourceUserId) {
    return isLoggedIn() && getCurrentUserId() == $resourceUserId;
}

