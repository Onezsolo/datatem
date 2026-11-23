/**
 * Refer2Bundle API V1 - Get Bundles Example (JavaScript)
 * 
 * This example demonstrates how to retrieve available data bundles
 * using the Refer2Bundle API.
 */

const apiKey = 'your_api_key_here'; // Optional for public bundles
let apiUrl = 'https://refer2bundle.org/api/v1/bundles';

// Optional: Filter by network
const network = 'MTN'; // Leave empty for all networks
if (network) {
    apiUrl += '?network=' + encodeURIComponent(network);
}

// Set headers
const headers = {
    'Content-Type': 'application/json'
};

// Add API key if provided (optional for bundles)
if (apiKey && apiKey !== 'your_api_key_here') {
    headers['X-API-KEY'] = apiKey;
}

// Make GET request
fetch(apiUrl, {
    method: 'GET',
    headers: headers
})
.then(response => {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
})
.then(data => {
    if (data.status === 'success') {
        console.log('✅ Success!');
        console.log(`Found ${data.data.count} bundles\n`);
        
        data.data.bundles.forEach(bundle => {
            console.log(`Network: ${bundle.network}`);
            console.log(`Plan: ${bundle.plan_name}`);
            console.log(`Price: GHS ${bundle.price}`);
            console.log('---');
        });
    } else {
        console.error('❌ Error:', data.data?.message || 'Unknown error');
    }
})
.catch(error => {
    console.error('❌ Request Error:', error.message);
});

