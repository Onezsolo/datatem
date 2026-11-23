<?php
/**
 * Refer2Bundle API V1 - Create Order Example (PHP)
 * 
 * This example demonstrates how to create a data order
 * using the Refer2Bundle API.
 */

$api_key = "your_api_key_here";
$api_url = "https://refer2bundle.org/api/v1/order";

// Order data
$data = [
    "network" => "MTN",
    "data_plan" => "1GB",
    "beneficiary" => "0241234567"
];

// Initialize cURL
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
$result = json_decode($response, true);

if ($http_code === 201 && $result['status'] === 'success') {
    echo "✅ Order Created Successfully!\n";
    echo "Reference: " . $result['data']['reference'] . "\n";
    echo "Network: " . $result['data']['network'] . "\n";
    echo "Data Plan: " . $result['data']['data_plan'] . "\n";
    echo "Beneficiary: " . $result['data']['beneficiary'] . "\n";
    echo "Amount: GHS " . $result['data']['amount'] . "\n";
    echo "Status: " . $result['data']['status'] . "\n";
} else {
    echo "❌ Error: " . ($result['data']['message'] ?? 'Unknown error') . "\n";
    echo "HTTP Code: " . $http_code . "\n";
    echo "Response: " . $response . "\n";
}

