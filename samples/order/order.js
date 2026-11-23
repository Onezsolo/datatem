/**
 * Refer2Bundle API V1 - Create Order Example (JavaScript)
 * 
 * This example demonstrates how to create a data order
 * using the Refer2Bundle API.
 */

const apiKey = 'your_api_key_here';
const apiUrl = 'https://refer2bundle.org/api/v1/order';

// Order data
const data = {
    network: 'MTN',
    data_plan: '1GB',
    beneficiary: '0241234567'
};

// Make POST request
fetch(apiUrl, {
    method: 'POST',
    headers: {
        'X-API-KEY': apiKey,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
})
.then(response => {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
})
.then(result => {
    if (result.status === 'success') {
        console.log('✅ Order Created Successfully!');
        console.log(`Reference: ${result.data.reference}`);
        console.log(`Network: ${result.data.network}`);
        console.log(`Data Plan: ${result.data.data_plan}`);
        console.log(`Beneficiary: ${result.data.beneficiary}`);
        console.log(`Amount: GHS ${result.data.amount}`);
        console.log(`Status: ${result.data.status}`);
    } else {
        console.error('❌ Error:', result.data?.message || 'Unknown error');
    }
})
.catch(error => {
    console.error('❌ Request Error:', error.message);
});

