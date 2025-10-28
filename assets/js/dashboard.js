// Globale Variablen
let selectedBuildingType = null;
let buildings = [];
let user = null;

/**
 * Initialisierung beim Laden der Seite
 * Lädt Gebäude, aktualisiert Ressourcen und richtet Event-Listener ein
 */
document.addEventListener('DOMContentLoaded', () => {
    loadBuildings();
    updateResources();
    
    // Automatische Aktualisierung der Ressourcen alle 2 Sekunden
    setInterval(() => {
        updateResources();
        loadBuildings();
    }, 2000);
    
    // Behandlung von Klicks auf die Stadtkarte //Bei KI gefragt wie man ungefähr das machen kann
    const cityMap = document.getElementById('cityMap');
    cityMap.addEventListener('click', (e) => {
        if (e.target === cityMap) {
            const rect = cityMap.getBoundingClientRect();
            const x = e.clientX - rect.left - 50;
            const y = e.clientY - rect.top - 50;
            
            if (selectedBuildingType) {
                buildBuilding(selectedBuildingType, x, y);
            } else {
                showMessage('Wählen Sie einen Gebäudetyp zum Bauen', true);
            }
        }
    });

    // Erste Registerkarte anzeigen
    showTab('build');
});

/**
 * Tauscht Bevölkerung gegen Verbesserungen
 * @param {number} cost - Kosten in Bevölkerung
 */
