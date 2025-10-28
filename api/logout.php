<?php
require_once '../includes/session.php';

// Sitzung beenden, damit der Benutzer ausgeloggt wird
session_destroy(); 
header('Location: ../index.php');
exit;

