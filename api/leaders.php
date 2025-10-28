<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $leaders = getLeaders(10);
    echo json_encode(['success' => true, 'leaders' => $leaders]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Methode']);
}

