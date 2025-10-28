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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'click') {
    $resourceType = $_POST['resource_type'] ?? '';
    
    if (empty($resourceType)) {
        echo json_encode(['success' => false, 'message' => 'Fehlender Ressourcentyp']);
        exit;
    }
    
    $result = clickResource(getCurrentUserId(), $resourceType);
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upgrade') {
    $resourceType = $_POST['resource_type'] ?? '';
    $cost = (int)$_POST['cost'] ?? 100;
    
    $result = upgradeClicker(getCurrentUserId(), $resourceType, $cost);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
}

