<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Anmeldung erforderlich']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Ressourcen aktualisieren (Auto-Update auslösen)
    updateResources(getCurrentUserId(), 0, 0, 0, 0);
    updateBuildingProgress();
    
    $user = getUserData(getCurrentUserId());
    //Hier wird das Ressourcen-Update für den Nutzer sowie der Baufortschritt durchgeführt
    //danach werden die aktuellen Benutzerdaten aus der Datenbank geholt, um sie an den Client zurückzugeben
    echo json_encode([
        'success' => true,
        'resources' => [
            'gold' => $user['gold'],
            'wood' => $user['wood'],
            'stone' => $user['stone'],
            'food' => $user['food'],
            'population' => $user['population'] ?? 0,
            'multiplier' => $user['population_multiplier'] ?? 1.0,
            'clicker_wood' => $user['clicker_wood'] ?? 0.1,
            'clicker_stone' => $user['clicker_stone'] ?? 0.1,
            'clicker_food' => $user['clicker_food'] ?? 0.1
        ]
    ]);
}

