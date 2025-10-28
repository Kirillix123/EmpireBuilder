<?php
// Funktionen zur Arbeit mit Benutzern
function getUserData($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Standardwerte
        if ($user) {
            if (!isset($user['clicker_wood']) || $user['clicker_wood'] === null || (float)$user['clicker_wood'] < 1.0) {
                $user['clicker_wood'] = 1.0;
            }
            if (!isset($user['clicker_stone']) || $user['clicker_stone'] === null || (float)$user['clicker_stone'] < 1.0) {
                $user['clicker_stone'] = 1.0;
            }
            if (!isset($user['clicker_food']) || $user['clicker_food'] === null || (float)$user['clicker_food'] < 1.0) {
                $user['clicker_food'] = 1.0;
            }
            if (!isset($user['population'])) {
                $user['population'] = 0;
            }
            if (!isset($user['population_multiplier'])) {
                $user['population_multiplier'] = 1.0;
            }
            if (!isset($user['population_fraction'])) {
                $user['population_fraction'] = 0.0;
            }
            if (!isset($user['casino_last_number'])) {
                $user['casino_last_number'] = null;
            }
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("Fehler beim Abrufen der Benutzerdaten: " . $e->getMessage());
        return null;
    }
}

// Funktionen zur Arbeit mit Gebäuden
function getBuildings($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM buildings WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Fehler beim Abrufen der Gebäude: " . $e->getMessage());
        return [];
    }
}

