#!/usr/bin/env ruby
# frozen_string_literal: true

require 'net/http'
require 'json'
require 'uri'

# DataBossHub API V1 - Get Balance Example (Ruby)
# 
# This example demonstrates how to retrieve your account balance
# using the DataBossHub API.

api_key = 'your_api_key_here'
api_url = URI('https://databosshub.org/api/v1/balance')

# Create HTTP connection
http = Net::HTTP.new(api_url.host, api_url.port)
http.use_ssl = true if api_url.scheme == 'https'

# Create request
request = Net::HTTP::Get.new(api_url)
request['X-API-KEY'] = api_key
request['Content-Type'] = 'application/json'

# Make request
begin
  response = http.request(request)
  
  if response.code == '200'
    data = JSON.parse(response.body)
    
    if data['status'] == 'success'
      puts '✅ Success!'
      puts "Balance: GHS #{data['data']['balance']}"
      puts "Currency: #{data['data']['currency']}"
      puts "User: #{data['data']['user']['name']} (#{data['data']['user']['email']})"
    else
      puts "❌ Error: #{data.dig('data', 'message') || 'Unknown error'}"
    end
  else
    puts "❌ HTTP Error: #{response.code}"
    puts "Response: #{response.body}"
  end
rescue => e
  puts "❌ Error: #{e.message}"
end

