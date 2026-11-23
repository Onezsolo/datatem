#!/bin/bash
# DataBossHub API V1 - Create Order Example (cURL/Bash)
# 
# This example demonstrates how to create a data order
# using the DataBossHub API.

API_KEY="your_api_key_here"
API_URL="https://databosshub.org/api/v1/order"

# Order data
DATA='{
  "network": "MTN",
  "data_plan": "1GB",
  "beneficiary": "0241234567"
}'

# Make POST request
response=$(curl -s -w "\n%{http_code}" -X POST "$API_URL" \
  -H "X-API-KEY: $API_KEY" \
  -H "Content-Type: application/json" \
  -d "$DATA")

# Extract HTTP code and body
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

# Check response
if [ "$http_code" -eq 201 ]; then
  # Parse JSON (requires jq)
  if command -v jq &> /dev/null; then
    status=$(echo "$body" | jq -r '.status')
    if [ "$status" = "success" ]; then
      echo "✅ Order Created Successfully!"
      echo "$body" | jq -r '"Reference: " + .data.reference'
      echo "$body" | jq -r '"Network: " + .data.network'
      echo "$body" | jq -r '"Data Plan: " + .data.data_plan'
      echo "$body" | jq -r '"Beneficiary: " + .data.beneficiary'
      echo "$body" | jq -r '"Amount: GHS " + .data.amount'
      echo "$body" | jq -r '"Status: " + .data.status'
    else
      echo "❌ Error: $(echo "$body" | jq -r '.data.message // "Unknown error"')"
    fi
  else
    echo "Response: $body"
    echo "Note: Install 'jq' for better JSON parsing"
  fi
else
  echo "❌ HTTP Error: $http_code"
  echo "Response: $body"
fi

