<?php
require_once '../config/database.php'; // Einbindung
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Setze den Content-Type auf JSON, damit der Browser weiß, dass JSON-Daten zurückgesendet werden
header('Content-Type: application/json');

// Überprüfe, ob der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    // Falls nicht eingeloggt, sende eine Fehlermeldung zurück und beende das Script
    echo json_encode(['success' => false, 'message' => 'Anmeldung erforderlich']);
    exit;
}

// Hole die gewünschte Aktion aus GET- oder POST-Parametern (falls vorhanden)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GEBÄUDE AUFLISTEN - Wenn es eine GET-Anfrage mit action="list" ist
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    // Hole alle Gebäude des aktuell eingeloggten Benutzers aus der Datenbank
    $buildings = getBuildings(getCurrentUserId());
    // Sende die Gebäude-Liste als JSON zurück
    echo json_encode(['success' => true, 'buildings' => $buildings]);
    
// GEBÄUDE HINZUFÜGEN - Wenn es eine POST-Anfrage mit action="add" ist
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    // Hole die Parameter für das neue Gebäude aus der POST-Anfrage
    $type = $_POST['type'] ?? '';           // Gebäudetyp (z.B. "house", "factory")
    $positionX = $_POST['position_x'] ?? 0; // X-Koordinate auf der Karte
    $positionY = $_POST['position_y'] ?? 0; // Y-Koordinate auf der Karte
    
    // Überprüfe, ob der Gebäudetyp angegeben wurde
    if (empty($type)) {
        // Falls kein Typ angegeben, sende Fehlermeldung und beende
        echo json_encode(['success' => false, 'message' => 'Gebäudetyp ist nicht angegeben']);
        exit;
    }
    
    // Füge das neue Gebäude zur Datenbank hinzu
    $result = addBuilding(getCurrentUserId(), $type, $positionX, $positionY);
    // Sende das Ergebnis (Erfolg oder Fehler) zurück
    echo json_encode($result);
    
// GEBÄUDE UPGRADEN - Wenn es eine POST-Anfrage mit action="upgrade" ist
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upgrade') {
    // Holen die ID des Gebäudes, das upgegraded werden soll
    $buildingId = $_POST['building_id'] ?? 0;
    
    // Überprüfen, ob eine gültige Gebäude-ID angegeben wurde
    if (empty($buildingId)) {
        // Falls keine ID angegeben, senden Fehlermeldung und beende
        echo json_encode(['success' => false, 'message' => 'Gebäude-ID ist nicht angegeben']);
        exit;
    }
    
    // Führe das Upgrade des Gebäudes durch
    $result = upgradeBuilding($buildingId);
    // Sende das Ergebnis zurück
    echo json_encode($result);
    
// GEBÄUDE LÖSCHEN - Wenn es eine DELETE-Anfrage mit action="delete" ist
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    // Hole die ID des zu löschenden Gebäudes aus den GET-Parametern
    $buildingId = $_GET['building_id'] ?? 0;
    
    try {
        // Stelle eine Verbindung zur Datenbank her
        $conn = getConnection();
        
        // Überprüfe die Zugriffsrechte - hole den Besitzer des Gebäudes
        $stmt = $conn->prepare("SELECT user_id FROM buildings WHERE id = ?");
        $stmt->execute([$buildingId]);
        $building = $stmt->fetch();
        
        // Überprüfe, ob das Gebäude existiert und der aktuelle Benutzer der Besitzer ist
        if (!$building || !checkOwnership($building['user_id'])) {
            // Falls kein Zugriff, sende Fehlermeldung und beende
            echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
            exit;
        }
        
        // Lösche das Gebäude aus der Datenbank
        $stmt = $conn->prepare("DELETE FROM buildings WHERE id = ?");
        $stmt->execute([$buildingId]);
        
        // Sende Erfolgsmeldung zurück
        echo json_encode(['success' => true, 'message' => 'Gebäude gelöscht']);
        
    } catch (PDOException $e) {
        // Falls ein Datenbankfehler auftritt, logge den Fehler und sende eine allgemeine Fehlermeldung
        error_log("Fehler beim Löschen des Gebäudes: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Fehler beim Löschen des Gebäudes']);
    }
// UNBEKANNTE AKTION - Falls keine der obigen Bedingungen zutrifft
} else {
    // Sende eine Fehlermeldung für unbekannte Aktionen
    echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
}

