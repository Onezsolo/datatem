<?php
/**
 * Refer2Bundle API V1 - Get Balance Example (PHP)
 * 
 * This example demonstrates how to retrieve your account balance
 * using the Refer2Bundle API.
 */

$api_key = "your_api_key_here";
$api_url = "https://refer2bundle.org/api/v1/balance";

// Initialize cURL
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: " . $api_key,
    "Content-Type: application/json"
]);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($curl_error) {
    die("cURL Error: " . $curl_error . "\n");
}

// Parse response
$data = json_decode($response, true);

if ($http_code === 200 && $data['status'] === 'success') {
    echo "✅ Success!\n";
    echo "Balance: GHS " . $data['data']['balance'] . "\n";
    echo "Currency: " . $data['data']['currency'] . "\n";
    echo "User: " . $data['data']['user']['name'] . " (" . $data['data']['user']['email'] . ")\n";
} else {
    echo "❌ Error: " . ($data['data']['message'] ?? 'Unknown error') . "\n";
    echo "HTTP Code: " . $http_code . "\n";
    echo "Response: " . $response . "\n";
}

