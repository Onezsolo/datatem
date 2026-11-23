import java.net.HttpURLConnection;
import java.net.URL;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import org.json.JSONObject;

/**
 * DataBossHub API V1 - Get Balance Example (Java)
 * 
 * This example demonstrates how to retrieve your account balance
 * using the DataBossHub API.
 * 
 * Requires: org.json library (add to your project)
 */

public class Balance {
    public static void main(String[] args) {
        String apiKey = "your_api_key_here";
        String apiUrl = "https://databosshub.org/api/v1/balance";
        
        try {
            // Create connection
            URL url = new URL(apiUrl);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");
            conn.setRequestProperty("X-API-KEY", apiKey);
            conn.setRequestProperty("Content-Type", "application/json");
            
            // Get response code
            int responseCode = conn.getResponseCode();
            
            if (responseCode == 200) {
                // Read response
                BufferedReader in = new BufferedReader(
                    new InputStreamReader(conn.getInputStream())
                );
                String inputLine;
                StringBuilder response = new StringBuilder();
                while ((inputLine = in.readLine()) != null) {
                    response.append(inputLine);
                }
                in.close();
                
                // Parse JSON
                JSONObject json = new JSONObject(response.toString());
                
                if (json.getString("status").equals("success")) {
                    JSONObject data = json.getJSONObject("data");
                    System.out.println("✅ Success!");
                    System.out.println("Balance: GHS " + data.getString("balance"));
                    System.out.println("Currency: " + data.getString("currency"));
                    JSONObject user = data.getJSONObject("user");
                    System.out.println("User: " + user.getString("name") + 
                                     " (" + user.getString("email") + ")");
                } else {
                    System.out.println("❌ Error: " + 
                        data.optJSONObject("data").optString("message", "Unknown error"));
                }
            } else {
                System.out.println("❌ HTTP Error: " + responseCode);
                BufferedReader errorReader = new BufferedReader(
                    new InputStreamReader(conn.getErrorStream())
                );
                String errorLine;
                while ((errorLine = errorReader.readLine()) != null) {
                    System.out.println(errorLine);
                }
                errorReader.close();
            }
            
        } catch (Exception e) {
            System.out.println("❌ Error: " + e.getMessage());
            e.printStackTrace();
        }
    }
}

