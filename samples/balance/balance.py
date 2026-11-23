#!/usr/bin/env python3
"""
DataBossHub API V1 - Get Balance Example (Python)
 
This example demonstrates how to retrieve your account balance
using the DataBossHub API.
"""

import requests
import json

# Configuration
api_key = "your_api_key_here"
api_url = "https://databosshub.org/api/v1/balance"

# Set headers
headers = {
    "X-API-KEY": api_key,
    "Content-Type": "application/json"
}

try:
    # Make GET request
    response = requests.get(api_url, headers=headers)
    
    # Check response
    if response.status_code == 200:
        data = response.json()
        if data['status'] == 'success':
            print("✅ Success!")
            print(f"Balance: GHS {data['data']['balance']}")
            print(f"Currency: {data['data']['currency']}")
            print(f"User: {data['data']['user']['name']} ({data['data']['user']['email']})")
        else:
            print(f"❌ Error: {data.get('data', {}).get('message', 'Unknown error')}")
    else:
        print(f"❌ HTTP Error: {response.status_code}")
        print(f"Response: {response.text}")
        
except requests.exceptions.RequestException as e:
    print(f"❌ Request Error: {e}")

