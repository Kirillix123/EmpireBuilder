<?php
// Einbindung
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Überprüfung der Benutzeranmeldung
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Aktualisierungen
updateResources(getCurrentUserId(), 0, 0, 0, 0);
updateBuildingProgress();

// Abrufen
$user = getUserData(getCurrentUserId());
$buildings = getBuildings(getCurrentUserId());
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EmpireBuilder</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Header -->
        <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">EmpireBuilder</h1>
                    <!-- Anzeige des Benutzernamens mit XSS-Schutz -->
                    <p class="text-gray-400">Willkommen, <?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <a href="api/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Abmelden
                </a>
            </div>
        </div>
        
        <!-- Resources Panel -->
        <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4 text-white">Ressourcen</h2>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4" id="resourcesPanel">
                <!-- Gold-Panel mit Anzeige der aktuellen Menge und des Einkommens -->
                <div class="bg-gradient-to-br from-yellow-900 to-yellow-800 border-2 border-yellow-500 rounded-lg p-4">
                    <div class="text-xs text-yellow-300 mb-1">💰 Gold</div>
                    <div class="text-2xl font-bold text-yellow-100" id="gold"><?php echo $user['gold']; ?></div>
                    <div class="text-xs text-yellow-400 mt-1">+<span id="goldIncome">0</span>/s (x<span id="goldMultiplier">1.0</span>)</div>
                </div>
                <!-- Holz-Panel mit Klick-Button -->
                <div class="bg-gradient-to-br from-amber-900 to-amber-800 border-2 border-amber-500 rounded-lg p-4">
                    <div class="text-xs text-amber-300 mb-1">🪵 Holz</div>
                    <div class="text-2xl font-bold text-amber-100" id="wood"><?php echo $user['wood']; ?></div>
                    <div class="text-xs text-amber-400 mt-1">+<span id="woodIncome">0</span>/s</div>
                    <!-- Button zum Sammeln von Holz durch Klicken -->
                    <button onclick="clickResource('wood')" class="mt-2 w-full bg-amber-600 text-white py-1 px-2 rounded text-xs hover:bg-amber-700">
                        Klick: +<span id="woodClick"><?php echo $user['clicker_wood'] ?? 1.0; ?></span>
                    </button>
                </div>
                <!-- Stein-Panel mit Klick-Button -->
                <div class="bg-gradient-to-br from-gray-700 to-gray-600 border-2 border-gray-500 rounded-lg p-4">
                    <div class="text-xs text-gray-300 mb-1">🪨 Stein</div>
                    <div class="text-2xl font-bold text-gray-100" id="stone"><?php echo $user['stone']; ?></div>
                    <div class="text-xs text-gray-400 mt-1">+<span id="stoneIncome">0</span>/s</div>
                    <!-- Button zum Sammeln von Stein durch Klicken -->
                    <button onclick="clickResource('stone')" class="mt-2 w-full bg-gray-600 text-white py-1 px-2 rounded text-xs hover:bg-gray-700">
                        Klick: +<span id="stoneClick"><?php echo $user['clicker_stone'] ?? 1.0; ?></span>
                    </button>
                </div>
                <!-- Nahrung-Panel mit Klick-Button -->
                <div class="bg-gradient-to-br from-green-900 to-green-800 border-2 border-green-500 rounded-lg p-4">
                    <div class="text-xs text-green-300 mb-1">🌾 Nahrung (verfügbar)</div>
                    <div class="text-2xl font-bold text-green-100" id="food"><?php echo max(0, $user['food'] - ($user['population'] ?? 0)); ?></div>
                    <div class="text-xs text-green-400 mt-1">+<span id="foodIncome">0</span>/s</div>
                    <!-- Button zum Sammeln von Nahrung durch Klicken -->
                    <button onclick="clickResource('food')" class="mt-2 w-full bg-green-600 text-white py-1 px-2 rounded text-xs hover:bg-green-700">
                        Klick: +<span id="foodClick"><?php echo $user['clicker_food'] ?? 1.0; ?></span>
                    </button>
                </div>
                <!-- Bevölkerungs-Panel mit Multiplikator -->
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 border-2 border-blue-500 rounded-lg p-4">
                    <div class="text-xs text-blue-300 mb-1">👥 Bevölkerung</div>
                    <div class="text-2xl font-bold text-blue-100" id="population"><?php echo $user['population'] ?? 0; ?></div>
                    <div class="text-xs text-blue-400 mt-1">Multi: x<span id="populationMult"><?php echo $user['population_multiplier'] ?? 1.0; ?></span></div>
                </div>
                <!-- Panel zum Tauschen von Bevölkerung gegen Gold-Boni -->
                <div class="bg-gradient-to-br from-purple-900 to-purple-800 border-2 border-purple-500 rounded-lg p-4">
                    <div class="text-xs text-purple-300 mb-1">🎯 Upgrade</div>
                    <div class="text-lg font-bold text-purple-100">100 👥</div>
                    <div class="text-xs text-purple-400 mt-1">= +0.1x Gold</div>
                    <!-- Button zum Tauschen von Bevölkerung gegen Gold-Multiplikator -->
                    <button onclick="exchangePopulation(100)" class="mt-2 w-full bg-purple-600 text-white py-1 px-2 rounded text-xs hover:bg-purple-700">
                        Tauschen
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex space-x-2 mb-4">
            <!-- Button zum Wechseln zur Bau-Registerkarte -->
            <button onclick="showTab('build')" id="buildTab" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold">Bauen</button>
            <!-- Button zum Wechseln zur Klicker-Upgrade-Registerkarte -->
            <button onclick="showTab('clicker')" id="clickerTab" class="px-6 py-2 bg-gray-700 text-white rounded-lg font-semibold">Klicker</button>
            <!-- Button zum Wechseln zur Ranglisten-Registerkarte -->
            <button onclick="showTab('leaders')" id="leadersTab" class="px-6 py-2 bg-gray-700 text-white rounded-lg font-semibold">🏆 Ränge</button>
            <!-- Button zum Wechseln zur Gebäudelisten-Registerkarte -->
            <button onclick="showTab('buildings')" id="buildingsTab" class="px-6 py-2 bg-gray-700 text-white rounded-lg font-semibold">Gebäude</button>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Build Panel -->
            <div id="buildPanel" class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6">
                <h2 class="text-lg font-semibold mb-4 text-white">Bauen</h2>
                <div class="space-y-2">
                    <!-- Button zum Anzeigen der Bauernhof-Informationen -->
                    <button onclick="showBuildingInfo('farm')" title="Produziert Nahrung" class="w-full bg-gradient-to-r from-green-700 to-green-600 text-white py-2 px-4 rounded hover:from-green-600 hover:to-green-500 transition">
                        🌾 Bauernhof - 100💰 50🪵 20🪨
                    </button>
                    <!-- Button zum Anzeigen der Sägewerk-Informationen -->
                    <button onclick="showBuildingInfo('lumbermill')" title="Produziert Holz" class="w-full bg-gradient-to-r from-amber-700 to-amber-600 text-white py-2 px-4 rounded hover:from-amber-600 hover:to-amber-500 transition">
                        🪵 Sägewerk - 120💰 40🪵 30🪨
                    </button>
                    <!-- Button zum Anzeigen der Minen-Informationen -->
                    <button onclick="showBuildingInfo('mine')" title="Produziert Stein" class="w-full bg-gradient-to-r from-gray-700 to-gray-600 text-white py-2 px-4 rounded hover:from-gray-600 hover:to-gray-500 transition">
                        ⛏️ Mine - 150💰 30🪵 80🪨
                    </button>
                    <!-- Button zum Anzeigen der Handelsposten-Informationen -->
                    <button onclick="showBuildingInfo('trade_post')" title="Produziert Gold" class="w-full bg-gradient-to-r from-yellow-700 to-yellow-600 text-white py-2 px-4 rounded hover:from-yellow-600 hover:to-yellow-500 transition">
                        🏪 Handelsposten - 200💰 100🪵 50🪨
                    </button>
                    <!-- Button zum Anzeigen der Haus-Informationen -->
                    <button onclick="showBuildingInfo('house')" title="Generiert Bevölkerung" class="w-full bg-gradient-to-r from-blue-700 to-blue-600 text-white py-2 px-4 rounded hover:from-blue-600 hover:to-blue-500 transition">
                        🏠 Haus - 300💰 150🪵 100🪨
                    </button>
                    <!-- Button zum Anzeigen der Lagerhaus-Informationen -->
                    <button onclick="showBuildingInfo('warehouse')" title="Erhöht Kapazität" class="w-full bg-gradient-to-r from-purple-700 to-purple-600 text-white py-2 px-4 rounded hover:from-purple-600 hover:to-purple-500 transition">
                        🏭 Lagerhaus - 80💰 120🪵 40🪨
                    </button>
                    <!-- Button zum Anzeigen der Casino-Informationen -->
                    <button onclick="showBuildingInfo('casino')" title="Mini-Spiel" class="w-full bg-gradient-to-r from-red-700 to-red-600 text-white py-2 px-4 rounded hover:from-red-600 hover:to-red-500 transition">
                        🎰 Casino - 500💰 200🪵 100🪨
                    </button>
                </div>
            </div>

            <!-- Clicker Panel -->
            <div id="clickerPanel" class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6 hidden">
                <h2 class="text-lg font-semibold mb-4 text-white">Klicker-Upgrades</h2>
                <div class="space-y-4">
                    <!-- Panel für Holz-Klicker-Upgrade -->
                    <div class="bg-gradient-to-br from-amber-900 to-amber-800 border-2 border-amber-500 rounded-lg p-4">
                        <h3 class="font-semibold mb-2 text-amber-100">🪵 Holz</h3>
                        <!-- Anzeige des aktuellen Holz-Klicker-Wertes -->
                        <p class="text-sm mb-2 text-amber-200">Aktuell: <span id="currentWoodClick"><?php echo $user['clicker_wood'] ?? 0.1; ?></span></p>
                        <!-- Button zum Verbessern des Holz-Klickers -->
                        <button onclick="upgradeClicker('wood')" class="w-full bg-amber-600 text-white py-2 px-4 rounded-lg hover:bg-amber-700">
                            Verbessern: <span id="woodClickCost">100</span>💰
                        </button>
                    </div>
                    <!-- Panel für Stein-Klicker-Upgrade -->
                    <div class="bg-gradient-to-br from-gray-700 to-gray-600 border-2 border-gray-500 rounded-lg p-4">
                        <h3 class="font-semibold mb-2 text-gray-100">🪨 Stein</h3>
                        <!-- Anzeige des aktuellen Stein-Klicker-Wertes -->
                        <p class="text-sm mb-2 text-gray-200">Aktuell: <span id="currentStoneClick"><?php echo $user['clicker_stone'] ?? 0.1; ?></span></p>
                        <!-- Button zum Verbessern des Stein-Klickers -->
                        <button onclick="upgradeClicker('stone')" class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700">
                            Verbessern: <span id="stoneClickCost">100</span>💰
                        </button>
                    </div>
                    <!-- Panel für Nahrung-Klicker-Upgrade -->
                    <div class="bg-gradient-to-br from-green-900 to-green-800 border-2 border-green-500 rounded-lg p-4">
                        <h3 class="font-semibold mb-2 text-green-100">🌾 Nahrung</h3>
                        <!-- Anzeige des aktuellen Nahrung-Klicker-Wertes -->
                        <p class="text-sm mb-2 text-green-200">Aktuell: <span id="currentFoodClick"><?php echo $user['clicker_food'] ?? 0.1; ?></span></p>
                        <!-- Button zum Verbessern des Nahrung-Klickers -->
                        <button onclick="upgradeClicker('food')" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700">
                            Verbessern: <span id="foodClickCost">100</span>💰
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leaders Panel -->
            <div id="leadersPanel" class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6 hidden">
                <h2 class="text-lg font-semibold mb-4 text-white">🏆 Top 10 Leaderboard</h2>
                <!-- Container für die Anzeige der Rangliste -->
                <div id="leadersList" class="space-y-2"></div>
            </div>
            
            <!-- City Map -->
            <div class="lg:col-span-2 bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6">
                <h2 class="text-lg font-semibold mb-4 text-white">🗺️ Stadtkarte</h2>
                <div id="cityMap" class="bg-gray-700 rounded-lg p-6 min-h-[400px] relative overflow-hidden">
                    <!-- Schleife zur Anzeige aller Benutzergebäude auf der Karte -->
                    <?php foreach ($buildings as $building): ?>
                        <!-- Abrufen der Informationen über den Gebäudetyp -->
                        <?php $info = getBuildingInfo($building['type']); ?>
                        <!-- Anzeige des Gebäudes auf der Karte mit Positionierung -->
                        <div class="absolute inline-block bg-gray-800 border-2 border-gray-600 rounded-lg p-3 shadow-lg cursor-pointer hover:border-blue-500" 
                             style="left: <?php echo $building['position_x']; ?>px; top: <?php echo $building['position_y']; ?>px;"
                             data-building-id="<?php echo $building['id']; ?>"
                             onclick="showBuildingDetails(<?php echo $building['id']; ?>)"
                             title="<?php echo $info['description']; ?>">
                            <!-- Anzeige des Gebäude-Icons je nach Typ -->
                            <div class="text-2xl"><?php $icons = ['farm' => '🌾', 'lumbermill' => '🪵', 'mine' => '⛏️', 'trade_post' => '🏪', 'house' => '🏠', 'warehouse' => '🏭', 'casino' => '🎰']; echo $icons[$building['type']] ?? '🏠'; ?></div>
                            <!-- Anzeige des Gebäude-Levels -->
                            <div class="text-xs text-center mt-1 text-white">Lvl <?php echo $building['level']; ?></div>
                            <!-- Anzeige des Upgrade-Fortschrittsbalkens, falls das Gebäude upgradet wird -->
                            <?php if ($building['is_upgrading']): ?>
                                <div class="w-full bg-gray-700 rounded-full h-1.5 mt-1">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo $building['upgrade_progress']; ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-sm text-gray-400">Klicken Sie auf die Karte, um das ausgewählte Gebäude zu bauen</div>
            </div>
        </div>
        
        <!-- Buildings List -->
        <div id="buildingsListPanel" class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 p-6 mt-6 hidden">
            <h2 class="text-lg font-semibold mb-4 text-white">Gebäude</h2>
            <!-- Container für die Anzeige der Gebäudeliste als Raster -->
            <div id="buildingsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"></div>
        </div>
    </div>
    
    <!-- Modals -->
    <!-- Modal-Fenster zur Anzeige von Gebäudeinformationen -->
    <div id="buildingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 border border-gray-700">
            <!-- Container für den Modal-Inhalt -->
            <div id="modalContent" class="text-white"></div>
        </div>
    </div>

    <!-- Modal-Fenster für das Casino-Minispiel -->
    <div id="casinoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 border border-gray-700">
            <h2 class="text-lg font-semibold mb-4 text-white">🎰 Casino - Rate die Zahl</h2>
            <!-- Container für den Casino-Inhalt -->
            <div id="casinoContent"></div>
        </div>
    </div>
    
    <!-- Einbindung der JavaScript-Datei mit der Spiellogik -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
