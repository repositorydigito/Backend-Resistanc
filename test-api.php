<?php

/**
 * Simple API Tester for RSISTANC
 * 
 * Run with: php test-api.php
 */

$baseUrl = 'http://backend-resistanc.test/api';

function testEndpoint($url, $description) {
    echo "\n🧪 Testing: $description\n";
    echo "URL: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            echo "✅ Success! Found " . count($data['data']) . " items\n";
            if (count($data['data']) > 0) {
                echo "First item ID: " . ($data['data'][0]['id'] ?? 'N/A') . "\n";
            }
        } else {
            echo "✅ Success! Single item with ID: " . ($data['id'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Error: $httpCode\n";
        echo substr($response, 0, 200) . "...\n";
    }
    
    echo str_repeat('-', 50) . "\n";
}

echo "🚀 RSISTANC API Tester\n";
echo str_repeat('=', 50) . "\n";

// Test endpoints
testEndpoint("$baseUrl/users", "Lista de usuarios");
testEndpoint("$baseUrl/users?per_page=3", "Lista de usuarios (3 por página)");
testEndpoint("$baseUrl/users?search=pablo", "Búsqueda de usuarios");
testEndpoint("$baseUrl/users/1", "Usuario específico");
testEndpoint("$baseUrl/users/1/profile", "Perfil del usuario");
testEndpoint("$baseUrl/users/1/contacts", "Contactos del usuario");
testEndpoint("$baseUrl/users/1/social-accounts", "Cuentas sociales");
testEndpoint("$baseUrl/users/1/login-audits", "Auditorías de login");

echo "\n🎉 Testing completed!\n";
echo "💡 Tip: Use Postman or browser for better JSON visualization\n";
