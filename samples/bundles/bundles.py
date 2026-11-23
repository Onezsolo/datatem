#!/usr/bin/env python3
"""
DataBossHub API V1 - Get Bundles Example (Python)
 
This example demonstrates how to retrieve available data bundles
using the DataBossHub API.
"""

import requests

# Configuration
api_key = "your_api_key_here"  # Optional for public bundles
api_url = "https://databosshub.org/api/v1/bundles"

# Optional: Filter by network
params = {}
network = "MTN"  # Leave empty for all networks
if network:
    params['network'] = network

# Set headers
headers = {
    "Content-Type": "application/json"
}

# Add API key if provided (optional for bundles)
if api_key and api_key != "your_api_key_here":
    headers["X-API-KEY"] = api_key

try:
    # Make GET request
    response = requests.get(api_url, params=params, headers=headers)
    
    # Check response
    if response.status_code == 200:
        data = response.json()
        if data['status'] == 'success':
            print("✅ Success!")
            print(f"Found {data['data']['count']} bundles\n")
            
            for bundle in data['data']['bundles']:
                print(f"Network: {bundle['network']}")
                print(f"Plan: {bundle['plan_name']}")
                print(f"Price: GHS {bundle['price']}")
                print("---")
        else:
            print(f"❌ Error: {data.get('data', {}).get('message', 'Unknown error')}")
    else:
        print(f"❌ HTTP Error: {response.status_code}")
        print(f"Response: {response.text}")
        
except requests.exceptions.RequestException as e:
    print(f"❌ Request Error: {e}")

