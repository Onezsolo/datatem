<?php
/**
 * Refer2Bundle API V1 - Get Bundles Example (PHP)
 * 
 * This example demonstrates how to retrieve available data bundles
 * using the Refer2Bundle API.
 */

$api_key = "your_api_key_here"; // Optional for public bundles
$api_url = "https://refer2bundle.org/api/v1/bundles";

// Optional: Filter by network
$network = "MTN"; // Leave empty for all networks
if (!empty($network)) {
    $api_url .= "?network=" . urlencode($network);
}

// Initialize cURL
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$headers = ["Content-Type: application/json"];

// Add API key if provided (optional for bundles)
if (!empty($api_key) && $api_key !== "your_api_key_here") {
    $headers[] = "X-API-KEY: " . $api_key;
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
    echo "Found " . $data['data']['count'] . " bundles\n\n";
    
    foreach ($data['data']['bundles'] as $bundle) {
        echo "Network: " . $bundle['network'] . "\n";
        echo "Plan: " . $bundle['plan_name'] . "\n";
        echo "Price: GHS " . $bundle['price'] . "\n";
        echo "---\n";
    }
} else {
    echo "❌ Error: " . ($data['data']['message'] ?? 'Unknown error') . "\n";
    echo "HTTP Code: " . $http_code . "\n";
    echo "Response: " . $response . "\n";
}