async function exchangePopulation(cost) {
    if (!confirm(`Möchten Sie ${cost} Bevölkerung gegen +0.1x Gold-Multiplikator tauschen?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'exchange');
        formData.append('cost', cost);
        
        const response = await fetch('api/population.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(`Erfolg! Neuer Multiplikator: x${data.newMultiplier.toFixed(2)}`, false);
            await updateResources();
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Tauschen', true);
        console.error(error);
    }
}

/**
 * Lädt die Rangliste der besten Spieler
 * Zeigt die Top-Spieler nach Gold sortiert an
 */
async function loadLeaders() {
    try {
        const response = await fetch('api/leaders.php');
        const data = await response.json();
        
        if (data.success) {
            const leadersList = document.getElementById('leadersList');
            if (!leadersList) return;
            
            leadersList.innerHTML = '';
            data.leaders.forEach((leader, index) => {
                const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
                const card = document.createElement('div');
                card.className = 'bg-gray-700 rounded-lg p-3 border border-gray-600';
                card.innerHTML = `
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm">${medal}</span>
                            <span class="font-semibold text-white">${leader.username}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-yellow-400 font-bold">${leader.gold}💰</div>
                            <div class="text-xs text-gray-400">${leader.population}👥</div>
                        </div>
                    </div>
                `;
                leadersList.appendChild(card);
            });
        }
    } catch (error) {
        console.error('Fehler beim Laden der Ränge:', error);
    }
}

/**
 * Wechselt zwischen verschiedenen Registerkarten der Benutzeroberfläche
 * @param {string} tabName - Name der anzuzeigenden Registerkarte
 */
function showTab(tabName) {
    document.getElementById('buildPanel').classList.add('hidden');
    document.getElementById('clickerPanel').classList.add('hidden');
    document.getElementById('buildingsListPanel').classList.add('hidden');
    document.getElementById('leadersPanel').classList.add('hidden');
    
    const tabs = ['buildTab', 'clickerTab', 'buildingsTab', 'leadersTab'];
    tabs.forEach(tab => {
        const el = document.getElementById(tab);
        if (el) {
            el.classList.remove('bg-blue-600');
            el.classList.add('bg-gray-700');
        }
    });

    if (tabName === 'build') {
        const el = document.getElementById('buildPanel');
        if (el) el.classList.remove('hidden');
        const tab = document.getElementById('buildTab');
        if (tab) {
            tab.classList.remove('bg-gray-700');
            tab.classList.add('bg-blue-600');
        }
        selectedBuildingType = null;
    } else if (tabName === 'clicker') {
        const el = document.getElementById('clickerPanel');
        if (el) el.classList.remove('hidden');
        const tab = document.getElementById('clickerTab');
        if (tab) {
            tab.classList.remove('bg-gray-700');
            tab.classList.add('bg-blue-600');
        }
        updateClickerPanel();
    } else if (tabName === 'leaders') {
        const el = document.getElementById('leadersPanel');
        if (el) el.classList.remove('hidden');
        const tab = document.getElementById('leadersTab');
        if (tab) {
            tab.classList.remove('bg-gray-700');
            tab.classList.add('bg-blue-600');
        }
        loadLeaders();
    } else if (tabName === 'buildings') {
        const el = document.getElementById('buildingsListPanel');
        if (el) el.classList.remove('hidden');
        const tab = document.getElementById('buildingsTab');
        if (tab) {
            tab.classList.remove('bg-gray-700');
            tab.classList.add('bg-blue-600');
        }
        displayBuildings();
    }
}

/**
 * Aktualisiert die Anzeige der Ressourcen des Spielers
 * Holt aktuelle Werte vom Server und zeigt sie in der Benutzeroberfläche an
 */
async function updateResources() {
    try {
        const response = await fetch('api/resources.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('gold').textContent = data.resources.gold;
            document.getElementById('wood').textContent = data.resources.wood;
            document.getElementById('stone').textContent = data.resources.stone;
            // Zeige verfügbare Nahrung (Nahrung - Bevölkerung)
            const availableFood = Math.max(0, data.resources.food - (data.resources.population ?? 0));
            document.getElementById('food').textContent = availableFood;
            document.getElementById('population').textContent = data.resources.population ?? 0;
            document.getElementById('populationMult').textContent = data.resources.multiplier ?? 1.0;
            document.getElementById('goldMultiplier').textContent = data.resources.multiplier ?? 1.0;
            
            // Einkommensraten aktualisieren
            updateIncomeRates();
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren der Ressourcen:', error);
    }
}

/**
 * Berechnet und aktualisiert die Einkommensraten für alle Ressourcen
 * Basiert auf den vorhandenen Gebäuden und deren Produktionsraten
 */
function updateIncomeRates() {
    let goldIncome = 0, woodIncome = 0, stoneIncome = 0, foodIncome = 0;
    
    buildings.forEach(building => {
        if (building.type == 'trade_post') goldIncome += building.income_rate;
        if (building.type == 'lumbermill') woodIncome += building.income_rate;
        if (building.type == 'mine') stoneIncome += building.income_rate;
        if (building.type == 'farm') foodIncome += building.income_rate;
    });
    
    document.getElementById('goldIncome').textContent = goldIncome.toFixed(2);
    document.getElementById('woodIncome').textContent = woodIncome.toFixed(2);
    document.getElementById('stoneIncome').textContent = stoneIncome.toFixed(2);
    // Netto-Nahrung wird serverseitig abgezogen (Bevölkerung/Sek), hier nur Produktion
    document.getElementById('foodIncome').textContent = foodIncome.toFixed(2);
}

/**
 * Lädt die Liste aller Gebäude des Spielers vom Server
 * Aktualisiert die globale buildings-Variable und die Einkommensraten
 */
async function loadBuildings() {
    try {
        const response = await fetch('api/buildings.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            buildings = data.buildings;
            updateIncomeRates();
        }
    } catch (error) {
        console.error('Fehler beim Laden der Gebäude:', error);
    }
}

/**
 * Führt einen Klick auf eine Ressource aus (Clicker-Mechanik)
 * @param {string} resourceType - Typ der Ressource (gold, wood, stone, food)
 */
async function clickResource(resourceType) {
    try {
        const formData = new FormData();
        formData.append('action', 'click');
        formData.append('resource_type', resourceType);
        
        const response = await fetch('api/clicker.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(`+${data.amount} erhalten!`, false);
            await updateResources();
            await loadBuildings();
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Klicken', true);
        console.error(error);
    }
}

/**
 * Verbessert die Klick-Effizienz für eine bestimmte Ressource
 * @param {string} resourceType - Typ der Ressource zum Verbessern
 */
async function upgradeClicker(resourceType) {
    try {
        const cost = document.getElementById(resourceType + 'ClickCost').textContent;
        
        const formData = new FormData();
        formData.append('action', 'upgrade');
        formData.append('resource_type', resourceType);
        formData.append('cost', cost);
        
        const response = await fetch('api/clicker.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Upgrade erfolgreich!', false);
            await updateResources();
            updateClickerPanel();
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Upgrade', true);
        console.error(error);
    }
}

/**
 * Aktualisiert das Clicker-Panel mit aktuellen Werten und Kosten
 * Zeigt die aktuellen Klick-Werte und Upgrade-Kosten für alle Ressourcen an
 */
async function updateClickerPanel() {
    try {
        const response = await fetch('api/resources.php');
        const data = await response.json();
        
        if (data.resources.clicker_wood !== undefined) {
            document.getElementById('currentWoodClick').textContent = data.resources.clicker_wood;
            document.getElementById('woodClick').textContent = data.resources.clicker_wood;
            const level = Math.max(0, Math.floor(data.resources.clicker_wood - 1));
            const cost = Math.round(1000 * Math.pow(1.2, level));
            const el = document.getElementById('woodClickCost');
            if (el) el.textContent = cost;
        }
        
        if (data.resources.clicker_stone !== undefined) {
            document.getElementById('currentStoneClick').textContent = data.resources.clicker_stone;
            document.getElementById('stoneClick').textContent = data.resources.clicker_stone;
            const level = Math.max(0, Math.floor(data.resources.clicker_stone - 1));
            const cost = Math.round(1000 * Math.pow(1.2, level));
            const el = document.getElementById('stoneClickCost');
            if (el) el.textContent = cost;
        }
        
        if (data.resources.clicker_food !== undefined) {
            document.getElementById('currentFoodClick').textContent = data.resources.clicker_food;
            document.getElementById('foodClick').textContent = data.resources.clicker_food;
            const level = Math.max(0, Math.floor(data.resources.clicker_food - 1));
            const cost = Math.round(1000 * Math.pow(1.2, level));
            const el = document.getElementById('foodClickCost');
            if (el) el.textContent = cost;
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren des Klicker-Panels:', error);
    }
}

/**
 * Zeigt alle Gebäude des Spielers in einer Liste an
 * Erstellt Karten für jedes Gebäude mit Informationen und Aktionsschaltflächen
 */
function displayBuildings() {
    const buildingsList = document.getElementById('buildingsList');
    if (!buildingsList) return;
    
    buildingsList.innerHTML = '';
    
    buildings.forEach(building => {
        const buildingCard = document.createElement('div');
        buildingCard.className = 'bg-gray-700 border border-gray-600 rounded-lg p-4';
        
        const typeNames = {
            'farm': '🌾 Bauernhof',
            'lumbermill': '🪵 Sägewerk',
            'mine': '⛏️ Mine',
            'trade_post': '🏪 Handelsposten',
            'house': '🏠 Haus',
            'warehouse': '🏭 Lagerhaus',
            'casino': '🎰 Casino'
        };
        
        const descriptions = {
            'farm': 'Produziert Nahrung',
            'lumbermill': 'Produziert Holz',
            'mine': 'Produziert Stein',
            'trade_post': 'Produziert Gold',
            'house': 'Generiert Bevölkerung',
            'warehouse': 'Erhöht Kapazität',
            'casino': 'Mini-Spiel'
        };
        
        buildingCard.innerHTML = `
            <h3 class="font-semibold text-white mb-2">${typeNames[building.type] || building.type}</h3>
            <p class="text-sm text-gray-300 mb-1">Level: ${building.level}</p>
            <p class="text-sm text-gray-300 mb-2">${descriptions[building.type]}: ${building.income_rate}/sek</p>
            ${building.type === 'casino' && !building.is_upgrading ? 
                `<button onclick="openCasino(${building.id})" class="w-full bg-gray-900 text-white py-2 px-4 rounded-lg hover:bg-gray-800 mt-2">
                    Spielen!
                </button>` :
                ''}
            ${building.is_upgrading ? 
                `<div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                    <div class="bg-gray-900 h-2 rounded-full transition-all" style="width: ${building.upgrade_progress}%"></div>
                </div>
                <p class="text-xs text-blue-400">Upgrade läuft...</p>` :
                `<button onclick="startUpgrade(${building.id})" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 mt-2">
                    Verbessern
                </button>`
            }
            <button onclick="deleteBuilding(${building.id})" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 mt-2">
                Löschen
            </button>
        `;
        
        buildingsList.appendChild(buildingCard);
    });
}

/**
 * Zeigt Informationen über einen ausgewählten Gebäudetyp zum Bauen an
 * @param {string} type - Typ des Gebäudes
 */
function showBuildingInfo(type) {
    selectedBuildingType = type;
    showMessage(`Ausgewählt: ${getBuildingName(type)}. Klicken Sie auf die Karte zum Bauen.`, false);
}

/**
 * Gibt den deutschen Namen für einen Gebäudetyp zurück
 * @param {string} type - Typ des Gebäudes
 * @returns {string} Deutscher Name des Gebäudes
 */
function getBuildingName(type) {
    const names = {
        'farm': '🌾 Bauernhof',
        'lumbermill': '🪵 Sägewerk',
        'mine': '⛏️ Mine',
        'trade_post': '🏪 Handelsposten',
        'house': '🏠 Haus',
        'warehouse': '🏭 Lagerhaus',
        'casino': '🎰 Casino'
    };
    return names[type] || type;
}

/**
 * Baut ein neues Gebäude an der angegebenen Position
 * @param {string} type - Typ des zu bauenden Gebäudes
 * @param {number} x - X-Koordinate auf der Karte
 * @param {number} y - Y-Koordinate auf der Karte
 */
async function buildBuilding(type, x, y) {
    try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('type', type);
        formData.append('position_x', x);
        formData.append('position_y', y);
        
        const response = await fetch('api/buildings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, false);
            selectedBuildingType = null;
            await loadBuildings();
            await updateResources();
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Bauen', true);
        console.error(error);
    }
}

/**
 * Zeigt detaillierte Informationen über ein bestimmtes Gebäude in einem Modal
 * @param {number} buildingId - ID des Gebäudes
 */
function showBuildingDetails(buildingId) {
    const building = buildings.find(b => b.id == buildingId);
    if (!building) return;
    
    // Wenn es ein Casino ist - Casino öffnen
    if (building.type === 'casino') {
        openCasino(buildingId);
        return;
    }
    
    const modal = document.getElementById('buildingModal');
    const modalContent = document.getElementById('modalContent');
    
    const typeNames = {
        'farm': '🌾 Bauernhof',
        'mine': '⛏️ Mine',
        'trade_post': '🏪 Handelsposten',
        'warehouse': '🏭 Lagerhaus',
        'casino': '🎰 Casino'
    };
    
    modalContent.innerHTML = `
        <h3 class="text-2xl font-bold mb-4">${typeNames[building.type] || building.type}</h3>
        <p class="mb-2"><strong>Level:</strong> ${building.level}</p>
        <p class="mb-2"><strong>Einkommen:</strong> ${building.income_rate.toFixed(2)}/sek</p>
        ${building.is_upgrading ? 
            `<div class="mb-4">
                <p class="text-sm mb-2">Upgrade-Fortschritt:</p>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-blue-600 h-4 rounded-full transition-all" style="width: ${building.upgrade_progress}%"></div>
                </div>
            </div>` :
            `<button onclick="startUpgrade(${building.id}); closeModal();" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                Gebäude verbessern
            </button>`
        }
        <button onclick="closeModal()" class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition mt-2">
            Schließen
        </button>
    `;
    
    modal.classList.remove('hidden');
}

/**
 * Startet das Upgrade eines Gebäudes
 * @param {number} buildingId - ID des zu verbessernden Gebäudes
 */
async function startUpgrade(buildingId) {
    try {
        const formData = new FormData();
        formData.append('action', 'upgrade');
        formData.append('building_id', buildingId);
        
        const response = await fetch('api/buildings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, false);
            await loadBuildings();
            await updateResources();
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Upgrade', true);
        console.error(error);
    }
}

/**
 * Löscht ein Gebäude nach Bestätigung durch den Benutzer
 * @param {number} buildingId - ID des zu löschenden Gebäudes
 */
async function deleteBuilding(buildingId) {
    if (!confirm('Möchten Sie dieses Gebäude wirklich löschen?')) return;
    
    try {
        const response = await fetch(`api/buildings.php?action=delete&building_id=${buildingId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, false);
            await loadBuildings();
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Löschen', true);
        console.error(error);
    }
}

/**
 * Schließt das Modal-Fenster für Gebäudedetails
 */
function closeModal() {
    document.getElementById('buildingModal').classList.add('hidden');
}

/**
 * Öffnet das Casino-Spiel für ein bestimmtes Gebäude
 * @param {number} buildingId - ID des Casino-Gebäudes
 */
async function openCasino(buildingId) {
    document.getElementById('casinoModal').classList.remove('hidden');
    
    const content = document.getElementById('casinoContent');
    content.innerHTML = `
        <div class="space-y-4">
            <div>
                <p id="casinoStatus" class="text-white">Generiere neue Zahl...</p>
            </div>
            <div id="casinoGame" class="hidden space-y-4">
                <p class="font-bold text-xl text-white">Letzte Zahl: <span id="lastNumber">-</span></p>
                <div>
                    <label class="block mb-2 text-white">Einsatz:</label>
                    <input type="number" id="casinoBet" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-800 text-white" min="1" value="10">
                </div>
                <div class="flex space-x-4">
                    <button onclick="placeBet('higher')" class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition">
                        Höher (>50)
                    </button>
                    <button onclick="placeBet('lower')" class="flex-1 bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 transition">
                        Tiefer (<50)
                    </button>
                </div>
                <button onclick="closeCasino()" class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition">
                    Schließen
                </button>
            </div>
        </div>
    `;
    
    // Zahl generieren
    try {
        const formData = new FormData();
        formData.append('action', 'generate');
        
        const response = await fetch('api/casino.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('lastNumber').textContent = data.number;
            document.getElementById('casinoStatus').textContent = 'Neue Zahl generiert!';
            document.getElementById('casinoGame').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Fehler beim Generieren der Zahl:', error);
    }
}

/**
 * Platziert eine Wette im Casino-Spiel
 * @param {string} guess - Vermutung des Spielers ('higher' oder 'lower')
 */
async function placeBet(guess) {
    const bet = parseInt(document.getElementById('casinoBet').value);
    
    if (bet <= 0) {
        showMessage('Ungültiger Einsatz', true);
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'bet');
        formData.append('bet', bet);
        formData.append('guess', guess);
        
        const response = await fetch('api/casino.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.win) {
                showMessage(`Gewonnen! Du hast ${data.winnings} Gold gewonnen!`, false);
                await updateResources();
            } else {
                showMessage('Verloren! Versuche es erneut.', true);
                await updateResources();
            }
            
            // Casino schließen
            closeCasino();
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Fehler beim Platzieren der Wette', true);
        console.error(error);
    }
}

/**
 * Schließt das Casino-Modal
 */
function closeCasino() {
    document.getElementById('casinoModal').classList.add('hidden');
}

/**
 * Zeigt eine Benachrichtigung am oberen rechten Bildschirmrand an
 * @param {string} message - Nachricht, die angezeigt werden soll
 * @param {boolean} isError - Ob es sich um eine Fehlermeldung handelt (Standard: false)
 */
function showMessage(message, isError = false) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-xl z-50 ${isError ? 'bg-red-500 text-white' : 'bg-green-500 text-white'}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
