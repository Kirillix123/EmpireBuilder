<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $populationCost = (int)($_POST['cost'] ?? 0);
    
    if ($action === 'exchange') {
        $result = exchangePopulationForUpgrade(getCurrentUserId(), $populationCost);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Methode']);
}