function addBuilding($userId, $type, $positionX, $positionY) {
    try {
        $conn = getConnection();
        
        // Baukosten bestimmen
        $buildingCosts = getBuildingCost($type);
        
        // Ressourcen prüfen
        $user = getUserData($userId);
        if ($user['gold'] < $buildingCosts['gold'] || 
            $user['wood'] < $buildingCosts['wood'] || 
            $user['stone'] < $buildingCosts['stone']) {
            return ['success' => false, 'message' => 'Nicht genügend Ressourcen für den Bau'];
        }
        
        // Ressourcen abziehen
        updateResources($userId, -$buildingCosts['gold'], -$buildingCosts['wood'], -$buildingCosts['stone']);
        
        // Gebäude hinzufügen
        $incomeRate = getBuildingIncomeRate($type, 1);
        $stmt = $conn->prepare("INSERT INTO buildings (user_id, type, level, income_rate, position_x, position_y) 
                                VALUES (?, ?, 1, ?, ?, ?)");
        $stmt->execute([$userId, $type, $incomeRate, $positionX, $positionY]);
        
        return ['success' => true, 'message' => 'Gebäude erfolgreich gebaut'];
    } catch (PDOException $e) {
        error_log("Fehler beim Hinzufügen des Gebäudes: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler beim Hinzufügen des Gebäudes'];
    }
}

function upgradeBuilding($buildingId) {
    try {
        $conn = getConnection();
        
        // Gebäudedaten abrufen
        $stmt = $conn->prepare("SELECT * FROM buildings WHERE id = ?");
        $stmt->execute([$buildingId]);
        $building = $stmt->fetch();
        
        if (!$building) {
            return ['success' => false, 'message' => 'Gebäude nicht gefunden'];
        }
        
        // Zugriffsrechte prüfen
        if (!checkOwnership($building['user_id'])) {
            return ['success' => false, 'message' => 'Zugriff verweigert'];
        }
        
        // Upgrade-Kosten bestimmen
        $upgradeCosts = getUpgradeCost($building['type'], $building['level']);
        
        // Ressourcen prüfen
        $user = getUserData($building['user_id']);
        if ($user['gold'] < $upgradeCosts['gold'] || 
            $user['wood'] < $upgradeCosts['wood'] || 
            $user['stone'] < $upgradeCosts['stone']) {
            return ['success' => false, 'message' => 'Nicht genügend Ressourcen für Upgrade'];
        }
        
        // Ressourcen abziehen
        updateResources($building['user_id'], -$upgradeCosts['gold'], -$upgradeCosts['wood'], -$upgradeCosts['stone']);
        
        // Upgrade starten
        $stmt = $conn->prepare("UPDATE buildings SET is_upgrading = 1, upgrade_progress = 0 WHERE id = ?");
        $stmt->execute([$buildingId]);
        
        return ['success' => true, 'message' => 'Upgrade gestartet'];
    } catch (PDOException $e) {
        error_log("Fehler beim Upgrade des Gebäudes: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler beim Upgrade des Gebäudes'];
    }
}

function updateBuildingProgress() {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM buildings WHERE is_upgrading = 1");
        $stmt->execute();
        $upgradingBuildings = $stmt->fetchAll();
        
        foreach ($upgradingBuildings as $building) {
            $newProgress = $building['upgrade_progress'] + 10;
            
            if ($newProgress >= 100) {
                // Upgrade abgeschlossen
                $newLevel = $building['level'] + 1;
                $newIncomeRate = getBuildingIncomeRate($building['type'], $newLevel);
                
                $updateStmt = $conn->prepare("UPDATE buildings SET level = ?, income_rate = ?, 
                                              upgrade_progress = 0, is_upgrading = 0 WHERE id = ?");
                $updateStmt->execute([$newLevel, $newIncomeRate, $building['id']]);
            } else {
                $updateStmt = $conn->prepare("UPDATE buildings SET upgrade_progress = ? WHERE id = ?");
                $updateStmt->execute([$newProgress, $building['id']]);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Fehler beim Aktualisieren des Gebäude-Fortschritts: " . $e->getMessage());
        return false;
    }
}

function updateResources($userId, $goldChange, $woodChange, $stoneChange, $foodChange = 0) {
    try {
        $conn = getConnection();
        
        // Einkommen aus Gebäuden berechnen
        $buildings = getBuildings($userId);
        $totalIncome = 0;
        $totalFood = 0;
        $totalStone = 0;
        $totalWood = 0;
        $totalPopulation = 0;
        
        foreach ($buildings as $building) {
            if ($building['type'] == 'trade_post') {
                $totalIncome += $building['income_rate'];
            } elseif ($building['type'] == 'farm') {
                $totalFood += $building['income_rate'];
            } elseif ($building['type'] == 'mine') {
                $totalStone += $building['income_rate'];
            } elseif ($building['type'] == 'lumbermill') {
                $totalWood += $building['income_rate'];
            } elseif ($building['type'] == 'house') {
                $totalPopulation += $building['income_rate'];
            }
        }
        
        // Bevölkerungs-Multiplikator holen
        $user = getUserData($userId);
        $multiplier = $user['population_multiplier'] ?? 1.0;
        $population = (int)($user['population'] ?? 0);
        
        // Zeit seit letztem Tick ermitteln (Sekunden) für Nahrungsverbrauch
        // Hinweis: last_tick wird pro Benutzer gepflegt
        $nowStmt = $conn->query("SELECT NOW() AS now");
        $nowRow = $nowStmt->fetch();
        $now = strtotime($nowRow['now']);
        $lastTickTime = isset($user['last_tick']) ? strtotime($user['last_tick']) : $now;
        $elapsedSeconds = max(0, $now - $lastTickTime);
        
        // Multiplikator auf Gold anwenden
        $totalIncome = $totalIncome * $multiplier;
        
        // Passives Einkommen mit verstrichener Zeit multiplizieren
        $totalIncome = $totalIncome * $elapsedSeconds;
        $totalFood = $totalFood * $elapsedSeconds;
        $totalStone = $totalStone * $elapsedSeconds;
        $totalWood = $totalWood * $elapsedSeconds;
        
        // Bevölkerung wird proportional zu verstrichener Zeit generiert (house: 0.1/Sek)
        $populationGenerated = $totalPopulation * $elapsedSeconds;
        
        // Aktuelle population_fraction holen
        $populationFraction = (float)($user['population_fraction'] ?? 0.0);
        
        // Berechnen der neuen population_fraction und des ganzzahligen Bevölkerungswachstums
        $newPopulationFraction = $populationFraction + $populationGenerated;
        $wholePopulationToAdd = (int)floor($newPopulationFraction); // Ganzer Teil
        $remainingFraction = $newPopulationFraction - $wholePopulationToAdd; // Rest
        
        // Nahrungsverbrauch: jede Sekunde -population
        $foodDrain = $population * $elapsedSeconds;
        $netFoodChange = $foodChange + $totalFood - $foodDrain;
        
        // Ressourcen aktualisieren
        $stmt = $conn->prepare("UPDATE users SET 
                                gold = gold + ? + ?, 
                                wood = wood + ? + ?,
                                stone = stone + ? + ?,
                                food = GREATEST(0, food + ?),
                                population = population + ?,
                                population_fraction = ?,
                                last_tick = NOW()
                                WHERE id = ?");
        $stmt->execute([$goldChange, $totalIncome, $woodChange, $totalWood, $stoneChange, $totalStone, $netFoodChange, $wholePopulationToAdd, $remainingFraction, $userId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Fehler bei der Aktualisierung der Ressourcen: " . $e->getMessage());
        return false;
    }
}

function getBuildingCost($type) {
    $costs = [
        'farm' => ['gold' => 100, 'wood' => 50, 'stone' => 20],
        'mine' => ['gold' => 150, 'wood' => 30, 'stone' => 80],
        'lumbermill' => ['gold' => 120, 'wood' => 40, 'stone' => 30],
        'trade_post' => ['gold' => 200, 'wood' => 100, 'stone' => 50],
        'warehouse' => ['gold' => 80, 'wood' => 120, 'stone' => 40],
        'house' => ['gold' => 300, 'wood' => 150, 'stone' => 100],
        'casino' => ['gold' => 500, 'wood' => 200, 'stone' => 100]
    ];
    return $costs[$type] ?? ['gold' => 100, 'wood' => 50, 'stone' => 20];
}

function getUpgradeCost($type, $currentLevel) {
    $baseCosts = getBuildingCost($type);
    $multiplier = $currentLevel * 1.5;
    
    return [
        'gold' => (int)($baseCosts['gold'] * $multiplier),
        'wood' => (int)($baseCosts['wood'] * $multiplier),
        'stone' => (int)($baseCosts['stone'] * $multiplier)
    ];
}

function getBuildingIncomeRate($type, $level) {
    $baseRates = [
        'farm' => 5,
        'mine' => 3,
        'lumbermill' => 4,
        'trade_post' => 10,
        'warehouse' => 0,
        'house' => 0.1, // +1 Einwohner alle 10 Sekunden
        'casino' => 0
    ];
    
    $baseRate = $baseRates[$type] ?? 1;
    return $baseRate * $level;
}

// Informationen über einen Gebäudetyp abrufen
function getBuildingInfo($type) {
    $info = [
        'farm' => ['name' => '🌾 Bauernhof', 'description' => 'Produziert Nahrung, anfangs 5 Nahrung/Sekunde', 'income' => 'Nahrung'],
        'mine' => ['name' => '⛏️ Mine', 'description' => 'Baut Stein ab, anfangs 3 Stein/Sekunde', 'income' => 'Stein'],
        'lumbermill' => ['name' => '🪵 Sägewerk', 'description' => 'Produziert Holz, anfangs 4 Holz/Sekunde', 'income' => 'Holz'],
        'trade_post' => ['name' => '🏪 Handelsposten', 'description' => 'Generiert Gold durch Handel, anfangs 10 Gold/Sekunde', 'income' => 'Gold'],
        'warehouse' => ['name' => '🏭 Lagerhaus', 'description' => 'Erhöht die Kapazität der Ressourcen', 'income' => 'Keine'],
        'house' => ['name' => '🏠 Haus', 'description' => 'Generiert Bevölkerung, anfangs 1 Einwohner/10 Sek', 'income' => 'Bevölkerung'],
        'casino' => ['name' => '🎰 Casino', 'description' => 'Mini-Spiel: Errate die nächste Zahl', 'income' => 'Keine']
    ];
    return $info[$type] ?? ['name' => $type, 'description' => '', 'income' => ''];
}

// Clicker-Logik für Ressourcen
function clickResource($userId, $resourceType) {
    try {
        $conn = getConnection();
        $user = getUserData($userId);
        
        if ($resourceType == 'wood') {
            $clickAmount = max(1.0, (float)$user['clicker_wood']);
            $stmt = $conn->prepare("UPDATE users SET wood = wood + ? WHERE id = ?");
            $stmt->execute([$clickAmount, $userId]);
            return ['success' => true, 'amount' => $clickAmount];
        } elseif ($resourceType == 'stone') {
            $clickAmount = max(1.0, (float)$user['clicker_stone']);
            $stmt = $conn->prepare("UPDATE users SET stone = stone + ? WHERE id = ?");
            $stmt->execute([$clickAmount, $userId]);
            return ['success' => true, 'amount' => $clickAmount];
        } elseif ($resourceType == 'food') {
            $clickAmount = max(1.0, (float)($user['clicker_food'] ?? 1.0));
            $stmt = $conn->prepare("UPDATE users SET food = food + ? WHERE id = ?");
            $stmt->execute([$clickAmount, $userId]);
            return ['success' => true, 'amount' => $clickAmount];
        }
        
        return ['success' => false, 'message' => 'Ungültiger Ressourcentyp'];
    } catch (PDOException $e) {
        error_log("Fehler im Clicker: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler im Clicker'];
    }
}

// Clicker verbessern
function upgradeClicker($userId, $resourceType, $cost) {
    try {
        $conn = getConnection();
        $user = getUserData($userId);
        
        // Aktuellen Click-Wert und Level bestimmen
        $currentValue = 1.0;
        if ($resourceType == 'wood') $currentValue = (float)($user['clicker_wood'] ?? 1.0);
        if ($resourceType == 'stone') $currentValue = (float)($user['clicker_stone'] ?? 1.0);
        if ($resourceType == 'food') $currentValue = (float)($user['clicker_food'] ?? 1.0);
        $currentLevel = max(0, (int)floor($currentValue - 1.0));
        
        // Kosten: Basis 1000, pro Stufe * 1.2
        $calculatedCost = (int)round(1000 * pow(1.2, $currentLevel));
        
        if ($user['gold'] < $calculatedCost) {
            return ['success' => false, 'message' => 'Nicht genug Gold'];
        }
        
        // Gold abziehen
        $stmt = $conn->prepare("UPDATE users SET gold = gold - ? WHERE id = ?");
        $stmt->execute([$calculatedCost, $userId]);
        
        // Click-Wert um +1 erhöhen
        if ($resourceType == 'wood') {
            $newValue = min(9999, $currentValue + 1.0);
            $stmt = $conn->prepare("UPDATE users SET clicker_wood = ? WHERE id = ?");
            $stmt->execute([$newValue, $userId]);
        } elseif ($resourceType == 'stone') {
            $newValue = min(9999, $currentValue + 1.0);
            $stmt = $conn->prepare("UPDATE users SET clicker_stone = ? WHERE id = ?");
            $stmt->execute([$newValue, $userId]);
        } elseif ($resourceType == 'food') {
            $newValue = min(9999, $currentValue + 1.0);
            $stmt = $conn->prepare("UPDATE users SET clicker_food = ? WHERE id = ?");
            $stmt->execute([$newValue, $userId]);
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Fehler beim Verbessern des Clickers: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler'];
    }
}

// Casino - Zahl generieren
function generateCasinoNumber($userId) {
    try {
        $conn = getConnection();
        $number = rand(1, 100);
        
        $stmt = $conn->prepare("UPDATE users SET casino_last_number = ? WHERE id = ?");
        $stmt->execute([$number, $userId]);
        
        return ['success' => true, 'number' => $number];
    } catch (PDOException $e) {
        error_log("Casino-Fehler: " . $e->getMessage());
        return ['success' => false];
    }
}

// Casino - Wette platzieren
function placeCasinoBet($userId, $bet, $guess) {
    try {
        $conn = getConnection();
        $user = getUserData($userId);
        $lastNumber = $user['casino_last_number'];
        
        if ($lastNumber === null) {
            return ['success' => false, 'message' => 'Keine vorherige Zahl'];
        }
        
        // Multiplikator berechnen
        $multiplier = 1.2;
        if ($bet >= 100) $multiplier = 1.4;
        if ($bet >= 1000) $multiplier = 1.6;
        if ($bet >= 10000) $multiplier = 1.8;
        if ($bet >= 100000) $multiplier = 2.0;
        
        $win = false;
        $newNumber = rand(1, 100);
        if ($guess == 'higher' && $newNumber > $lastNumber) $win = true;
        if ($guess == 'lower' && $newNumber < $lastNumber) $win = true;
        
        if ($win) {
            $winnings = $bet * $multiplier;
            $stmt = $conn->prepare("UPDATE users SET gold = gold - ? + ?, casino_last_number = ? WHERE id = ?");
            $stmt->execute([$bet, $winnings, $newNumber, $userId]);
            return ['success' => true, 'win' => true, 'winnings' => $winnings, 'newNumber' => $newNumber];
        } else {
            $stmt = $conn->prepare("UPDATE users SET gold = gold - ?, casino_last_number = ? WHERE id = ?");
            $stmt->execute([$bet, $newNumber, $userId]);
            return ['success' => true, 'win' => false, 'winnings' => 0, 'newNumber' => $newNumber];
        }
    } catch (PDOException $e) {
        error_log("Fehler bei der Wette: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler'];
    }
}

// Bevölkerung gegen Upgrade tauschen
function exchangePopulationForUpgrade($userId, $populationCost) {
    try {
        $conn = getConnection();
        $user = getUserData($userId);
        
        if ($user['population'] < $populationCost) {
            return ['success' => false, 'message' => 'Nicht genug Bevölkerung'];
        }
        
        // Multiplikator um 0.1 erhöhen
        $newMultiplier = $user['population_multiplier'] + 0.1;
        
        $stmt = $conn->prepare("UPDATE users SET population = population - ?, population_multiplier = ? WHERE id = ?");
        $stmt->execute([$populationCost, $newMultiplier, $userId]);
        
        return ['success' => true, 'newMultiplier' => $newMultiplier];
    } catch (PDOException $e) {
        error_log("Fehler beim Tauschen der Bevölkerung: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler'];
    }
}

// Bestenliste abrufen
function getLeaders($limit = 10) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, gold, population FROM users ORDER BY gold DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Fehler beim Abrufen der Bestenliste: " . $e->getMessage());
        return [];
    }
}

//Durch KI geprüft, muss funktionieren