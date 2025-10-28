<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    //Ablauf (einfach erklärt):
    // 1. Wir holen die Benutzerdaten aus dem Formular.
    // 2. Wir prüfen, ob die Eingaben valide sind.
    // 3. Wenn alles passt, speichern wir den Benutzer in der Datenbank.
    // 4. Wenn etwas falsch ist, gibt es eine Fehlermeldung zurück.

    // Dieser Code habe ich bei KI gefragt: "Wie kann ich einen API code in php schreiben?", alle andere API codes sind von mir. (Nach Muster gearbeitet)


    // Validierung der Eingaben
    if (empty($username) || empty($password)) { //Wenn der Benutzername oder das Passwort leer ist, gibt es eine Fehlermeldung zurück.
        echo json_encode(['success' => false, 'message' => 'Bitte alle Felder ausfüllen']);
        exit;
    }
    
    if (strlen($username) < 3) { //Wenn der Benutzername weniger als 3 Zeichen enthält, gibt es eine Fehlermeldung zurück.
        echo json_encode(['success' => false, 'message' => 'Benutzername muss mindestens 3 Zeichen enthalten']);
        exit;
    }
    
    if (strlen($password) < 6) { //Wenn das Passwort weniger als 6 Zeichen enthält, gibt es eine Fehlermeldung zurück.
        echo json_encode(['success' => false, 'message' => 'Passwort muss mindestens 6 Zeichen enthalten']);
        exit;
    }
    
    try {
        $conn = getConnection();
        
        // Prüfen, ob Benutzername bereits existiert
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?"); //stmt bedeutet statement, das ist die SQL-Anweisung.
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) { //fetch bedeutet, dass wir die Daten aus der Datenbank holen.
            echo json_encode(['success' => false, 'message' => 'Benutzername ist bereits vergeben']); //Wenn der Benutzername bereits vergeben ist, gibt es eine Fehlermeldung zurück.
            exit;
        }
        
        // Neuen Benutzer anlegen
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); //password_hash bedeutet, dass wir das Passwort hashen.
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);
        
        $userId = $conn->lastInsertId(); 
        
        // Automatisches Login
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        
        echo json_encode(['success' => true, 'message' => 'Registrierung erfolgreich', 'redirect' => 'dashboard.php']); //Wenn die Registrierung erfolgreich ist, gibt es eine Erfolgsmeldung zurück.
        
    } catch (PDOException $e) {
        error_log("Registrierungs-Fehler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Fehler bei der Registrierung']); //Sonst Fehler
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
}

