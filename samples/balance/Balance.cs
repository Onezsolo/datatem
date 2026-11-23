using System;
using System.Net.Http;
using System.Threading.Tasks;
using Newtonsoft.Json;

/**
 * DataBossHub API V1 - Get Balance Example (C#)
 * 
 * This example demonstrates how to retrieve your account balance
 * using the DataBossHub API.
 * 
 * Requires: Newtonsoft.Json NuGet package
 */

class Program
{
    static async Task Main()
    {
        string apiKey = "your_api_key_here";
        string apiUrl = "https://databosshub.org/api/v1/balance";
        
        using (HttpClient client = new HttpClient())
        {
            client.DefaultRequestHeaders.Add("X-API-KEY", apiKey);
            client.DefaultRequestHeaders.Add("Content-Type", "application/json");
            
            try
            {
                HttpResponseMessage response = await client.GetAsync(apiUrl);
                string responseBody = await response.Content.ReadAsStringAsync();
                
                if (response.IsSuccessStatusCode)
                {
                    dynamic data = JsonConvert.DeserializeObject(responseBody);
                    
                    if (data.status == "success")
                    {
                        Console.WriteLine("✅ Success!");
                        Console.WriteLine($"Balance: GHS {data.data.balance}");
                        Console.WriteLine($"Currency: {data.data.currency}");
                        Console.WriteLine($"User: {data.data.user.name} ({data.data.user.email})");
                    }
                    else
                    {
                        Console.WriteLine($"❌ Error: {data.data?.message ?? "Unknown error"}");
                    }
                }
                else
                {
                    Console.WriteLine($"❌ HTTP Error: {(int)response.StatusCode}");
                    Console.WriteLine($"Response: {responseBody}");
                }
            }
            catch (Exception e)
            {
                Console.WriteLine($"❌ Error: {e.Message}");
            }
        }
    }
}

