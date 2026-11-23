#!/bin/bash
# DataBossHub API V1 - Get Balance Example (cURL/Bash)
# 
# This example demonstrates how to retrieve your account balance
# using the DataBossHub API.

API_KEY="your_api_key_here"
API_URL="https://databosshub.org/api/v1/balance"

# Make GET request
response=$(curl -s -w "\n%{http_code}" -X GET "$API_URL" \
  -H "X-API-KEY: $API_KEY" \
  -H "Content-Type: application/json")

# Extract HTTP code and body
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

# Check response
if [ "$http_code" -eq 200 ]; then
  # Parse JSON (requires jq)
  if command -v jq &> /dev/null; then
    status=$(echo "$body" | jq -r '.status')
    if [ "$status" = "success" ]; then
      echo "✅ Success!"
      echo "$body" | jq -r '"Balance: GHS " + .data.balance'
      echo "$body" | jq -r '"Currency: " + .data.currency'
      echo "$body" | jq -r '"User: " + .data.user.name + " (" + .data.user.email + ")"'
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

