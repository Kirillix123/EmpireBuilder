<?php
require_once '../config/database.php'; //Datenbankverbindung
require_once '../includes/session.php';

header('Content-Type: application/json'); //Welche datei typen wir zurückgeben.
    
// Ablauf (einfach erklärt):
// 1. Wir holen die Benutzerdaten aus der Datenbank anhand des eingegebenen Benutzernamens.
// 2. Falls ein Nutzer mit diesem Namen existiert, prüfen wir, ob das Passwort stimmt.
// 3. Wenn beides passt, speichern wir die Benutzer-ID und den Namen in der Sitzung (Login).
// 4. Ist etwas falsch, gibt es eine Fehlermeldung zurück.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ''); //Username aus dem Formular
    $password = $_POST['password'] ?? ''; //Passwort aus dem Formular
    
    // Validierung der Eingaben
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Bitte alle Felder ausfüllen']);
        exit;
    }

    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true, 'message' => 'Erfolgreich angemeldet', 'redirect' => 'dashboard.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Benutzername oder Passwort ist falsch']);
        }
    } catch (PDOException $e) {
        error_log("Login-Fehler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Fehler beim Anmelden']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
}

