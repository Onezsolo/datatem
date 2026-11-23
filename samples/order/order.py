#!/usr/bin/env python3
"""
DataBossHub API V1 - Create Order Example (Python)
 
This example demonstrates how to create a data order
using the DataBossHub API.
"""

import requests
import json

# Configuration
api_key = "your_api_key_here"
api_url = "https://databosshub.org/api/v1/order"

# Order data
data = {
    "network": "MTN",
    "data_plan": "1GB",
    "beneficiary": "0241234567"
}

# Set headers
headers = {
    "X-API-KEY": api_key,
    "Content-Type": "application/json"
}

try:
    # Make POST request
    response = requests.post(api_url, json=data, headers=headers)
    
    # Check response
    if response.status_code == 201:
        result = response.json()
        if result['status'] == 'success':
            print("✅ Order Created Successfully!")
            print(f"Reference: {result['data']['reference']}")
            print(f"Network: {result['data']['network']}")
            print(f"Data Plan: {result['data']['data_plan']}")
            print(f"Beneficiary: {result['data']['beneficiary']}")
            print(f"Amount: GHS {result['data']['amount']}")
            print(f"Status: {result['data']['status']}")
        else:
            print(f"❌ Error: {result.get('data', {}).get('message', 'Unknown error')}")
    else:
        print(f"❌ HTTP Error: {response.status_code}")
        print(f"Response: {response.text}")
        
except requests.exceptions.RequestException as e:
    print(f"❌ Request Error: {e}")

