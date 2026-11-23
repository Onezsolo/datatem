package main

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
)

/**
 * DataBossHub API V1 - Get Balance Example (Go)
 * 
 * This example demonstrates how to retrieve your account balance
 * using the DataBossHub API.
 */

type ApiResponse struct {
	Status string `json:"status"`
	Data   struct {
		Balance  string `json:"balance"`
		Currency string `json:"currency"`
		User     struct {
			ID    int    `json:"id"`
			Name  string `json:"name"`
			Email string `json:"email"`
		} `json:"user"`
	} `json:"data"`
}

func main() {
	apiKey := "your_api_key_here"
	apiUrl := "https://databosshub.org/api/v1/balance"

	// Create request
	req, err := http.NewRequest("GET", apiUrl, nil)
	if err != nil {
		fmt.Printf("❌ Error creating request: %v\n", err)
		return
	}

	// Set headers
	req.Header.Set("X-API-KEY", apiKey)
	req.Header.Set("Content-Type", "application/json")

	// Make request
	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		fmt.Printf("❌ Request error: %v\n", err)
		return
	}
	defer resp.Body.Close()

	// Read response
	body, err := ioutil.ReadAll(resp.Body)
	if err != nil {
		fmt.Printf("❌ Error reading response: %v\n", err)
		return
	}

	// Parse JSON
	var apiResp ApiResponse
	if err := json.Unmarshal(body, &apiResp); err != nil {
		fmt.Printf("❌ Error parsing JSON: %v\n", err)
		fmt.Printf("Response: %s\n", string(body))
		return
	}

	// Check status
	if resp.StatusCode == 200 && apiResp.Status == "success" {
		fmt.Println("✅ Success!")
		fmt.Printf("Balance: GHS %s\n", apiResp.Data.Balance)
		fmt.Printf("Currency: %s\n", apiResp.Data.Currency)
		fmt.Printf("User: %s (%s)\n", apiResp.Data.User.Name, apiResp.Data.User.Email)
	} else {
		fmt.Printf("❌ Error: HTTP %d\n", resp.StatusCode)
		fmt.Printf("Response: %s\n", string(body))
	}
}

