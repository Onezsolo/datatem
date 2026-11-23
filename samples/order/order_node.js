/**
 * Refer2Bundle API V1 - Create Order Example (Node.js)
 * 
 * This example demonstrates how to create a data order
 * using the Refer2Bundle API.
 * 
 * Install dependencies: npm install axios
 */

const axios = require('axios');

const apiKey = 'your_api_key_here';
const apiUrl = 'https://refer2bundle.org/api/v1/order';

// Order data
const data = {
    network: 'MTN',
    data_plan: '1GB',
    beneficiary: '0241234567'
};

// Make POST request
axios.post(apiUrl, data, {
    headers: {
        'X-API-KEY': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (response.data.status === 'success') {
        console.log('✅ Order Created Successfully!');
        console.log(`Reference: ${response.data.data.reference}`);
        console.log(`Network: ${response.data.data.network}`);
        console.log(`Data Plan: ${response.data.data.data_plan}`);
        console.log(`Beneficiary: ${response.data.data.beneficiary}`);
        console.log(`Amount: GHS ${response.data.data.amount}`);
        console.log(`Status: ${response.data.data.status}`);
    } else {
        console.error('❌ Error:', response.data.data?.message || 'Unknown error');
    }
})
.catch(error => {
    if (error.response) {
        console.error('❌ HTTP Error:', error.response.status);
        console.error('Response:', error.response.data);
    } else {
        console.error('❌ Request Error:', error.message);
    }
});

