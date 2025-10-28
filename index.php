<?php 
require_once 'config/database.php'; //Datenbankverbindung
require_once 'includes/session.php'; //Sitzungsverwaltung


// Wenn der Benutzer bereits eingeloggt ist: Weiterleitung zum Dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>

<!-- HTML-Code für die Anmeldeseite -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>EmpireBuilder - Anmeldung / Registrierung</title> 
    <script src="https://cdn.tailwindcss.com"></script> <!-- Tailwind CSS -->
</head>
<body class="bg-gradient-to-br from-blue-900 to-purple-900 min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white text-center">
                <h1 class="text-3xl font-bold">🏰 EmpireBuilder 🏰</h1>
                <p class="mt-2 text-blue-100">Beste Wirtschaftsstrategie</p>
            </div>
            
            <div class="p-6">
                <!-- Tabs zum Wechseln zwischen Anmeldung und Registrierung -->
                <div class="flex border-b mb-6">
                    <button class="flex-1 py-2 px-4 font-semibold text-blue-600 border-b-2 border-blue-600" id="loginTab">
                        Anmeldung
                    </button>
                    <button class="flex-1 py-2 px-4 font-semibold text-gray-500" id="registerTab">
                        Registrierung
                    </button>
                </div>
                
                <!-- Anmeldeformular -->
                <div id="loginForm" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Benutzername</label>
                        <input type="text" id="loginUsername" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Passwort</label>
                        <input type="password" id="loginPassword" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div> 
                    <button onclick="login()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold"> <!-- Knopf für die Anmeldung -->
                        Anmelden
                    </button>
                </div>
                
                <!-- Registrierungsformular -->
                <div id="registerForm" class="space-y-4 hidden">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Benutzername (min. 3 Zeichen)</label>
                        <input type="text" id="registerUsername" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Passwort (min. 6 Zeichen)</label>
                        <input type="password" id="registerPassword" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required> 
                    </div>
                    <button onclick="register()" class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-200 font-semibold"> <!-- Knopf für die Registrierung -->
                        Registrieren
                    </button>
                </div>
                
                <div id="message" class="mt-4"></div> <!-- Nachrichtenanzeige -->
            </div>
        </div>
    </div>
    <!-- JavaScript-Code für die Anmeldeseite -->
    <script> 
        // Umschalten zwischen Tabs (Anmeldung)
        document.getElementById('loginTab').addEventListener('click', () => {
            document.getElementById('loginForm').classList.remove('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginTab').classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('loginTab').classList.remove('text-gray-500');
            document.getElementById('registerTab').classList.remove('border-blue-600', 'text-blue-600');
            document.getElementById('registerTab').classList.add('text-gray-500');
        });
        // Umschalten zwischen Tabs (Registrierung)
        document.getElementById('registerTab').addEventListener('click', () => {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
            document.getElementById('registerTab').classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('registerTab').classList.remove('text-gray-500');
            document.getElementById('loginTab').classList.remove('border-blue-600', 'text-blue-600');
            document.getElementById('loginTab').classList.add('text-gray-500');
        });
        
        function showMessage(message, isError = false) { //Nachrichtenanzeige
            const messageDiv = document.getElementById('message');
            messageDiv.className = `mt-4 p-4 rounded-lg ${isError ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;
            messageDiv.textContent = message;
        }
        
        async function login() { //Anmeldefunktion
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;
            
            const formData = new FormData(); //Formulardaten
            formData.append('username', username);
            formData.append('password', password);
            
            try {
                const response = await fetch('api/login.php', { //API-Anfrage
                    method: 'POST',
                    body: formData
                });
                const data = await response.json(); //API-Antwort
                
                if (data.success) {
                    showMessage('Erfolgreich angemeldet!', false);
                    setTimeout(() => { //Weiterleitung nach erfolgreicher Anmeldung
                        window.location.href = data.redirect;
                    }, 1000);
                } else { //Fehlermeldung
                    showMessage(data.message, true);
                }
            } catch (error) {
                showMessage('Verbindungsfehler', true);
            }
        }
        
        async function register() { //Registrierungfunktion
            const username = document.getElementById('registerUsername').value;
            const password = document.getElementById('registerPassword').value;
            
            const formData = new FormData(); //Formulardaten
            formData.append('username', username);
            formData.append('password', password);
            
            try {
                const response = await fetch('api/register.php', { //API-Anfrage
                    method: 'POST',
                    body: formData
                });
                const data = await response.json(); //API-Antwort
                
                if (data.success) {
                    showMessage('Registrierung erfolgreich!', false);
                    setTimeout(() => { //Weiterleitung nach erfolgreicher Registrierung
                        window.location.href = data.redirect;
                    }, 1000);
                } else { //Fehlermeldung
                    showMessage(data.message, true);
                }
            } catch (error) {
                showMessage('Verbindungsfehler', true);
            }
        }
    </script>
</body>


<!-- Anmeldeseite, als Programmierungssprache habe ich meistens javascript verwendet, da es hier besser funktioniert.
 ich habe mit api gearbeitet, das sind die POST und GET requests. Für diese Technologie habe ich bei
 KI gefragt: "Wie erstelle ich eine API und wie kann ich sie verwenden in php?" -->

</html>
