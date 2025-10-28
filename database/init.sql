-- EmpireBuilder Datenbank, gefragt bei KI: "Erstelle eine Skizze für eine geschützte Datenbank, mit beispiel Code"

-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS empirebuilder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE empirebuilder;

-- Tabelle der Benutzer
-- Wichtige Felder:
--  - Ressourcen (gold, wood, stone, food) mit Defaults
--  - Clicker-Raten (clicker_wood/stone/food) als DECIMAL mit sinnvollen Defaults
--  - Bevölkerungs-Logik (population, population_multiplier)
--  - "casino_last_number" für das Casino-Feature
--  - Zeitstempel für Auditing
CREATE TABLE IF NOT EXISTS users 
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    gold INT DEFAULT 1000,
    wood INT DEFAULT 100,
    stone INT DEFAULT 100,
    food INT DEFAULT 100,
    clicker_wood DECIMAL(3,1) DEFAULT 1.0,
    clicker_stone DECIMAL(3,1) DEFAULT 1.0,
    clicker_food DECIMAL(3,1) DEFAULT 1.0,
    casino_last_number INT DEFAULT NULL,
    population INT DEFAULT 0,
    population_multiplier DECIMAL(5,2) DEFAULT 1.0,
    -- Fractional part of population for accumulation (0.1/sec generation)
    population_fraction DECIMAL(10,2) DEFAULT 0.0,
    -- Zeitpunkt der letzten Tick-Verarbeitung (für Nahrungsverbrauch)
    last_tick TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle der Gebäude
CREATE TABLE IF NOT EXISTS buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    level INT DEFAULT 1,
    income_rate DECIMAL(10,2) DEFAULT 0,
    upgrade_progress INT DEFAULT 0,
    is_upgrading BOOLEAN DEFAULT 0,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optimierung
CREATE INDEX idx_user_id ON buildings(user_id);
CREATE INDEX idx_building_type ON buildings(type);

