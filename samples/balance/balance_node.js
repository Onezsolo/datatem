/**
 * Refer2Bundle API V1 - Get Balance Example (Node.js)
 * 
 * This example demonstrates how to retrieve your account balance
 * using the Refer2Bundle API.
 * 
 * Install dependencies: npm install axios
 */

const axios = require('axios');

const apiKey = 'your_api_key_here';
const apiUrl = 'https://refer2bundle.org/api/v1/balance';

// Make GET request
axios.get(apiUrl, {
    headers: {
        'X-API-KEY': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (response.data.status === 'success') {
        console.log('✅ Success!');
        console.log(`Balance: GHS ${response.data.data.balance}`);
        console.log(`Currency: ${response.data.data.currency}`);
        console.log(`User: ${response.data.data.user.name} (${response.data.data.user.email})`);
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

