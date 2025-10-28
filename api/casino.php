<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'generate') {
    $result = generateCasinoNumber(getCurrentUserId());
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bet') {
    $bet = (int)$_POST['bet'] ?? 0;
    $guess = $_POST['guess'] ?? '';
    
    if ($bet <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ungültiger Einsatz']);
        exit;
    }
    
    $result = placeCasinoBet(getCurrentUserId(), $bet, $guess);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
}

