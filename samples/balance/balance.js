/**
 * Refer2Bundle API V1 - Get Balance Example (JavaScript)
 * 
 * This example demonstrates how to retrieve your account balance
 * using the Refer2Bundle API.
 */

const apiKey = 'your_api_key_here';
const apiUrl = 'https://refer2bundle.org/api/v1/balance';

// Make GET request
fetch(apiUrl, {
    method: 'GET',
    headers: {
        'X-API-KEY': apiKey,
        'Content-Type': 'application/json'
    }
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
        console.log(`Balance: GHS ${data.data.balance}`);
        console.log(`Currency: ${data.data.currency}`);
        console.log(`User: ${data.data.user.name} (${data.data.user.email})`);
    } else {
        console.error('❌ Error:', data.data?.message || 'Unknown error');
    }
})
.catch(error => {
    console.error('❌ Request Error:', error.message);
});

